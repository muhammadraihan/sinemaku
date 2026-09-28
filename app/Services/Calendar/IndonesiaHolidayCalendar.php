<?php

namespace App\Services\Calendar;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class IndonesiaHolidayCalendar
{
    public const GOOGLE_ICS_URL = 'https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics';

    public function fetch(int $year): array
    {
        $response = Http::timeout(20)->retry(2, 500)->get(self::GOOGLE_ICS_URL);
        if (!$response->successful()) {
            throw new RuntimeException('Kalender Indonesia tidak dapat diambil saat ini.');
        }

        return $this->parse($response->body(), $year);
    }

    public function parse(string $ics, int $year): array
    {
        $ics = preg_replace("/\r?\n[ \t]/", '', $ics);
        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $ics, $matches);
        $holidays = [];

        foreach ($matches[1] ?? [] as $event) {
            $date = $this->field($event, 'DTSTART(?:;VALUE=DATE)?');
            $name = $this->field($event, 'SUMMARY');
            $description = $this->field($event, 'DESCRIPTION');
            if (!$date || !$name || !str_contains(mb_strtolower($description), 'hari libur nasional')) {
                continue;
            }

            $date = CarbonImmutable::createFromFormat('!Ymd', substr($date, 0, 8));
            if (!$date || $date->year !== $year) {
                continue;
            }

            $holidays[$date->toDateString()] = [
                'holiday_date' => $date->toDateString(),
                'name' => $this->decode($name),
                'source' => 'Google Calendar Indonesia',
            ];
        }

        ksort($holidays);
        return array_values($holidays);
    }

    private function field(string $event, string $name): string
    {
        return preg_match('/^'.$name.':(.*)$/m', $event, $match) ? trim($match[1]) : '';
    }

    private function decode(string $value): string
    {
        return trim(str_replace(['\\,', '\\;', '\\n', '\\\\'], [',', ';', ' ', '\\'], $value));
    }
}
