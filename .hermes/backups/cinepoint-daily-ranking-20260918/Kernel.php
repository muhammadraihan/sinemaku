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
        // Public read-only discovery only. This does not open order, seat, or payment flows.
        $schedule->command('seatmap:collect-showtimes --provider=cinepolis --max-movies=40')
            ->everyThirtyMinutes()
            ->withoutOverlapping(25)
            ->runInBackground();

        // TIX ID discovery is browser-rendered public metadata only. Seat-layout snapshots
        // remain intentionally disabled until the independent read-only safety probe passes.
        $schedule->command('seatmap:tix-discover --max-theaters=8 --max-cities=200 --theater-offset=auto')
            ->everyThirtyMinutes()
            ->withoutOverlapping(28)
            ->runInBackground();
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
