<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CinepointRemoteSyncQueue
{
    public function enqueue(): array
    {
        try { return DB::transaction(function () {
            $existing = DB::table('cinepoint_sync_requests')
                ->whereIn('status', ['queued', 'running'])
                ->orderByDesc('id')->first();
            if ($existing) return $this->publicJob($existing, 'already_queued');

            $now = now();
            $id = DB::table('cinepoint_sync_requests')->insertGetId([
                'status' => 'queued', 'active_slot' => 1, 'attempts' => 0,
                'requested_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            return ['status' => 'queued', 'job' => $this->publicJob(DB::table('cinepoint_sync_requests')->find($id))];
        }, 3); } catch (\Illuminate\Database\QueryException $e) {
            $existing = DB::table('cinepoint_sync_requests')->where('active_slot',1)->first();
            if ($existing) return $this->publicJob($existing, 'already_queued');
            throw $e;
        }
    }

    public function claim(string $workerId): array
    {
        return DB::transaction(function () use ($workerId) {
            DB::table('cinepoint_sync_requests')->where('status', 'running')->where('lease_expires_at', '<', now())->where('attempts', '>=', (int) config('services.cinepoint.max_attempts', 3))->update([
                'status' => 'failed', 'failure_reason' => 'max_attempts_exceeded', 'active_slot' => null, 'finished_at' => now(), 'updated_at' => now(),
            ]);
            $job = DB::table('cinepoint_sync_requests')->where(function ($query) {
                $query->where('status', 'queued')
                    ->orWhere(function ($q) { $q->where('status', 'running')->where('lease_expires_at', '<', now()); });
            })->where('attempts', '<', (int) config('services.cinepoint.max_attempts', 3))->orderBy('requested_at')->lockForUpdate()->first();
            if (!$job) return ['job' => null];

            $token = Str::random(64);
            DB::table('cinepoint_sync_requests')->where('id', $job->id)->update([
                'status' => 'running', 'lease_token_hash' => hash('sha256', $token),
                'lease_expires_at' => now()->addSeconds((int) config('services.cinepoint.lease_seconds', 600)),
                'attempts' => $job->attempts + 1, 'started_at' => $job->started_at ?: now(),
                'updated_at' => now(),
            ]);
            $job = DB::table('cinepoint_sync_requests')->find($job->id);
            return ['job' => array_merge($this->publicJob($job), ['lease_token' => $token])];
        });
    }

    public function finish(int $id, string $token, string $status, ?array $snapshot = null, ?string $failure = null): array
    {
        if (!in_array($status, ['success', 'failed'], true)) throw new \UnexpectedValueException('Status hasil worker tidak valid.');
        return DB::transaction(function () use ($id, $token, $status, $snapshot, $failure) {
            $job = DB::table('cinepoint_sync_requests')->where('id', $id)->lockForUpdate()->first();
            if (!$job) throw new \UnexpectedValueException('Job tidak ditemukan.');
            if ($job->status !== 'running') throw new \RuntimeException('Job bukan running.', 409);
            if (!$job->lease_expires_at || now()->greaterThanOrEqualTo($job->lease_expires_at) || !hash_equals((string) $job->lease_token_hash, hash('sha256', $token))) {
                throw new \RuntimeException('Lease tidak valid, kedaluwarsa, atau sudah digantikan.', 409);
            }
            $snapshotId = null;
            if ($status === 'success') {
                if (!is_array($snapshot)) throw new \UnexpectedValueException('Snapshot sukses wajib disertakan.');
                $stored = app(CinepointDailyIngestor::class)->ingest($snapshot);
                $snapshotId = $stored['snapshot_id'];
            }
            DB::table('cinepoint_sync_requests')->where('id', $id)->update([
                'status' => $status, 'snapshot_id' => $snapshotId,
                'failure_reason' => $status === 'failed' ? $this->safeMessage($failure ?: 'Worker gagal tanpa alasan.') : null,
                'lease_token_hash' => null, 'lease_expires_at' => null, 'active_slot' => null,
                'finished_at' => now(), 'updated_at' => now(),
            ]);
            return ['status' => $status, 'job_id' => $id, 'snapshot_id' => $snapshotId];
        });
    }

    public function publicJob(object $job, string $status = null): array
    {
        return ['id' => (int) $job->id, 'status' => $status ?: $job->status, 'attempts' => (int) $job->attempts,
            'requested_at' => $job->requested_at, 'started_at' => $job->started_at, 'finished_at' => $job->finished_at,
            'failure_reason' => $job->failure_reason];
    }

    private function safeMessage(string $message): string
    {
        return in_array($message, ['browser_failed', 'worker_failed', 'invalid_snapshot'], true) ? $message : 'worker_failed';
    }
}
