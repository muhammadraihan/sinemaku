<?php
namespace App\Console\Commands;
use App\Services\CinepointDailyCollector;
use Illuminate\Console\Command;
class CollectCinepointDailyCommand extends Command
{
    protected $signature = 'cinepoint:collect-daily';
    protected $description = 'Collect complete public Cinepoint daily ranking snapshot through configured browser runtime';
    public function handle(CinepointDailyCollector $collector): int
    {
        try { $this->info(json_encode($collector->collect(), JSON_UNESCAPED_SLASHES)); return self::SUCCESS; }
        catch (\Throwable $e) { $this->error('Cinepoint gagal: '.$e->getMessage()); return self::FAILURE; }
    }
}
