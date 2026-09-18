<?php

namespace App\Services\Seatmap;

interface SeatmapSourceAdapter
{
    public function provider(): string;

    public function supportsReadOnlySeatmap(): bool;

    public function safetyNotes(): array;

    public function discoverShowtimes(array $filters = []): array;

    public function fetchSeatmapSnapshot(array $showtime): array;
}
