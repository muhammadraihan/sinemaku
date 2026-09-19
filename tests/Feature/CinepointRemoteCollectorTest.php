<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CinepointRemoteCollectorTest extends TestCase
{
    private const SECRET = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'cinepoint_test',
            'database.connections.cinepoint_test' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
            'services.cinepoint_ingest.secret' => self::SECRET,
            'services.cinepoint_ingest.key_id' => 'cinepoint-vps-1',
            'services.cinepoint.ingest_secret' => self::SECRET,
            'services.cinepoint.key_id' => 'cinepoint-vps-1',
            'services.cinepoint.mode' => 'remote',
            'services.cinepoint.lease_seconds' => 600,
        ]);
        DB::purge('cinepoint_test');
        DB::setDefaultConnection('cinepoint_test');

        require_once database_path('migrations/2026_09_19_000002_create_cinepoint_collector_deliveries.php');
        (new \CreateCinepointCollectorDeliveries)->up();

        Schema::create('cinepoint_daily_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('period_date')->nullable();
            $table->string('payload_fingerprint', 64)->nullable()->unique();
            $table->string('status', 20);
            $table->unsignedInteger('source_total')->nullable();
            $table->unsignedInteger('collected_count')->default(0);
            $table->boolean('is_complete')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
        Schema::create('cinepoint_daily_entries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('snapshot_id');
            $table->string('source_movie_id');
            $table->unsignedInteger('rank');
            $table->string('title');
            $table->text('poster_url')->nullable();
            $table->unsignedBigInteger('daily_admissions');
            $table->unsignedBigInteger('total_admissions');
            $table->timestamps();
            $table->unique(['snapshot_id', 'source_movie_id']);
        });
        Schema::create('cinepoint_sync_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status', 20)->default('queued');
            $table->unsignedTinyInteger('active_slot')->nullable()->unique();
            $table->string('scheduled_slot', 32)->nullable()->unique();
            $table->string('lease_token_hash', 64)->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('snapshot_id')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_hmac_rejects_missing_tampered_and_expired_requests_and_replays_idempotently(): void
    {
        $body = json_encode(['worker_id' => 'vps-1']);
        $this->postJson('/api/internal/cinepoint/sync-jobs/claim', json_decode($body, true))->assertStatus(401);

        $headers = $this->signedHeaders('POST', '/api/internal/cinepoint/sync-jobs/claim', $body);
        $headers['X-Cinepoint-Signature'] = str_repeat('0', 64);
        $this->call('POST', '/api/internal/cinepoint/sync-jobs/claim', [], [], [], $this->serverHeaders($headers), $body)->assertStatus(401);

        $expired = $this->signedHeaders('POST', '/api/internal/cinepoint/sync-jobs/claim', $body, time() - 301);
        $this->call('POST', '/api/internal/cinepoint/sync-jobs/claim', [], [], [], $this->serverHeaders($expired), $body)->assertStatus(401);

        $valid = $this->signedHeaders('POST', '/api/internal/cinepoint/sync-jobs/claim', $body);
        $this->call('POST', '/api/internal/cinepoint/sync-jobs/claim', [], [], [], $this->serverHeaders($valid), $body)->assertOk();
        $this->call('POST', '/api/internal/cinepoint/sync-jobs/claim', [], [], [], $this->serverHeaders($valid), $body)->assertOk();
    }

    public function test_snapshot_ingest_is_complete_readable_idempotent_and_keeps_changed_same_day(): void
    {
        Carbon::setTestNow('2026-09-19 10:00:00');
        $payload = $this->payload(10);
        $first = $this->signedJson('POST', '/api/internal/cinepoint/snapshots', $payload)->assertCreated()->json();
        $this->assertSame('stored', $first['status']);
        $this->assertDatabaseHas('cinepoint_daily_snapshots', ['id' => $first['snapshot_id'], 'status' => 'success', 'is_complete' => 1, 'collected_count' => 2]);
        $this->assertSame(['Film A', 'Film B'], DB::table('cinepoint_daily_entries')->where('snapshot_id', $first['snapshot_id'])->orderBy('rank')->pluck('title')->all());

        $duplicate = $this->signedJson('POST', '/api/internal/cinepoint/snapshots', $payload)->assertOk()->json();
        $this->assertSame('duplicate', $duplicate['status']);
        $this->assertSame($first['snapshot_id'], $duplicate['snapshot_id']);
        $this->assertSame(1, DB::table('cinepoint_daily_snapshots')->count());

        $changed = $this->payload(11);
        $second = $this->signedJson('POST', '/api/internal/cinepoint/snapshots', $changed)->assertCreated()->json();
        $this->assertNotSame($first['snapshot_id'], $second['snapshot_id']);
        $this->assertSame(2, DB::table('cinepoint_daily_snapshots')->where('period_date', '2026-09-19')->count());
        $this->assertSame(11, DB::table('cinepoint_daily_entries')->where('snapshot_id', $second['snapshot_id'])->where('rank', 1)->value('daily_admissions'));
    }

    public function test_ingest_strictly_rejects_malformed_json_shape_and_values_without_writes(): void
    {
        $body = '{bad';
        $headers = $this->signedHeaders('POST', '/api/internal/cinepoint/snapshots', $body);
        $this->call('POST', '/api/internal/cinepoint/snapshots', [], [], [], $this->serverHeaders($headers), $body)->assertStatus(422);

        $extra = $this->payload(10);
        $extra['unexpected'] = true;
        $this->signedJson('POST', '/api/internal/cinepoint/snapshots', $extra)->assertStatus(422);

        $invalid = $this->payload(10);
        $invalid['entries'][0]['daily_admissions'] = '10';
        $this->signedJson('POST', '/api/internal/cinepoint/snapshots', $invalid)->assertStatus(422);
        $this->assertSame(0, DB::table('cinepoint_daily_snapshots')->count());
        $this->assertSame(0, DB::table('cinepoint_daily_entries')->count());
    }

    public function test_manual_request_is_leased_once_then_success_result_links_snapshot(): void
    {
        $requestId = DB::table('cinepoint_sync_requests')->insertGetId([
            'status' => 'queued', 'active_slot' => 1, 'attempts' => 0, 'requested_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        $claim = $this->signedJson('POST', '/api/internal/cinepoint/sync-jobs/claim', ['worker_id' => 'vps-1'])->assertOk()->json();
        $this->assertSame($requestId, $claim['job']['id']);
        $this->assertNotEmpty($claim['job']['lease_token']);
        $this->assertDatabaseHas('cinepoint_sync_requests', ['id' => $requestId, 'status' => 'running', 'attempts' => 1]);
        $this->signedJson('POST', '/api/internal/cinepoint/sync-jobs/claim', ['worker_id' => 'vps-2'])->assertOk()->assertJson(['job' => null]);

        $result = $this->signedJson('POST', "/api/internal/cinepoint/sync-jobs/{$requestId}/result", [
            'lease_token' => $claim['job']['lease_token'], 'status' => 'success', 'snapshot' => $this->payload(10),
        ])->assertOk()->json();
        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('cinepoint_sync_requests', ['id' => $requestId, 'status' => 'success', 'snapshot_id' => $result['snapshot_id']]);
    }

    public function test_expired_lease_can_be_reclaimed_and_old_token_cannot_report(): void
    {
        $id = DB::table('cinepoint_sync_requests')->insertGetId([
            'status' => 'running', 'active_slot' => 1, 'lease_token_hash' => hash('sha256', 'old-token'), 'lease_expires_at' => now()->subSecond(),
            'attempts' => 1, 'requested_at' => now()->subMinute(), 'started_at' => now()->subMinute(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $claim = $this->signedJson('POST', '/api/internal/cinepoint/sync-jobs/claim', ['worker_id' => 'vps-1'])->assertOk()->json();
        $this->assertSame($id, $claim['job']['id']);
        $this->assertSame(2, DB::table('cinepoint_sync_requests')->where('id', $id)->value('attempts'));

        $this->signedJson('POST', "/api/internal/cinepoint/sync-jobs/{$id}/result", [
            'lease_token' => 'old-token', 'status' => 'failed', 'error_code' => 'worker_failed',
        ])->assertStatus(409);
        $this->signedJson('POST', "/api/internal/cinepoint/sync-jobs/{$id}/result", [
            'lease_token' => $claim['job']['lease_token'], 'status' => 'failed', 'error_code' => 'browser_failed',
        ])->assertOk();
        $this->assertDatabaseHas('cinepoint_sync_requests', ['id' => $id, 'status' => 'failed', 'failure_reason' => 'browser_failed']);
    }

    public function test_delivery_replay_is_permanent_and_conflicting_body_is_rejected(): void
    {
        $path = '/api/internal/cinepoint/snapshots';
        $body = json_encode($this->payload(10));
        $headers = $this->signedHeaders('POST', $path, $body);
        $first = $this->call('POST', $path, [], [], [], $this->serverHeaders($headers), $body)->assertCreated()->json();
        $this->call('POST', $path, [], [], [], $this->serverHeaders($headers), $body)->assertOk()->assertJson(['snapshot_id' => $first['snapshot_id']]);
        $body2 = json_encode($this->payload(11));
        $headers['X-Cinepoint-Body-Sha256'] = hash('sha256', $body2);
        $headers['X-Cinepoint-Signature'] = hash_hmac('sha256', "POST\n".$path."\n".$headers['X-Cinepoint-Timestamp']."\n".$headers['X-Cinepoint-Delivery']."\n".hash('sha256', $body2), self::SECRET);
        $this->call('POST', $path, [], [], [], $this->serverHeaders($headers), $body2)->assertStatus(409);
        $this->assertSame(1, DB::table('cinepoint_daily_snapshots')->count());
        $this->assertSame(1, DB::table('cinepoint_collector_deliveries')->count());
    }

    public function test_exhausted_expired_job_releases_slot_and_expired_results_are_rejected(): void
    {
        $queue = app(\App\Services\CinepointRemoteSyncQueue::class);
        $queue->enqueue();
        $job = $queue->claim('test')['job'];
        DB::table('cinepoint_sync_requests')->where('id', $job['id'])->update(['attempts'=>3, 'lease_expires_at'=>now()->subSecond()]);
        $this->signedJson('POST', '/api/internal/cinepoint/sync-jobs/'.$job['id'].'/result', [
            'lease_token'=>$job['lease_token'], 'status'=>'failed', 'error_code'=>'browser_failed',
        ])->assertStatus(409);
        $this->assertNull($queue->claim('test')['job']);
        $this->assertDatabaseHas('cinepoint_sync_requests', ['id'=>$job['id'], 'status'=>'failed', 'active_slot'=>null]);
        $this->assertSame('queued', $queue->enqueue()['status']);
        $this->assertSame(2, DB::table('cinepoint_sync_requests')->count());
    }

    public function test_invalid_posters_counts_ranks_dates_and_types_are_atomic(): void
    {
        $cases = [];
        $p=$this->payload(10); $p['entries'][0]['poster_url']='https://evil.test/x'; $cases[]=$p;
        $p=$this->payload(10); $p['entries'][1]['rank']=1; $cases[]=$p;
        $p=$this->payload(10); $p['entries'][1]['source_movie_id']='a'; $cases[]=$p;
        $p=$this->payload(10); $p['source_total']=3; $cases[]=$p;
        $p=$this->payload(10); $p['period_label']='Feb 30, 2026'; $cases[]=$p;
        $p=$this->payload(10); $p['entries'][0]['daily_admissions']=-1; $cases[]=$p;
        foreach ($cases as $payload) $this->signedJson('POST','/api/internal/cinepoint/snapshots',$payload)->assertStatus(422);
        $this->assertSame(0,DB::table('cinepoint_daily_snapshots')->count());
        $this->assertSame(0,DB::table('cinepoint_collector_deliveries')->count());
    }

    public function test_manual_web_sync_authentication_coalescing_and_remote_scheduler(): void
    {
        $path='/backoffice/audience-estimate/cinepoint/sync';
        $this->post($path)->assertRedirect();
        $this->assertSame(0,DB::table('cinepoint_sync_requests')->count());
        $user=new \App\Models\User;
        $user->id=1;
        $this->actingAs($user);
        $this->post($path)->assertRedirect()->assertSessionHas('cinepoint_success');
        $this->post($path)->assertRedirect()->assertSessionHas('cinepoint_success');
        $this->assertSame(1,DB::table('cinepoint_sync_requests')->count());
        $this->assertSame(0,DB::table('cinepoint_daily_snapshots')->count());
        $data=app(\App\Http\Controllers\CinepointDailyController::class)->ranking()->getData(true);
        $this->assertSame('queued',$data['remote_job']['status']);
        $schedule=app(\Illuminate\Console\Scheduling\Schedule::class);
        foreach ($schedule->events() as $event) $this->assertStringNotContainsString('cinepoint:collect-daily', (string)$event->command);
    }

    public function test_auth_binds_key_body_path_and_enforces_tls_and_size(): void
    {
        $path='/api/internal/cinepoint/snapshots';
        $body=json_encode($this->payload(10));
        $headers=$this->signedHeaders('POST',$path,$body);
        $bad=$headers; $bad['X-Cinepoint-Key-Id']='wrong-key';
        $this->call('POST',$path,[],[],[],$this->serverHeaders($bad),$body)->assertStatus(401);
        $this->call('POST',$path,[],[],[],$this->serverHeaders($headers),$body.' ')->assertStatus(401);
        $this->call('POST','/api/internal/cinepoint/sync-jobs/claim',[],[],[],$this->serverHeaders($headers),$body)->assertStatus(401);
        $large=str_repeat(' ',2097153);
        $this->call('POST',$path,[],[],[],$this->serverHeaders($this->signedHeaders('POST',$path,$large)),$large)->assertStatus(413);
        $this->app->instance('env','production');
        $this->call('POST',$path,[],[],[],$this->serverHeaders($headers),$body)->assertStatus(403);
        $this->assertSame(0,DB::table('cinepoint_daily_snapshots')->count());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(int $daily): array
    {
        return [
            'period_label' => 'Sep 19, 2026', 'source_total' => 2,
            'entries' => [
                ['source_movie_id' => 'a', 'rank' => 1, 'title' => 'Film A', 'poster_url' => 'https://cinepoint-assets.s3.amazonaws.com/a.jpg', 'daily_admissions' => $daily, 'total_admissions' => 20],
                ['source_movie_id' => 'b', 'rank' => 2, 'title' => 'Film B', 'poster_url' => null, 'daily_admissions' => 5, 'total_admissions' => 8],
            ],
        ];
    }

    private function signedJson(string $method, string $path, array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return $this->call($method, $path, [], [], [], $this->serverHeaders($this->signedHeaders($method, $path, $body)), $body);
    }

    private function signedHeaders(string $method, string $path, string $body, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $nonce = bin2hex(random_bytes(16));
        $canonical = $method."\n".$path."\n".$timestamp."\n".$nonce."\n".hash('sha256', $body);
        return [
            'Content-Type' => 'application/json',
            'X-Cinepoint-Timestamp' => (string) $timestamp,
            'X-Cinepoint-Key-Id' => 'cinepoint-vps-1',
            'X-Cinepoint-Delivery' => $nonce,
            'X-Cinepoint-Body-Sha256' => hash('sha256', $body),
            'X-Cinepoint-Signature' => hash_hmac('sha256', $canonical, self::SECRET),
        ];
    }

    private function serverHeaders(array $headers): array
    {
        $server = [];
        foreach ($headers as $key => $value) {
            $server[$key === 'Content-Type' ? 'CONTENT_TYPE' : 'HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
        }
        return $server;
    }
}
