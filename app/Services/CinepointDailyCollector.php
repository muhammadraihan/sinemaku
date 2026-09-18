<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class CinepointDailyCollector
{
    public function collect(): array
    {
        $lock = Cache::lock('cinepoint-daily-collection', 600);
        if (!$lock->get()) throw new \RuntimeException('Sinkronisasi Cinepoint sedang berjalan. Tunggu maksimal 10 menit lalu coba lagi.');
        $id = null;
        try {
            $this->markStaleRunningAttempts();
            $id = DB::table('cinepoint_daily_snapshots')->insertGetId(['status'=>'running','started_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            $parsed = app(CinepointDailyPayloadParser::class)->parseBrowser($this->runBrowserCollector());
            DB::transaction(function () use ($id, $parsed) {
                foreach ($parsed['entries'] as $row) DB::table('cinepoint_daily_entries')->insert(array_merge($row, ['snapshot_id'=>$id,'created_at'=>now(),'updated_at'=>now()]));
                $stored = DB::table('cinepoint_daily_entries')->where('snapshot_id',$id)->count();
                $unique = DB::table('cinepoint_daily_entries')->where('snapshot_id',$id)->distinct()->count('source_movie_id');
                if ($stored !== $parsed['source_total'] || $unique !== $parsed['source_total']) throw new \RuntimeException("Verifikasi database gagal: sumber={$parsed['source_total']}, tersimpan={$stored}, unik={$unique}.");
                DB::table('cinepoint_daily_snapshots')->where('id',$id)->update(['period_date'=>$parsed['period_date'],'status'=>'success','source_total'=>$parsed['source_total'],'collected_count'=>$stored,'is_complete'=>true,'error_message'=>null,'finished_at'=>now(),'updated_at'=>now()]);
            });
            Cache::put('cinepoint-collector-heartbeat', now('Asia/Jakarta')->toIso8601String(), 86400);
            return ['id'=>$id,'status'=>'success','period_date'=>$parsed['period_date'],'collected_count'=>$parsed['source_total'],'source_total'=>$parsed['source_total'],'error_message'=>null];
        } catch (\Throwable $e) {
            if ($id) DB::table('cinepoint_daily_snapshots')->where('id',$id)->update(['status'=>'failed','error_message'=>$this->safeMessage($e),'finished_at'=>now(),'updated_at'=>now()]);
            throw new \RuntimeException($this->safeMessage($e), 0, $e);
        } finally { $lock->release(); }
    }

    private function runBrowserCollector(): array
    {
        $node = env('CINEPOINT_NODE_BINARY', 'node');
        $script = env('CINEPOINT_BROWSER_SCRIPT', base_path('scripts/cinepoint-daily-browser.cjs'));
        $process = new Process([$node, $script], base_path(), null, null, 120);
        $process->run();
        if (!$process->isSuccessful()) throw new \RuntimeException('Browser collector gagal: '.trim($process->getErrorOutput() ?: $process->getOutput()).' Pastikan Node.js, Chrome, dan npm install tersedia.');
        try { return json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable $e) { throw new \RuntimeException('Output browser collector bukan JSON yang valid.'); }
    }

    private function markStaleRunningAttempts(): void
    {
        DB::table('cinepoint_daily_snapshots')->where('status','running')->where('started_at','<',now()->subMinutes(15))->update(['status'=>'failed','error_message'=>'Proses sebelumnya tidak selesai dalam 15 menit (stale). Aman untuk dicoba ulang.','finished_at'=>now(),'updated_at'=>now()]);
    }

    private function safeMessage(\Throwable $e): string
    {
        $message = preg_replace('/[\r\n]+/', ' ', $e->getMessage());
        return mb_substr($message ?: 'Kesalahan tidak diketahui.', 0, 1500);
    }
}
