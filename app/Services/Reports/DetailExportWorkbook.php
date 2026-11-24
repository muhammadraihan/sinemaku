<?php

namespace App\Services\Reports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DetailExportWorkbook
{
    private const TOTAL_KEYS = [
        'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'Total', 'seats_available',
        'gross', 'pajak', 'net', 'share_ph', 'royalty', 'total_akhir',
    ];

    public function build(array $rows, array $allTotals): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $allSheet = $spreadsheet->getActiveSheet();
        $this->writeSheet($allSheet, 'Semua Type Tiket', $rows, $allTotals);

        $bogofRows = $this->filterTicketRows($rows, 'BOGOF');
        $bogofSheet = $spreadsheet->createSheet();
        $this->writeSheet($bogofSheet, 'BOGOF', $bogofRows, $this->totalsFor($bogofRows));

        $freepassRows = $this->filterTicketRows($rows, 'FREEPASS');
        $freepassSheet = $spreadsheet->createSheet();
        $this->writeSheet($freepassSheet, 'FREEPASS', $freepassRows, $this->totalsFor($freepassRows));

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function filterTicketRows(array $rows, string $ticketType): array
    {
        return array_values(array_filter($rows, function ($row) use ($ticketType) {
            return $this->normalizeTicketType($row->type_tiket ?? '') === $ticketType;
        }));
    }

    private function normalizeTicketType(string $ticketType): string
    {
        return str_replace([' ', '-'], '', strtoupper(trim($ticketType)));
    }

    private function totalsFor(array $rows): array
    {
        $totals = array_fill_keys(self::TOTAL_KEYS, 0.0);

        foreach ($rows as $row) {
            foreach (self::TOTAL_KEYS as $key) {
                $totals[$key] += $this->toNumber($row->{$key} ?? 0);
            }
        }

        return $totals;
    }

    private function writeSheet(Worksheet $sheet, string $title, array $rows, array $totals): void
    {
        $sheet->setTitle($title);
        $headers = [
            'Tanggal', 'Kategori', 'Kota', 'Nama Bioskop', 'Studio', 'Kapasitas', 'Type Tiket',
            'S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'Total', 'Kapasitas Tersedia',
            'Occupancy Rate', 'Harga', 'Gross', 'ATP', 'Effective Tax Rate', 'Pajak %', 'Pajak',
            'Net', 'Share', 'Share Production House', 'Royalty (1.5%)', 'Total Akhir',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:AB1')->getFont()->setBold(true);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:AB1');

        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet->fromArray([[
                $row->tgl_tayang,
                $row->name,
                $row->kota,
                strtoupper($row->nama_bioskop),
                $row->studio,
                $row->kapasitas,
                $row->type_tiket,
                $row->S1,
                $row->S2,
                $row->S3,
                $row->S4,
                $row->S5,
                $row->S6,
                $row->S7,
                $row->Total,
                $row->seats_available,
                $row->occupancy_rate . '%',
                $row->harga,
                $row->gross,
                $row->atp,
                $row->effective_tax_rate . '%',
                $row->pajak_persen . '%',
                $row->pajak,
                $row->net,
                '50%',
                $row->share_ph,
                $row->royalty,
                $row->total_akhir,
            ]], null, 'A' . $rowNum);
            $rowNum++;
        }

        $sheet->setCellValue('A' . $rowNum, 'TOTAL');
        $sheet->mergeCells('A' . $rowNum . ':G' . $rowNum);
        $sheet->fromArray([[
            $this->displayTotal($totals, 'S1'), $this->displayTotal($totals, 'S2'),
            $this->displayTotal($totals, 'S3'), $this->displayTotal($totals, 'S4'),
            $this->displayTotal($totals, 'S5'), $this->displayTotal($totals, 'S6'),
            $this->displayTotal($totals, 'S7'), $this->displayTotal($totals, 'Total'),
            number_format($this->toNumber($totals['seats_available'] ?? 0), 2),
            $this->toNumber($totals['seats_available'] ?? 0) > 0
                ? number_format(($this->toNumber($totals['Total'] ?? 0) / $this->toNumber($totals['seats_available'])) * 100, 2) . '%'
                : '0.00%',
            '', number_format($this->toNumber($totals['gross'] ?? 0), 2),
            $this->toNumber($totals['Total'] ?? 0) > 0
                ? number_format($this->toNumber($totals['gross'] ?? 0) / $this->toNumber($totals['Total']), 2)
                : '0.00',
            $this->toNumber($totals['gross'] ?? 0) > 0
                ? number_format(($this->toNumber($totals['pajak'] ?? 0) / $this->toNumber($totals['gross'])) * 100, 2) . '%'
                : '0.00%',
            '', number_format($this->toNumber($totals['pajak'] ?? 0), 2),
            number_format($this->toNumber($totals['net'] ?? 0), 2), '50%',
            number_format($this->toNumber($totals['share_ph'] ?? 0), 2),
            number_format($this->toNumber($totals['royalty'] ?? 0), 2),
            number_format($this->toNumber($totals['total_akhir'] ?? 0), 2),
        ]], null, 'H' . $rowNum);
        $sheet->getStyle('A' . $rowNum . ':AB' . $rowNum)->getFont()->setBold(true);

        for ($columnIndex = 1; $columnIndex <= 28; $columnIndex++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
    }

    private function displayTotal(array $totals, string $key): float
    {
        return $this->toNumber($totals[$key] ?? 0);
    }

    private function toNumber($value): float
    {
        return (float) str_replace(',', '', (string) ($value ?? 0));
    }
}
