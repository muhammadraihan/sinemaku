<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Reports\PlatinumPdfParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlatinumPdfImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'platinum_pdf_test', 'database.connections.platinum_pdf_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true], 'cache.default' => 'array']);
        DB::purge('platinum_pdf_test');
        DB::setDefaultConnection('platinum_pdf_test');
        Cache::flush();
        foreach (['users', 'kategori_bioskops', 'master_bioskops', 'master_films', 'type_tikets', 'kapasitas', 'kotas', 'provinces', 'pelaporans'] as $table) {
            Schema::create($table, function ($table) {
                $table->increments('id'); $table->string('uuid')->nullable()->unique(); $table->string('name')->nullable(); $table->string('email')->nullable(); $table->string('password')->nullable(); $table->rememberToken(); $table->string('nama')->nullable(); $table->string('nama_bioskop')->nullable(); $table->string('type')->nullable(); $table->string('kota')->nullable(); $table->string('provinsi_id')->nullable(); $table->string('kategori')->nullable(); $table->string('nama_film')->nullable(); $table->date('tgl_tayang')->nullable(); $table->time('jam_tayang')->nullable(); $table->string('show')->nullable(); $table->string('type_tiket')->nullable(); $table->string('harga')->nullable(); $table->string('jumlah')->nullable(); $table->string('gross')->nullable(); $table->string('tax')->nullable(); $table->string('net')->nullable(); $table->string('studio')->nullable(); $table->string('kapasitas')->nullable(); $table->string('provinsi')->nullable(); $table->string('created_by')->nullable(); $table->timestamps();
            });
        }
    }

    public function test_preview_then_confirm_inserts_platinum_rows_once_and_consumes_token(): void
    {
        $user = $this->seedMappings();
        $this->app->instance(PlatinumPdfParser::class, $this->parser());
        $file = UploadedFile::fake()->create('platinum.pdf', 10, 'application/pdf');
        $preview = $this->actingAs($user)->post(route('pelaporan.upload.platinum.preview'), ['file' => $file]);
        $preview->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('blocking_issues', [])->assertJsonCount(2, 'preview');
        $this->assertSame(0, DB::table('pelaporans')->count());
        $this->actingAs($user)->post(route('pelaporan.upload.platinum.confirm'), ['token' => $preview->json('token')])->assertOk()->assertJsonPath('inserted', 2);
        $this->assertSame(2, DB::table('pelaporans')->count());
        $this->actingAs($user)->post(route('pelaporan.upload.platinum.confirm'), ['token' => $preview->json('token')])->assertStatus(422);
    }

    public function test_preview_is_user_bound_and_replay_is_blocked(): void
    {
        $owner = $this->seedMappings();
        $otherId = DB::table('users')->insertGetId(['uuid' => 'user-2', 'name' => 'Other', 'email' => 'other@example.test', 'password' => bcrypt('secret')]);
        $this->app->instance(PlatinumPdfParser::class, $this->parser());
        $preview = $this->actingAs($owner)->post(route('pelaporan.upload.platinum.preview'), ['file' => UploadedFile::fake()->create('platinum.pdf', 10, 'application/pdf')]);
        $this->actingAs(User::findOrFail($otherId))->post(route('pelaporan.upload.platinum.confirm'), ['token' => $preview->json('token')])->assertStatus(403);
        $this->actingAs($owner)->post(route('pelaporan.upload.platinum.confirm'), ['token' => $preview->json('token')])->assertOk();
        $again = $this->actingAs($owner)->post(route('pelaporan.upload.platinum.preview'), ['file' => UploadedFile::fake()->create('platinum.pdf', 10, 'application/pdf')]);
        $this->actingAs($owner)->post(route('pelaporan.upload.platinum.confirm'), ['token' => $again->json('token')])->assertStatus(422);
    }

    private function parser(): PlatinumPdfParser
    {
        $parser = \Mockery::mock(PlatinumPdfParser::class);
        $parser->shouldReceive('parse')->andReturn([
            'cinema_name' => 'PLATINUM CINEPLEX CIBITUNG', 'film_name' => 'MEMBURU PEMANGSA', 'studio' => '2', 'report_date' => '2026-09-24', 'financial_profile' => 'standard_tax_exclusive_net', 'warnings' => [],
            'rows' => [
                ['tanggal' => '2026-09-24', 'jam_tayang' => '13:25', 'show' => 1, 'studio' => '2', 'type_tiket' => 'STANDARD', 'harga' => 30000, 'jumlah' => 2, 'gross' => 60000, 'tax_amount' => 5454.54, 'tax_rate' => 9.0909, 'net' => 54545.45],
                ['tanggal' => '2026-09-24', 'jam_tayang' => '15:35', 'show' => 2, 'studio' => '2', 'type_tiket' => 'STANDARD', 'harga' => 30000, 'jumlah' => 2, 'gross' => 60000, 'tax_amount' => 5454.54, 'tax_rate' => 9.0909, 'net' => 54545.45],
            ],
            'totals' => ['admits' => 4, 'gross' => 120000, 'tax_amount' => 10909.08, 'net' => 109090.90],
            'source_totals' => ['admits' => 4, 'gross' => 120000, 'tax_amount' => 10909.08, 'net' => 109090.90],
        ]);
        return $parser;
    }

    private function seedMappings(): User
    {
        $id = DB::table('users')->insertGetId(['uuid' => 'user-1', 'name' => 'Tester', 'email' => 'tester@example.test', 'password' => bcrypt('secret')]);
        DB::table('kategori_bioskops')->insert(['uuid' => 'category-1', 'name' => 'PLATINUM']);
        DB::table('master_bioskops')->insert(['uuid' => 'cinema-1', 'nama_bioskop' => 'PLATINUM CINEPLEX CIBITUNG', 'type' => 'category-1', 'kota' => 'BEKASI']);
        DB::table('master_films')->insert(['uuid' => 'film-1', 'name' => 'MEMBURU PEMANGSA']);
        DB::table('kotas')->insert(['uuid' => 'city-1', 'nama' => 'BEKASI', 'provinsi_id' => 'province-1']); DB::table('provinces')->insert(['uuid' => 'province-1', 'nama' => 'JAWA BARAT']);
        DB::table('type_tikets')->insert(['uuid' => 'ticket-1', 'name' => 'STANDARD', 'kategori' => 'category-1']);
        DB::table('kapasitas')->insert(['uuid' => 'capacity-1', 'kategori' => 'category-1', 'nama_bioskop' => 'cinema-1', 'type_tiket' => 'ticket-1', 'studio' => '2']);
        return User::findOrFail($id);
    }
}
