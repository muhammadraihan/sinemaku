<?php

namespace App\Services\CityPerformance;

class ShowtimeIdentity
{
    public function make(
        string $sourceKey,
        string $cinemaKey,
        string $movieKey,
        string $date,
        string $time,
        ?string $format = null
    ): string {
        $dimensions = [$sourceKey, $cinemaKey, $movieKey, $date, $this->normalizeTime($time), $format ?: ''];
        $normalized = array_map(static function (string $value): string {
            return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
        }, $dimensions);

        return hash('sha256', implode('|', $normalized));
    }

    private function normalizeTime(string $time): string
    {
        return preg_match('/^\d{2}:\d{2}$/', trim($time)) ? trim($time).':00' : trim($time);
    }
}
