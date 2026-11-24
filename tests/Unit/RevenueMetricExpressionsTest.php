<?php

namespace Tests\Unit;

use App\Services\Reports\RevenueMetricExpressions;
use PHPUnit\Framework\TestCase;

class RevenueMetricExpressionsTest extends TestCase
{
    /** @test */
    public function it_defines_gross_and_net_atp_from_the_same_audience_denominator(): void
    {
        $metrics = new RevenueMetricExpressions('SUM(audience)', 'SUM(gross)', 'SUM(net)');

        $this->assertSame('CASE WHEN SUM(audience) > 0 THEN SUM(gross) / SUM(audience) ELSE 0 END', $metrics->atpGross());
        $this->assertSame('CASE WHEN SUM(audience) > 0 THEN SUM(net) / SUM(audience) ELSE 0 END', $metrics->atpNet());
    }
}
