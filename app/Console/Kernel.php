<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Legacy discovery remains available manually, but is no longer scheduled.
        if (config('services.cinepoint.mode', 'remote') !== 'local') return;
        $schedule->command('cinepoint:collect-daily')
            ->cron('0 7,12,18 * * *')->timezone('Asia/Jakarta')
            ->withoutOverlapping(10);
        $schedule->call(function () {
            \Illuminate\Support\Facades\Cache::put('cinepoint-scheduler-heartbeat',
                \Carbon\Carbon::now('Asia/Jakarta')->toIso8601String(), 86400);
        })->everyMinute()->name('cinepoint-scheduler-heartbeat');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
