<?php
namespace App\Http\Controllers;

use App\Services\CinepointDailyCollector;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CinepointDailyController extends Controller
{
    public function index() { return view('seatmap-monitor.cinepoint', $this->data()); }
    public function ranking() { return response()->json($this->data()); }
    public function sync(CinepointDailyCollector $collector)
    {
        try {
            $result = $collector->collect();
            return redirect()->route('seatmap-monitor.index')->with('cinepoint_success', "Sinkronisasi lengkap berhasil: {$result['collected_count']}/{$result['source_total']} film tersimpan.");
        } catch (\Throwable $e) {
            return redirect()->route('seatmap-monitor.index')->with('cinepoint_error', 'Sinkronisasi gagal; snapshot sukses sebelumnya tetap tersedia. Penyebab: '.mb_substr($e->getMessage(),0,500));
        }
    }
    private function data(): array
    {
        $latest = DB::table('cinepoint_daily_snapshots')->orderByDesc('id')->first();
        $snapshot = DB::table('cinepoint_daily_snapshots')->where('status','success')->where('is_complete',true)->orderByDesc('period_date')->orderByDesc('id')->first();
        $now = Carbon::now('Asia/Jakarta'); $next = null;
        foreach ([0,1] as $day) foreach ([7,12,18] as $hour) { $candidate=$now->copy()->startOfDay()->addDays($day)->setHour($hour); if ($candidate->greaterThan($now) && (!$next || $candidate->lessThan($next))) $next=$candidate; }
        return ['snapshot'=>$snapshot,'latest_attempt'=>$latest,'entries'=>$snapshot ? DB::table('cinepoint_daily_entries')->where('snapshot_id',$snapshot->id)->orderBy('rank')->get() : collect(),'history'=>DB::table('cinepoint_daily_snapshots')->orderByDesc('id')->limit(5)->get(),'source_url'=>'https://cinepoint.com/','attribution'=>'Data terpublikasi + estimasi proprietary Cinepoint','next_scheduled_at'=>$next->format('d M Y H:i').' WIB','scheduler_heartbeat'=>Cache::get('cinepoint-scheduler-heartbeat')];
    }
}
