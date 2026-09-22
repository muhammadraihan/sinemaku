<?php

namespace App\Services\Seatmap;

use App\Services\Seatmap\Adapters\UnsupportedSeatmapAdapter;

class SeatmapAdapterRegistry
{
    public function forProvider(string $provider): SeatmapSourceAdapter
    {
        return new UnsupportedSeatmapAdapter($provider);
    }

    public function supportedProviders(): array
    {
        return [
            'cinepolis' => 'Cinépolis Indonesia',
            'cgv' => 'CGV Indonesia',
            'xxi' => 'Cinema XXI / m.tix',
            'other' => 'Jaringan lain',
        ];
    }
}
