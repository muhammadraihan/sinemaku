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
        $this->assertSame(['04', '06'], array_column($result['rows'], 'studio'));
        $this->assertSame(['18:45', '13:05'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame(43, $result['totals']['admits']);
        $this->assertSame(1161000.0, $result['totals']['gross']);
        $this->assertSame(105545.65, $result['totals']['tax_amount']);
        $this->assertSame(1055454.35, $result['totals']['net']);
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
