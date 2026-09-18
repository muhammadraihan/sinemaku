<?php

namespace App\Console\Commands;

use App\Models\SeatmapSource;
use App\Models\SeatmapShowtime;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class CollectSeatmapShowtimesCommand extends Command
{
    protected $signature = 'seatmap:collect-showtimes {--provider=cinepolis} {--max-movies=5} {--dry-run : Do not write to database}';

    protected $description = 'Collect public cinema showtimes for audience estimate monitoring.';

    public function handle()
    {
        $provider = strtolower($this->option('provider'));
        if ($provider !== 'cinepolis') {
            $this->error('Only Cinepolis discovery is implemented. CGV/XXI adapters are next.');
            return 1;
        }

        $script = base_path('scripts/seatmap/cinepolis-discovery.js');
        if (!file_exists($script)) {
            $this->error('Discovery worker not found: ' . $script);
            return 1;
        }

        $process = new Process(['node', $script, '--max-movies=' . (int) $this->option('max-movies')], base_path(), null, null, 240);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error(trim($process->getErrorOutput() ?: $process->getOutput()));
            return 1;
        }

        $payload = json_decode($process->getOutput(), true);
        if (!is_array($payload)) {
            $this->error('Discovery worker returned invalid JSON.');
            return 1;
        }

        $showtimes = $payload['showtimes'] ?? [];
        $this->info(sprintf('Provider=%s status=%s films=%s showtimes=%s', $provider, $payload['status'] ?? 'unknown', $payload['films_scanned'] ?? 0, count($showtimes)));

        foreach (($payload['warnings'] ?? []) as $warning) {
            $this->warn($warning);
        }

        if ($this->option('dry-run')) {
            $this->line(json_encode(array_slice($showtimes, 0, 5), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $source = SeatmapSource::firstOrCreate(
            ['provider' => 'cinepolis', 'label' => 'Cinepolis Indonesia public booking'],
            ['base_url' => 'https://www.cinepolis.co.id', 'status' => 'active', 'notes' => 'Public read-only movie/showtime discovery via browser worker.']
        );

        $written = 0;
        DB::transaction(function () use ($showtimes, $source, &$written) {
            foreach ($showtimes as $row) {
                $showDate = $this->parseDate($row['show_date_label'] ?? null);
                if (!$showDate || empty($row['external_showtime_id']) || empty($row['film_name']) || empty($row['cinema_name']) || empty($row['show_time'])) {
                    continue;
                }

                SeatmapShowtime::updateOrCreate(
                    [
                        'source_uuid' => $source->uuid,
                        'external_showtime_id' => $row['external_showtime_id'],
                    ],
                    [
                        'film_name' => $row['film_name'],
                        'cinema_name' => $row['cinema_name'],
                        'city' => null,
                        'screen_name' => trim(($row['screen_name'] ?? '') . (!empty($row['ticket_price_label']) ? ' · ' . $row['ticket_price_label'] : '')),
                        'show_date' => $showDate,
                        'show_time' => $row['show_time'],
                        'booking_url' => $row['booking_url'] ?? null,
                        'status' => 'active',
                        'last_seen_at' => now(),
                    ]
                );
                $written++;
            }
        });

        $this->info('Written showtimes: ' . $written);
        return 0;
    }

    private function parseDate($label)
    {
        if (!$label) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim($label))->toDateString();
            } catch (\Throwable $e) {
                // try next format
            }
        }

        return null;
    }
}
