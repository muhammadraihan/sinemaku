<?php

namespace Tests\Unit;

use App\Services\Reports\CinepolisPdfParser;
use PHPUnit\Framework\TestCase;

class CinepolisPdfParserTest extends TestCase
{
    /** @test */
    public function it_extracts_cinepolis_distributor_pdf_rows_from_text_based_pdf()
    {
        $parser = new CinepolisPdfParser();

        $result = $parser->parse(__DIR__ . '/../Fixtures/cinepolis-vista-sample.pdf');

        $this->assertSame('MAXXBOX LIPPO VILLAGE', $result['cinema_name']);
        $this->assertSame('BOLEHKAH SEKALI SAJA KUMENANGIS', $result['film_name']);
        $this->assertSame('5', $result['studio']);
        $this->assertSame('2024-10-23', $result['report_date']);
        $this->assertSame(7, count($result['rows']));
        $this->assertSame(65, $result['totals']['admits']);
        $this->assertSame(1845000.0, $result['totals']['gross']);
        $this->assertSame(167727.21, $result['totals']['tax_amount']);
        $this->assertSame(1677272.79, $result['totals']['net']);
        $this->assertEqualsWithDelta(9.0909, $result['totals']['tax_rate'], 0.0001);
        $this->assertSame([
            'admits' => 65,
            'gross' => 1845000.0,
            'tax_amount' => 167727.21,
            'net' => 1677272.79,
        ], $result['source_totals']);

        $this->assertSame([
            'tanggal' => '2024-10-23',
            'jam_tayang' => '13:55',
            'show' => 1,
            'studio' => '5',
            'type_tiket' => 'REGULAR',
            'harga' => 30000.0,
            'jumlah' => 10,
            'gross' => 300000.0,
            'tax_amount' => 27272.70,
            'tax_rate' => 9.0909,
            'net' => 272727.30,
            'attribute' => '2D',
        ], $result['rows'][0]);
    }

    /** @test */
    public function distributor_identity_and_vista_footer_are_not_required()
    {
        $parser = new CinepolisPdfParser();
        $text = (new \Smalot\PdfParser\Parser())
            ->parseFile(__DIR__ . '/../Fixtures/cinepolis-vista-sample.pdf')
            ->getText();
        $text = str_replace('Detailed Distributors Report', '', $text);
        $text = preg_replace('/© Vista Entertainment Solutions.*$/s', '', $text);

        $result = $parser->parseText($text);

        $this->assertSame('MAXXBOX LIPPO VILLAGE', $result['cinema_name']);
        $this->assertSame(7, count($result['rows']));
    }

    /** @test */
    public function it_rejects_a_report_when_source_totals_do_not_match_detail_rows()
    {
        $parser = new CinepolisPdfParser();
        $text = (new \Smalot\PdfParser\Parser())
            ->parseFile(__DIR__ . '/../Fixtures/cinepolis-vista-sample.pdf')
            ->getText();
        $text = preg_replace(
            '/Day Total Paid\s+65\s+1,845,000\.00\s+167,727\.21\s+1,677,272\.79/',
            'Day Total Paid 66 1,845,000.00 167,727.21 1,677,272.79',
            $text
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total detail PDF tidak sama dengan Day Total sumber');

        $parser->parseText($text);
    }

    /** @test */
    public function it_extracts_multiple_film_screen_blocks_from_one_pdf()
    {
        $parser = new CinepolisPdfParser();
        $result = $parser->parse(__DIR__ . '/../Fixtures/cinepolis-jember-two-screens.pdf');

        $this->assertSame('LIPPO PLAZA JEMBER', $result['cinema_name']);
        $this->assertSame('PATAH HATI YANG KUPILIH', $result['film_name']);
        $this->assertSame(2, count($result['rows']));
        $this->assertSame(['4', '6'], array_column($result['rows'], 'studio'));
        $this->assertSame(['18:45', '13:05'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame(43, $result['totals']['admits']);
        $this->assertSame(1161000.0, $result['totals']['gross']);
        $this->assertSame(105545.65, $result['totals']['tax_amount']);
        $this->assertSame(1055454.35, $result['totals']['net']);
    }

    /** @test */
    public function it_extracts_all_ticket_types_within_each_show()
    {
        $parser = new CinepolisPdfParser();
        $result = $parser->parse(__DIR__ . '/../Fixtures/cinepolis-palembang-icon-multi-ticket.pdf');

        $this->assertSame(3, count($result['rows']));
        $this->assertSame(['REGULAR', 'REGULAR', 'COMPLIMENTRY VOUCHER'], array_column($result['rows'], 'type_tiket'));
        $this->assertSame(['12:10', '20:15', '20:15'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame([1, 1, 1], array_column($result['rows'], 'show'));
        $this->assertSame(['2', '3', '3'], array_column($result['rows'], 'studio'));
        $this->assertSame([14, 11, 1], array_column($result['rows'], 'jumlah'));
        $this->assertSame(26, $result['totals']['admits']);
        $this->assertSame(26, $result['source_totals']['admits']);
    }

    /** @test */
    public function it_parses_indonesian_money_and_ticket_continuation_rows(): void
    {
        $parser = new CinepolisPdfParser();
        $text = <<<'PDF'
LIVING PLAZA BALIKPAPAN
Detailed Distributors Report
From Thursday 24/09/2026 Until Friday 25/09/2026 Ticket Detail Level: Ticket Type
SINEMAKU
MEMBURU PEMANGSA CINEMA06
Admits Gross Tax NetTicket PriceTicket Type Attribute
24/09/2026
12:45 REGULAR 35.000,00 4 140.000,00 12.727,28 127.272,72 2D
15:10 REGULAR 35.000,00 3 105.000,00 9.545,46 95.454,54 2D
17:35 REGULAR 35.000,00 19 665.000,00 60.454,58 604.545,42 2D
REGULAR DIST FULL 35.000,00 1 35.000,00 3.181,82 31.818,18 2D
20:00 REGULAR 35.000,00 28 980.000,00 89.090,96 890.909,04 2D
REGULAR DIST FULL 35.000,00 1 35.000,00 3.181,82 31.818,18 2D
Day Total Paid 56 1.960.000,00 178.181,92 1.781.818,08
0,00 0,00 0,00 0 Day Total Complementory
PDF;

        $result = $parser->parseText($text);

        $this->assertSame('LIVING PLAZA BALIKPAPAN', $result['cinema_name']);
        $this->assertSame(6, count($result['rows']));
        $this->assertSame(['REGULAR', 'REGULAR', 'REGULAR', 'REGULAR DIST FULL', 'REGULAR', 'REGULAR DIST FULL'], array_column($result['rows'], 'type_tiket'));
        $this->assertSame(['12:45', '15:10', '17:35', '17:35', '20:00', '20:00'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame(56, $result['totals']['admits']);
        $this->assertSame(1960000.0, $result['totals']['gross']);
        $this->assertSame(178181.92, $result['totals']['tax_amount']);
        $this->assertSame(1781818.08, $result['totals']['net']);
    }

    /** @test */
    public function it_parses_ticket_class_layout_with_trailing_ticket_name_and_complimentary_amounts(): void
    {
        $parser = new CinepolisPdfParser();
        $text = <<<'PDF'
LM KUTA
Detailed Distributors Report
From Thursday 24/09/2026 06:00 Until Friday 25/09/2026 06:00 Ticket Detail Level: Ticket Class
SINEMAKU
MEMBURU PEMANGSA CINEMA01
Admits Gross Tax NetTicket PriceTicket Class Attribute
24/09/2026
12:45 25.000,00 6 150.000,00 13.636,38 136.363,622DREGULAR
136.363,6213.636,38150.000,006
15:10 25.000,00 4 100.000,00 9.090,92 90.909,082DREGULAR
90.909,089.090,92100.000,004
17:35 25.000,00 26 650.000,00 59.090,98 590.909,022DREGULAR
590.909,0259.090,98650.000,0026
20:00 0.00 1 25.000,00 2.272,73 22.727,272DREGULAR
25.000,00 57 1.425.000,00 129.545,61 1.295.454,392DREGULAR
1.318.181,66131.818,341.450.000,0058
Day Total Paid 93 2.350.000,00 211.363,89 2.113.636,11
22,727.27 2,272.73 25,000.00 1Day Total Complementory
213.636,6294 2.136.363,382.350.000,00Total for Film this Screen
PDF;

        $result = $parser->parseText($text);

        $this->assertSame('LM KUTA', $result['cinema_name']);
        $this->assertSame(5, count($result['rows']));
        $this->assertSame(['12:45', '15:10', '17:35', '20:00', '20:00'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame(['REGULAR', 'REGULAR', 'REGULAR', 'REGULAR', 'REGULAR'], array_column($result['rows'], 'type_tiket'));
        $this->assertSame([6, 4, 26, 1, 57], array_column($result['rows'], 'jumlah'));
        $this->assertSame(94, $result['totals']['admits']);
        $this->assertSame(2350000.0, $result['totals']['gross']);
        $this->assertSame(213636.62, $result['totals']['tax_amount']);
        $this->assertSame(2136363.38, $result['totals']['net']);
        $this->assertSame($result['totals'], array_merge($result['source_totals'], ['tax_rate' => $result['totals']['tax_rate']]));
    }

    /** @test */
    public function it_parses_dot_thousands_without_a_decimal_fraction(): void
    {
        $parser = new CinepolisPdfParser();
        $text = <<<'PDF'
CINEPOLIS SENAYAN PARK
Detailed Distributors Report
From Wednesday 24/09/2026 Until Thursday 25/09/2026 Ticket Detail Level: Ticket Class
MEMBURU PEMANGSA CINEMA01
Admits Gross Tax NetTicket PriceTicket Class Attribute
24/09/2026
13:10 REGULAR 45.000 7 315.000,00 28.636 286.3642D
Day Total Paid 7 315.000 28.636 286.364
0.00 0.00 0.00 0Day Total Complementory
28.6367 286.364315.000Total for Film this Screen
PDF;

        $result = $parser->parseText($text);

        $this->assertSame(1, count($result['rows']));
        $this->assertSame(45000.0, $result['rows'][0]['harga']);
        $this->assertSame(315000.0, $result['rows'][0]['gross']);
        $this->assertSame(28636.0, $result['rows'][0]['tax_amount']);
        $this->assertSame(286364.0, $result['rows'][0]['net']);
        $this->assertSame(315000.0, $result['totals']['gross']);
    }

    /** @test */
    public function it_accepts_one_rupiah_tax_and_net_rounding_difference_against_source_total(): void
    {
        $parser = new CinepolisPdfParser();
        $text = <<<'PDF'
CINEPOLIS SENAYAN PARK
Detailed Distributors Report
From Wednesday 24/09/2026 Until Thursday 25/09/2026 Ticket Detail Level: Ticket Type
MEMBURU PEMANGSA CINEMA06
Admits Gross Tax NetTicket PriceTicket Type Attribute
24/09/2026
13:10 REGULAR 45.000 7 315.000,00 28.636 286.364 2D
15:30 REGULAR 45.000 17 765.000,00 69.545 695.455 2D
Day Total Paid 24 1.080.000 98.182 981.818
0.00 0.00 0.00 0 Day Total Complementory
PDF;

        $result = $parser->parseText($text);

        $this->assertSame(24, $result['totals']['admits']);
        $this->assertSame(1080000.0, $result['totals']['gross']);
        $this->assertSame(98181.0, $result['totals']['tax_amount']);
        $this->assertSame(981819.0, $result['totals']['net']);
        $this->assertSame(98182.0, $result['source_totals']['tax_amount']);
        $this->assertSame(981818.0, $result['source_totals']['net']);
    }

    /** @test */
    public function it_rejects_pdf_without_parseable_cinema_name()
    {
        $parser = new CinepolisPdfParser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tanggal laporan tidak dapat dibaca');

        $parser->parseText("BOLEHKAH SEKALI SAJA KUMENANGIS\nTicket Type Attribute Ticket Price Admits Gross Tax Net");
    }
}
