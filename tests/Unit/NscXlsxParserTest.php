<?php

namespace Tests\Unit;

use App\Services\Reports\NscXlsxParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class NscXlsxParserTest extends TestCase
{
    /** @test */
    public function it_parses_paid_and_bogof_rows_across_multiple_sheets_and_date_formats(): void
    {
        $path = $this->workbook([
            '24 SEP' => $this->sheetRows('NSC Rangkasbitung', '24-Sep-26', [
                ['2', '2D', 'Regular', ' Rp 35,000 ', '09:50', 0, null],
            ], [0, 0, 0]),
            '25 SEP' => $this->sheetRows('NSC Rangkasbitung', '25-Sep-26', [
                ['2', '2D', 'Regular', ' Rp 35,000 ', '09:50', 0, null, '15:55', 12, 6],
            ], [12, 6, 420000]),
        ]);

        $result = (new NscXlsxParser())->parse($path);

        $this->assertSame('NSC RANGKASBITUNG', $result['cinema_name']);
        $this->assertSame('MEMBURU PEMANGSA', $result['film_name']);
        $this->assertSame(['REGULAR', 'BOGOF'], array_column($result['rows'], 'ticket_name'));
        $this->assertSame(['15:55', '15:55'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame([12.0, 6.0], array_column($result['rows'], 'jumlah'));
        $this->assertSame([35000.0, 0.0], array_column($result['rows'], 'harga'));
        $this->assertSame(18.0, $result['totals']['admits']);
        $this->assertSame(420000.0, $result['totals']['gross']);
    }

    /** @test */
    public function it_normalizes_site_placeholders_semicolon_time_and_single_show_paid_total_mismatch(): void
    {
        $path = $this->workbook([
            'Format' => $this->sheetRows('NSC ___JOMBANG____', '25-Sep-2026', [
                ['1', '2D', 'Regular', ' Rp 30,000 ', null, null, null, null, null, null, '15;20', 10, 5],
            ], [5, 5, 150000]),
        ]);

        $result = (new NscXlsxParser())->parse($path);

        $this->assertSame('NSC JOMBANG', $result['cinema_name']);
        $this->assertSame(['15:20', '15:20'], array_column($result['rows'], 'jam_tayang'));
        $this->assertSame([5.0, 5.0], array_column($result['rows'], 'jumlah'));
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('dikoreksi dari 10 menjadi 5', $result['warnings'][0]);
    }

    /** @test */
    public function it_requires_an_operator_to_allocate_total_free_when_the_show_is_blank(): void
    {
        $path = $this->workbook([
            'Format' => $this->sheetRows('NSC DIENG WONOSOBO', '24-Sep-26', [
                ['1', '2D', 'Regular', ' Rp 30,000 ', null, null, null, null, null, null, '14:00', 10, null],
            ], [10, 5, 300000]),
        ]);

        $result = (new NscXlsxParser())->parse($path);

        $this->assertSame(['REGULAR'], array_column($result['rows'], 'ticket_name'));
        $this->assertSame(5.0, $result['pending_free_assignments'][0]['jumlah']);
        $this->assertSame('14:00', $result['pending_free_assignments'][0]['candidate_shows'][0]['jam_tayang']);
        $this->assertSame(3, $result['pending_free_assignments'][0]['candidate_shows'][0]['show']);
    }

    /** @test */
    public function it_rejects_sales_that_do_not_reconcile_with_paid_total(): void
    {
        $path = $this->workbook([
            'Format' => $this->sheetRows('NSC TEST', '9/25/2026', [
                ['1', '2D', 'Regular', ' Rp 25,000 ', '13:25', 4, 0],
            ], [4, 0, 90000]),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Total Sales');
        (new NscXlsxParser())->parse($path);
    }

    private function sheetRows(string $site, string $date, array $dataRows, array $grandTotal): array
    {
        $rows = [
            [null, 'TICKET SALES REPORT'],
            ['Site :', $site],
            ['Address :', 'Alamat'],
            [],
            ['Distributor :', 'SINEMAKU PICTURES'],
            ['Movie Title :', 'MEMBURU PEMANGSA'],
            ['Show Date :', $date],
            [],
            ['Cinema', 'Movie Format', 'Seat Grade', 'Price', '1st Showtime', null, null, '2nd Showtime', null, null, '3rd Showtime', null, null, '4th Showtime', null, null, '5th Showtime', null, null, '6th Showtime', null, null, '7th Showtime', null, null, 'Total', null, 'Total Sales'],
            [null, null, null, null, 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Time', 'Paid', 'Free', 'Paid', 'Free'],
        ];
        foreach ($dataRows as $row) {
            $row = array_pad($row, 28, null);
            $paid = 0;
            $free = 0;
            for ($column = 4; $column <= 22; $column += 3) {
                $paid += (float) ($row[$column + 1] ?? 0);
                $free += (float) ($row[$column + 2] ?? 0);
            }
            $row[25] = $grandTotal[0];
            $row[26] = $grandTotal[1];
            $row[27] = $grandTotal[2];
            $rows[] = $row;
        }
        $grand = array_fill(0, 28, null);
        $grand[0] = 'Grand Total';
        $grand[25] = $grandTotal[0];
        $grand[26] = $grandTotal[1];
        $grand[27] = $grandTotal[2];
        $rows[] = $grand;
        return $rows;
    }

    private function workbook(array $sheets): string
    {
        $book = new Spreadsheet();
        $first = true;
        foreach ($sheets as $title => $rows) {
            $sheet = $first ? $book->getActiveSheet() : $book->createSheet();
            $first = false;
            $sheet->setTitle($title);
            $sheet->fromArray($rows, null, 'A1');
        }
        $path = tempnam(sys_get_temp_dir(), 'nsc-parser-');
        (new Xlsx($book))->save($path);
        return $path;
    }
}
