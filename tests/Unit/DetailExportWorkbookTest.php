<?php

namespace Tests\Unit;

use App\Services\Reports\DetailExportWorkbook;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PHPUnit\Framework\TestCase;

class DetailExportWorkbookTest extends TestCase
{
    /** @test */
    public function it_creates_all_ticket_sheet_and_filtered_bogof_freepass_sheets(): void
    {
        $rows = [
            $this->row('REGULAR', 10),
            $this->row('BOGOF', 2),
            $this->row('FREE PASS', 3),
            $this->row('FREEPASS', 4),
        ];

        $workbook = (new DetailExportWorkbook())->build($rows, $this->totals(19));

        $this->assertSame(['Semua Type Tiket', 'BOGOF', 'FREEPASS'], $workbook->getSheetNames());
        $this->assertSame(6, $workbook->getSheetByName('Semua Type Tiket')->getHighestRow());
        $this->assertSame(3, $workbook->getSheetByName('BOGOF')->getHighestRow());
        $this->assertSame(4, $workbook->getSheetByName('FREEPASS')->getHighestRow());
        $this->assertSame('Type Tiket', $workbook->getSheetByName('Semua Type Tiket')->getCell('G1')->getValue());
        $this->assertSame('BOGOF', $workbook->getSheetByName('BOGOF')->getCell('G2')->getValue());
        $this->assertSame('FREE PASS', $workbook->getSheetByName('FREEPASS')->getCell('G2')->getValue());
        $this->assertSame('FREEPASS', $workbook->getSheetByName('FREEPASS')->getCell('G3')->getValue());
    }

    private function row(string $type, int $total): object
    {
        return (object) [
            'tgl_tayang' => '2026-11-09', 'name' => 'XXI', 'kota' => 'JAKARTA',
            'nama_bioskop' => 'CINEMA', 'studio' => '1', 'kapasitas' => 100,
            'type_tiket' => $type, 'S1' => $total, 'S2' => 0, 'S3' => 0, 'S4' => 0,
            'S5' => 0, 'S6' => 0, 'S7' => 0, 'Total' => $total, 'seats_available' => '100.00',
            'occupancy_rate' => '10.00', 'harga' => '30000.00', 'gross' => '300000.00',
            'atp' => '30000.00', 'effective_tax_rate' => '10.00', 'pajak_persen' => '10',
            'pajak' => '30000.00', 'net' => '270000.00', 'share_ph' => '135000.00',
            'royalty' => '2025.00', 'total_akhir' => '132975.00',
        ];
    }

    private function totals(int $total): array
    {
        return ['S1' => $total, 'S2' => 0, 'S3' => 0, 'S4' => 0, 'S5' => 0, 'S6' => 0, 'S7' => 0,
            'Total' => $total, 'seats_available' => 400, 'gross' => 1200000, 'pajak' => 120000,
            'net' => 1080000, 'share_ph' => 540000, 'royalty' => 8100, 'total_akhir' => 531900];
    }
}
