<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;

class XxiPricingRules
{
    private array $holidays;

    public function __construct(array $holidays = [])
    {
        $this->holidays = array_fill_keys(array_map(fn ($date) => CarbonImmutable::parse($date)->format('Y-m-d'), $holidays), true);
    }

    public function dayGroup($date): string
    {
        $date = CarbonImmutable::parse($date);
        $key = $date->format('Y-m-d');

        if (isset($this->holidays[$key]) || $date->isWeekend()) {
            return 'weekend_holiday';
        }

        return $date->isFriday() ? 'friday' : 'weekday';
    }

    public function showtimes(): array
    {
        return [
            1 => '11:00',
            2 => '13:00',
            3 => '15:00',
            4 => '17:00',
            5 => '19:00',
            6 => '21:00',
            7 => '23:00',
        ];
    }

    public function showtime(int $show): ?string
    {
        return $this->showtimes()[$show] ?? null;
    }
}
