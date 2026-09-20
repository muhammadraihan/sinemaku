<?php

namespace App\Services\CityPerformance;

class CityPerformanceCalculator
{
    public function showtimeWeight(string $time, ?string $format, array $factors): float
    {
        $timeFactor = (float) ($factors['time'][$time] ?? 1.0);
        $formatFactor = (float) ($factors['format'][$format] ?? 1.0);

        return $timeFactor * $formatFactor;
    }

    public function coverageGate(
        int $collectedCities,
        int $requestedCities,
        int $observedRows,
        int $validRows,
        float $minimumCityCoverage = 0.70,
        float $minimumValidRows = 0.85
    ): bool {
        if ($requestedCities === 0 || $observedRows === 0) {
            return false;
        }

        return ($collectedCities / $requestedCities) >= $minimumCityCoverage
            && ($validRows / $observedRows) >= $minimumValidRows;
    }

    public function largestRemainderAllocation(int $total, array $weights): array
    {
        $weightTotal = array_sum($weights);
        if ($total < 0 || $weightTotal <= 0) {
            return array_fill_keys(array_keys($weights), 0);
        }

        $allocation = [];
        $remainders = [];
        foreach ($weights as $key => $weight) {
            $exact = $total * ((float) $weight / $weightTotal);
            $allocation[$key] = (int) floor($exact);
            $remainders[$key] = $exact - $allocation[$key];
        }

        $remaining = $total - array_sum($allocation);
        $order = array_keys($weights);
        usort($order, static function ($left, $right) use ($remainders): int {
            return $remainders[$right] <=> $remainders[$left];
        });
        for ($index = 0; $index < $remaining; $index++) {
            $allocation[$order[$index % count($order)]]++;
        }

        return $allocation;
    }

    public function lpiLabel(float $lpi): string
    {
        if ($lpi > 120) return 'very strong';
        if ($lpi >= 105) return 'strong';
        if ($lpi >= 85) return 'normal';
        if ($lpi >= 70) return 'low';

        return 'very low';
    }

    public function confidence(float $cityCoverage, float $validRowRatio): string
    {
        $score = min($cityCoverage, $validRowRatio);
        if ($score >= 0.85) return 'high';
        if ($score >= 0.70) return 'medium';

        return 'low';
    }
}
