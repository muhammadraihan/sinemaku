<?php

namespace App\Services\Seatmap\Adapters;

use App\Services\Seatmap\SeatmapSourceAdapter;

class UnsupportedSeatmapAdapter implements SeatmapSourceAdapter
{
    private $provider;

    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function supportsReadOnlySeatmap(): bool
    {
        return false;
    }

    public function safetyNotes(): array
    {
        return [
            'Adapter belum diverifikasi untuk provider ini.',
            'Collector tidak boleh login, booking, hold seat, checkout, atau bypass CAPTCHA sampai flow read-only diverifikasi.',
        ];
    }

    public function discoverShowtimes(array $filters = []): array
    {
        return [];
    }

    public function fetchSeatmapSnapshot(array $showtime): array
    {
        throw new \RuntimeException('Seatmap adapter belum tersedia untuk provider ' . $this->provider);
    }
}
