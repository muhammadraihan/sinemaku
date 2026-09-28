<?php

namespace Tests\Unit;

use App\Services\Calendar\IndonesiaHolidayCalendar;
use Tests\TestCase;

class IndonesiaHolidayCalendarTest extends TestCase
{
    public function test_it_parses_national_holidays_for_requested_year_only(): void
    {
        $ics = <<<'ICS'
BEGIN:VCALENDAR
BEGIN:VEVENT
DTSTART;VALUE=DATE:20260101
DESCRIPTION:Hari libur nasional
SUMMARY:Hari Tahun Baru
END:VEVENT
BEGIN:VEVENT
DTSTART;VALUE=DATE:20261231
DESCRIPTION:Perayaan
SUMMARY:Malam Tahun Baru
END:VEVENT
BEGIN:VEVENT
DTSTART;VALUE=DATE:20270101
DESCRIPTION:Hari libur nasional
SUMMARY:Hari Tahun Baru 2027
END:VEVENT
END:VCALENDAR
ICS;

        $items = (new IndonesiaHolidayCalendar())->parse($ics, 2026);

        $this->assertSame([
            [
                'holiday_date' => '2026-01-01',
                'name' => 'Hari Tahun Baru',
                'source' => 'Google Calendar Indonesia',
            ],
        ], $items);
    }
}
