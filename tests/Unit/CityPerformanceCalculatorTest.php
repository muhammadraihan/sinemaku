<?php

namespace Tests\Unit;

use App\Services\CityPerformance\CityPerformanceCalculator;
use PHPUnit\Framework\TestCase;

class CityPerformanceCalculatorTest extends TestCase
{
    private CityPerformanceCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CityPerformanceCalculator();
    }

    public function test_weight_uses_only_explicit_time_and_format_factors(): void
    {
        $this->assertSame(1.5, $this->calculator->showtimeWeight('18:00', 'IMAX', ['time' => ['18:00' => 1.2], 'format' => ['IMAX' => 1.25]]));
        $this->assertSame(1.0, $this->calculator->showtimeWeight('18:00', 'IMAX', []));
    }

    public function test_coverage_gate_requires_city_and_row_thresholds(): void
    {
        $this->assertTrue($this->calculator->coverageGate(5, 5, 100, 90, 0.70, 0.85));
        $this->assertFalse($this->calculator->coverageGate(3, 5, 100, 90, 0.70, 0.85));
        $this->assertFalse($this->calculator->coverageGate(5, 5, 100, 80, 0.70, 0.85));
    }

    public function test_largest_remainder_reconciles_integer_allocation(): void
    {
        $this->assertSame(['jakarta' => 34, 'bandung' => 33, 'surabaya' => 33], $this->calculator->largestRemainderAllocation(100, ['jakarta' => 1, 'bandung' => 1, 'surabaya' => 1]));
    }

    public function test_lpi_labels_follow_the_documented_city_performance_bands(): void
    {
        $this->assertSame('very strong', $this->calculator->lpiLabel(121));
        $this->assertSame('strong', $this->calculator->lpiLabel(105));
        $this->assertSame('normal', $this->calculator->lpiLabel(85));
        $this->assertSame('low', $this->calculator->lpiLabel(70));
        $this->assertSame('very low', $this->calculator->lpiLabel(69.99));
    }

    public function test_confidence_is_transparent(): void
    {
        $this->assertSame('high', $this->calculator->confidence(0.92, 0.95));
        $this->assertSame('low', $this->calculator->confidence(0.4, 0.5));
    }
}
