<?php
namespace App\Http\Controllers;

use App\Services\CinepointDailyCollector;
use App\Services\CinepointRemoteSyncQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CinepointDailyController extends Controller
{
    public function index() { return view('seatmap-monitor.cinepoint', $this->data()); }
    public function ranking() { return response()->json($this->data()); }
    public function sync(\Illuminate\Http\Request $request, CinepointDailyCollector $collector, CinepointRemoteSyncQueue $queue)
    {
        try {
            if (config('services.cinepoint.mode', 'remote') !== 'local') {
                $queued = $queue->enqueue();
                if ($request->expectsJson()) {
                    $queued['status_url'] = route('seatmap-monitor.cinepoint.status', ['id' => $queued['job']['id']]);
                    return response()->json($queued, 202);
                }
                return redirect()->route('seatmap-monitor.index')->with('cinepoint_success', $queued['status'] === 'already_queued' ? 'Sinkronisasi sudah masuk antrean atau sedang diproses.' : 'Permintaan sinkronisasi masuk antrean VPS.');
            }
            $result = $collector->collect();
            if ($request->expectsJson()) return response()->json(['status' => 'success', 'result' => $result]);
            return redirect()->route('seatmap-monitor.index')->with('cinepoint_success', "Sinkronisasi lengkap berhasil: {$result['collected_count']}/{$result['source_total']} film tersimpan.");
        } catch (\Throwable $e) {
            $message = 'Sinkronisasi gagal; snapshot sukses sebelumnya tetap tersedia. Periksa konfigurasi collector dan database.';
            if ($request->expectsJson()) return response()->json(['message' => $message], 500);
            return redirect()->route('seatmap-monitor.index')->with('cinepoint_error', $message);
        }
    }
    public function status(int $id, CinepointRemoteSyncQueue $queue)
    {
        $job = DB::table('cinepoint_sync_requests')->find($id);
        abort_unless($job, 404);
        return response()->json(['job' => $queue->publicJob($job)])->header('Cache-Control', 'no-store');
    }

    private function data(): array
    {
        $latest = DB::table('cinepoint_daily_snapshots')->orderByDesc('id')->first();
        // Rank by source period, independently of when an unchanged payload was verified.
        $snapshot = DB::table('cinepoint_daily_snapshots')->where('status','success')->where('is_complete',true)->orderByDesc('period_date')->orderByDesc('id')->first();
        $snapshotSync = DB::table('cinepoint_daily_snapshots')->where('status','success')->where('is_complete',true)->max('last_verified_at');
        $requestSync = DB::table('cinepoint_sync_requests')->where('status','success')->max('finished_at');
        $createdSync = DB::table('cinepoint_daily_snapshots')->where('status','success')->where('is_complete',true)->max('finished_at');
        $lastSuccessfulSyncAt = collect([$snapshotSync, $requestSync, $createdSync])->filter()->max();
        $now = Carbon::now('Asia/Jakarta'); $next = null;
        foreach ([0,1] as $day) foreach ([7,12,18] as $hour) { $candidate=$now->copy()->startOfDay()->addDays($day)->setHour($hour); if ($candidate->greaterThan($now) && (!$next || $candidate->lessThan($next))) $next=$candidate; }
        $remoteJob = config('services.cinepoint.mode', 'remote') === 'local' ? null : DB::table('cinepoint_sync_requests')->orderByDesc('id')->first();
        return ['snapshot'=>$snapshot,'last_successful_sync_at'=>$lastSuccessfulSyncAt,'latest_attempt'=>$latest,'entries'=>$snapshot ? DB::table('cinepoint_daily_entries')->where('snapshot_id',$snapshot->id)->orderBy('rank')->get() : collect(),'history'=>DB::table('cinepoint_daily_snapshots')->orderByDesc('id')->limit(5)->get(),'source_url'=>'https://cinepoint.com/','attribution'=>'Data terpublikasi + estimasi proprietary Cinepoint','next_scheduled_at'=>$next->format('d M Y H:i').' WIB','scheduler_heartbeat'=>Cache::get('cinepoint-scheduler-heartbeat'),'remote_job'=>$remoteJob ? app(CinepointRemoteSyncQueue::class)->publicJob($remoteJob) : null,'collector_heartbeat'=>Cache::get('cinepoint-collector-heartbeat')];
    }
}
