<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CityPerformanceController extends Controller
{
    public function index()
    {
        $latestRun = null;
        $source = null;

        if (Schema::hasTable('city_performance_collection_runs')) {
            $latestRun = DB::table('city_performance_collection_runs')
                ->where('status', 'complete')
                ->orderByDesc('period_date')
                ->orderByDesc('finished_at')
                ->first();
            if ($latestRun) {
                $source = DB::table('city_performance_sources')->find($latestRun->source_id);
            }
        }

        return view('seatmap-monitor.city-performance', compact('latestRun', 'source'));
    }
}
