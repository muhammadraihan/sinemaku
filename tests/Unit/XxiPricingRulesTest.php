<?php

namespace Tests\Unit;

use App\Services\Reports\XxiPricingRules;
use PHPUnit\Framework\TestCase;

class XxiPricingRulesTest extends TestCase
{
    /** @test */
    public function it_classifies_weekdays_friday_weekend_and_configured_holidays(): void
    {
        $rules = new XxiPricingRules(['2026-09-29']);

        $this->assertSame('weekday', $rules->dayGroup('2026-09-28'));
        $this->assertSame('friday', $rules->dayGroup('2026-09-25'));
        $this->assertSame('weekend_holiday', $rules->dayGroup('2026-09-26'));
        $this->assertSame('weekend_holiday', $rules->dayGroup('2026-09-29'));
    }

    /** @test */
    public function it_maps_all_seven_xxi_show_numbers_without_dropping_show_seven(): void
    {
        $rules = new XxiPricingRules();

        $this->assertSame([
            1 => '11:00', 2 => '13:00', 3 => '15:00', 4 => '17:00',
            5 => '19:00', 6 => '21:00', 7 => '23:00',
        ], $rules->showtimes());
        $this->assertSame('23:00', $rules->showtime(7));
    }
}
