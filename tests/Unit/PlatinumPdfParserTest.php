<?php

namespace Tests\Unit;

use App\Services\Reports\PlatinumPdfParser;
use PHPUnit\Framework\TestCase;

class PlatinumPdfParserTest extends TestCase
{
    /** @test */
    public function it_parses_berau_gross_inclusive_net_profile(): void
    {
        $result = (new PlatinumPdfParser())->parseText(<<<'PDF'
Total Box Office
Movie Sessions Admits Gross Net Tax
Memburu Pemangsa 3 115 4,025,000.00 4,025,000.00 365,909.30
24-Sep-2026STUDIO 3 11:50 am 6STANDARD 35,000.00 210,000.00 210,000.00 19,090.92
24-Sep-2026STUDIO 3 04:05 pm 63STANDARD 35,000.00 2,205,000.00 2,205,000.00 200,454.66
24-Sep-2026STUDIO 3 08:20 pm 46STANDARD 35,000.00 1,610,000.00 1,610,000.00 146,363.72
Platinum Cineplex Berau
Distributor Report
Screening Period 24-09-2026 06:00 AM TO 25-09-2026 06:00 AM
PDF);

        $this->assertSame('PLATINUM CINEPLEX BERAU', $result['cinema_name']);
        $this->assertSame('MEMBURU PEMANGSA', $result['film_name']);
        $this->assertSame('2026-09-24', $result['report_date']);
        $this->assertSame('gross_inclusive_net', $result['financial_profile']);
        $this->assertCount(3, $result['rows']);
        $this->assertSame(['11:50', '16:05', '20:20'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame(115, $result['totals']['admits']);
        $this->assertSame(4025000.0, $result['totals']['gross']);
        $this->assertNotEmpty($result['warnings']);
    }

    /** @test */
    public function it_parses_lahat_and_ignores_an_empty_show_without_a_ticket_type(): void
    {
        $result = (new PlatinumPdfParser())->parseText(<<<'PDF'
Movie Sessions Admits Gross Net Tax
Memburu Pemangsa 2 24 960,000.00 872,727.26 87,272.64
Memburu Pemangsa 1 0 0.00 0.00 0.00
24-Sep-2026 STUDIO 4 03:25 pm 4 STANDARD 40,000.00 160,000.00 145,454.54 14,545.44
24-Sep-2026 STUDIO 4 07:45 pm 20 STANDARD 40,000.00 800,000.00 727,272.72 72,727.20
24-Sep-2026 STUDIO 3 01:00 pm 0 0.00 0.00 0.00 0.00
Platinum Cineplex Lahat
Screening Period 24-09-2026 06:00 AM TO 25-09-2026 06:00 AM
PDF);

        $this->assertSame('standard_tax_exclusive_net', $result['financial_profile']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame([4, 20], array_column($result['rows'], 'jumlah'));
        $this->assertSame(['4', '4'], array_column($result['rows'], 'studio'));
        $this->assertSame(960000.0, $result['totals']['gross']);
    }

    /** @test */
    public function it_parses_cibitung_with_decimal_rounding(): void
    {
        $result = (new PlatinumPdfParser())->parseText(<<<'PDF'
Movie Sessions Admits Gross Net Tax
Memburu Pemangsa 2 4 120,000.00 109,090.91 10,909.08
Memburu Pemangsa 1 0 0.00 0.00 0.00
24-Sep-2026 STUDIO 2 01:25 pm 2 STANDARD 30,000.00 60,000.00 54,545.45 5,454.54
24-Sep-2026 STUDIO 2 03:35 pm 2 STANDARD 30,000.00 60,000.00 54,545.45 5,454.54
24-Sep-2026 STUDIO 2 07:35 pm 0 0.00 0.00 0.00 0.00
Platinum Cineplex Cibitung
Screening Period 24-09-2026 06:00 AM TO 25-09-2026 06:00 AM
PDF);

        $this->assertCount(2, $result['rows']);
        $this->assertSame([1, 2], array_column($result['rows'], 'show'));
        $this->assertSame(109090.90, $result['totals']['net']);
        $this->assertSame(10909.08, $result['totals']['tax_amount']);
    }

    /** @test */
    public function it_rejects_price_times_admissions_mismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Harga × admits tidak sama dengan gross');

        (new PlatinumPdfParser())->parseText(<<<'PDF'
Movie Sessions Admits Gross Net Tax
Memburu Pemangsa 1 2 60,000.00 54,545.45 5,454.54
24-Sep-2026 STUDIO 2 01:25 pm 2 STANDARD 30,000.00 70,000.00 54,545.45 5,454.54
Platinum Cineplex Cibitung
Screening Period 24-09-2026 06:00 AM TO 25-09-2026 06:00 AM
PDF);
    }
}
