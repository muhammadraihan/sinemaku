<?php

namespace App\Services\Reports;

use App\Models\CalendarHoliday;
use App\Models\CinemaTicketPrice;
use Carbon\CarbonImmutable;

class CinemaTicketPriceResolver
{
    public function resolve(string $masterBioskopUuid, string $typeTiketUuid, $reportDate): ?array
    {
        $date = CarbonImmutable::parse($reportDate);
        $price = CinemaTicketPrice::query()
            ->where('master_bioskop_uuid', $masterBioskopUuid)
            ->where('type_tiket_uuid', $typeTiketUuid)
            ->where('active', true)
            ->validOn($date->toDateString())
            ->orderByDesc('valid_from')
            ->first();

        if (!$price) {
            return null;
        }

        $holiday = CalendarHoliday::query()
            ->whereDate('holiday_date', $date->toDateString())
            ->where('active', true)
            ->exists();
        $dayGroup = ($holiday || $date->isWeekend())
            ? 'weekend_holiday'
            : ($date->isFriday() ? 'friday' : 'weekday');

        $field = $dayGroup.'_price';

        return [
            'price' => (string) $price->{$field},
            'day_group' => $dayGroup,
            'price_uuid' => $price->uuid,
            'master_bioskop_uuid' => $price->master_bioskop_uuid,
            'type_tiket_uuid' => $price->type_tiket_uuid,
            'valid_from' => $price->valid_from->toDateString(),
            'valid_until' => $price->valid_until ? $price->valid_until->toDateString() : null,
        ];
    }
}
