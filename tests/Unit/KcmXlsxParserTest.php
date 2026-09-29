<?php

namespace Tests\Unit;

use App\Services\Reports\KcmXlsxParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class KcmXlsxParserTest extends TestCase
{
    /** @test */
    public function it_parses_sinemaku_layout_with_dynamic_shows_free_pass_and_promo_audit(): void
    {
        $path = $this->workbook([
            ['KCM PAMEKASAN'],
            ['City', 'PAMEKASAN'],
            ['Date', '27/09/2026'],
            [],
            ['Film', 'Format', 'Studio', 'Price', 'Show 1', null, null, 'Show 2', null, null, 'Show 3', null, null, 'Total Sold', 'Total Free', 'Total Promo', 'Total Sales'],
            [null, null, null, null, 'Sold', 'Free', 'Promo', 'Sold', 'Free', 'Promo', 'Sold', 'Free', 'Promo'],
            ['FILM KCM', '2D', 'Studio 1', '50000', 4, 1, 2, 3, 0, 0, 0, 2, 0, 7, 3, 2, 350000],
            ['Grand Total'],
        ]);

        $result = (new KcmXlsxParser())->parse($path);

        $this->assertSame('SINEMAKU', $result['layout']);
        $this->assertSame('KCM PAMEKASAN', $result['cinema_name']);
        $this->assertSame('PAMEKASAN', $result['city']);
        $this->assertSame('2026-09-27', $result['report_date']);
        $this->assertSame(['REGULAR', 'FREE PASS', 'REGULAR', 'FREE PASS'], array_column($result['rows'], 'ticket_name'));
        $this->assertSame(['1', '1', '2', '3'], array_column($result['rows'], 'show'));
        $this->assertSame([4.0, 1.0, 3.0, 2.0], array_column($result['rows'], 'jumlah'));
        $this->assertSame([null, null, null, null], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame(2.0, $result['source_totals']['promo']);
        $this->assertNotEmpty($result['blocking_warnings']);
        $this->assertSame(2.0, $result['row_audit'][0]['promo']);
        $this->assertSame(7.0, $result['source_totals']['sold']);
        $this->assertSame(3.0, $result['source_totals']['free']);
        $this->assertSame(350000.0, $result['source_totals']['gross']);
    }

    /** @test */
    public function it_uses_numeric_kota_as_studio_in_sinemaku_layout(): void
    {
        $path = $this->workbook([
            ['LAPORAN PENJUALAN TIKET SINEMAKU'],
            ['JEMBER', null, 'Nama Bioskop : KOTA CINEMA MALL JEMBER'],
            [null, null, null, 'Hari/Tanggal: MINGGU, 27 SEPTEMBER 2026'],
            ['KOTA', 'MOVIE', 'Fmt', 'Seat', 'HTM', 'Show 1', null, null, 'TOTAL', null, null, 'Jumlah Uang'],
            [null, null, null, null, null, 'Sold', 'Free', 'Promo', 'Sold', 'Free', 'Promo'],
            [2, 'MEMBURU PEMANGSA', '2D', 180, 27000, 8, 0, 0, 8, 0, 0, 216000],
        ]);

        $result = (new KcmXlsxParser())->parse($path);

        $this->assertSame('2', $result['rows'][0]['studio']);
    }

    /** @test */
    public function it_parses_external_layout_with_dynamic_shows_and_source_identity(): void
    {
        $path = $this->workbook([
            ['Cinema', 'KCM WISMA ASRI'],
            ['City', 'JEMBER'],
            ['Report Date', '2026-09-27'],
            [],
            ['Film', 'Format', 'Seat', 'Price', 'Show 1', null, 'Show 2', null, 'Show 3', null, 'Show 4', null, 'Show 5', null, 'Total SO', 'Total FP', 'Total Sales'],
            [null, null, null, null, 'SO', 'FP', 'SO', 'FP', 'SO', 'FP', 'SO', 'FP', 'SO', 'FP'],
            ['FILM LUAR', '2D', '2', 30000, 5, 1, 0, 2, 3, 0, 0, 0, 4, 1, 12, 4, 360000],
            ['TOTAL'],
        ]);

        $result = (new KcmXlsxParser())->parse($path);

        $this->assertSame('EXTERNAL', $result['layout']);
        $this->assertCount(6, $result['rows']);
        $this->assertSame(['REGULAR', 'FREE PASS', 'FREE PASS', 'REGULAR', 'REGULAR', 'FREE PASS'], array_column($result['rows'], 'ticket_name'));
        $this->assertSame([5.0, 1.0, 2.0, 3.0, 4.0, 1.0], array_column($result['rows'], 'jumlah'));
        $this->assertSame('Worksheet', $result['rows'][0]['source_sheet']);
        $this->assertSame(7, $result['rows'][0]['source_row']);
        $this->assertNotEmpty($result['rows'][0]['source_id']);
    }

    /** @test */
    public function it_rejects_unexplained_printed_total_mismatch(): void
    {
        $path = $this->workbook([
            ['Cinema', 'KCM TEST'], ['City', 'JAKARTA'], ['Date', '27/09/2026'], [],
            ['Film', 'Format', 'Seat', 'Price', 'Show 1', null, 'Total SO', 'Total FP', 'Total Sales'],
            [null, null, null, null, 'SO', 'FP'],
            ['FILM', '2D', '1', 50000, 3, 0, 4, 0, 150000],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total sold');
        (new KcmXlsxParser())->parse($path);
    }

    public function test_external_layout_preserves_valued_free_pass_when_source_gross_includes_it(): void
    {
        $path = $this->workbook([
            ['LAPORAN PENJUALAN FILM HARIAN EXTERNAL'],
            [], ['DISTRIBUTOR', '', 'Sinemaku Entertaiment'], ['JUDUL FILM', '', 'MEMBURU PEMANGSA'],
            ['NAMA BIOSKOP', '', 'Situbondo'], ['HTM'], [], [],
            ['Tanggal', 'ST', 'Judul Film', 'KP', 'Show 1', '', 'TOTAL', '', 'HTM', 'TOTAL'],
            ['', '', '', '', 'SO', 'FP', 'SO', 'FP'],
            ['2026-09-27', '3', 'MEMBURU PEMANGSA', '143', 50, 2, 50, 2, 'Rp 30.000', 'Rp 1.560.000'],
        ]);

        $result = (new KcmXlsxParser())->parse($path);
        $free = collect($result['rows'])->firstWhere('ticket_name', 'FREE PASS');

        $this->assertSame(30000.0, $free['harga']);
        $this->assertSame(60000.0, $free['net']);
        $this->assertSame(1560000.0, $result['source_totals']['gross']);
    }

    private function workbook(array $rows): string
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray($rows, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'kcm-parser-');
        (new Xlsx($book))->save($path);
        return $path;
    }
}
