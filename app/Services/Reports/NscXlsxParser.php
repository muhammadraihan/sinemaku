<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class NscXlsxParser
{
    public function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('File Excel NSC tidak dapat dibaca.');
        }

        try {
            $book = IOFactory::load($path);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Isi Excel NSC tidak dapat dibaca.', 0, $exception);
        }

        $rows = [];
        $warnings = [];
        $cinemaName = null;
        $filmName = null;
        $dates = [];
        $pendingFreeAssignments = [];

        foreach ($book->getWorksheetIterator() as $sheet) {
            $parsed = $this->parseSheet($sheet->toArray(null, true, true, false), $sheet->getTitle());
            if (!$parsed) {
                continue;
            }
            $cinemaName ??= $parsed['cinema_name'];
            $filmName ??= $parsed['film_name'];
            if ($this->normalize($cinemaName) !== $this->normalize($parsed['cinema_name'])) {
                throw new \InvalidArgumentException('Nama bioskop NSC berbeda antar sheet.');
            }
            if ($this->normalize($filmName) !== $this->normalize($parsed['film_name'])) {
                throw new \InvalidArgumentException('Nama film berbeda antar sheet NSC.');
            }
            $dates[] = $parsed['report_date'];
            $rows = array_merge($rows, $parsed['rows']);
            $pendingFreeAssignments = array_merge($pendingFreeAssignments, $parsed['pending_free_assignments']);
            $warnings = array_merge($warnings, $parsed['warnings']);
        }

        if (!$rows) {
            throw new \InvalidArgumentException('Tidak ada detail penjualan NSC yang dapat diparse dari workbook.');
        }

        $pendingFree = array_sum(array_column($pendingFreeAssignments, 'jumlah'));
        $totals = [
            'paid' => array_sum(array_map(fn ($row) => $row['ticket_name'] === 'REGULAR' ? $row['jumlah'] : 0, $rows)),
            'free' => array_sum(array_map(fn ($row) => $row['ticket_name'] === 'BOGOF' ? $row['jumlah'] : 0, $rows)) + $pendingFree,
            'admits' => array_sum(array_column($rows, 'jumlah')) + $pendingFree,
            'gross' => round(array_sum(array_map(fn ($row) => $row['harga'] * $row['jumlah'], $rows)), 2),
        ];

        return [
            'cinema_name' => $cinemaName,
            'film_name' => $filmName,
            'report_date' => count(array_unique($dates)) === 1 ? $dates[0] : null,
            'rows' => $rows,
            'totals' => $totals,
            'pending_free_assignments' => $pendingFreeAssignments,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function parseSheet(array $source, string $sheetName): ?array
    {
        $site = $this->findLabelValue($source, 'Site');
        $film = $this->findLabelValue($source, 'Movie Title');
        $date = $this->findLabelValue($source, 'Show Date');
        $headerIndex = $this->findRow($source, fn ($row) => $this->normalize($row[0] ?? '') === 'CINEMA');
        if ($headerIndex === null || !$site || !$film || !$date) {
            return null;
        }

        $cinema = $this->cleanName($site);
        $film = $this->cleanName($film);
        $reportDate = $this->parseDate($date);
        $columnGroups = [];
        foreach ($source[$headerIndex] as $column => $value) {
            if (preg_match('/^\d+(?:ST|ND|RD|TH)\s+SHOWTIME$/i', trim((string) $value))) {
                $columnGroups[] = (int) $column;
            }
        }
        if (!$columnGroups) {
            $columnGroups = range(4, 22, 3);
        }

        $rows = [];
        $warnings = [];
        $pendingFreeAssignments = [];
        for ($index = $headerIndex + 2; $index < count($source); $index++) {
            $line = $source[$index];
            $first = $this->normalize($line[0] ?? '');
            if ($first === 'GRAND TOTAL' || str_starts_with($first, 'NOTE')) {
                break;
            }
            if (!$this->hasContent($line) || $this->isPlaceholderRow($line)) {
                continue;
            }

            $studio = trim((string) ($line[0] ?? ''));
            $price = $this->money($line[3] ?? null);
            if ($studio === '' || $price === null) {
                continue;
            }
            $active = [];
            foreach ($columnGroups as $show => $timeColumn) {
                $time = $this->normalizeTime($line[$timeColumn] ?? null);
                $paid = $this->number($line[$timeColumn + 1] ?? null);
                $free = $this->number($line[$timeColumn + 2] ?? null);
                if ($time === null && $paid === 0.0 && $free === 0.0) {
                    continue;
                }
                if ($time === null) {
                    throw new \InvalidArgumentException('Jam tayang tidak dapat dibaca pada sheet '.$sheetName.' baris '.($index + 1).'.');
                }
                $active[] = ['time' => $time, 'paid' => $paid, 'free' => $free, 'show' => $show + 1];
            }
            if (!$active) {
                continue;
            }

            $rowPaid = $this->number($line[25] ?? null);
            $rowFree = $this->number($line[26] ?? null);
            $rowSales = $this->money($line[27] ?? null) ?? 0.0;
            $detailPaid = array_sum(array_column($active, 'paid'));
            $detailFree = array_sum(array_column($active, 'free'));
            if (abs($rowSales - ($rowPaid * $price)) > 0.01 || abs($rowSales - array_sum(array_map(fn ($item) => $item['paid'] * $price, $active))) > 0.01) {
                if (count($active) === 1 && abs($rowSales - ($rowPaid * $price)) <= 0.01) {
                    $old = $active[0]['paid'];
                    $active[0]['paid'] = $rowPaid;
                    $detailPaid = $rowPaid;
                    $warnings[] = 'Sheet '.$sheetName.' baris '.($index + 1).': Paid dikoreksi dari '.$this->displayNumber($old).' menjadi '.$this->displayNumber($rowPaid).' berdasarkan Total Sales.';
                } else {
                    throw new \InvalidArgumentException('Total Sales tidak sama dengan detail show pada sheet '.$sheetName.' baris '.($index + 1).'.');
                }
            }
            if (abs($rowPaid - $detailPaid) > 0.01) {
                throw new \InvalidArgumentException('Total tiket Paid tidak sama dengan detail show pada sheet '.$sheetName.' baris '.($index + 1).'.');
            }
            if (abs($rowFree - $detailFree) > 0.01) {
                $unallocatedFree = $rowFree - $detailFree;
                if ($unallocatedFree < 0 || !$active) {
                    throw new \InvalidArgumentException('Total tiket Free tidak sama dengan detail show pada sheet '.$sheetName.' baris '.($index + 1).'.');
                }
                $pendingFreeAssignments[] = [
                    'key' => hash('sha256', $sheetName.'|'.($index + 1).'|'.$studio.'|'.$reportDate),
                    'source_row' => $index + 1,
                    'source_sheet' => $sheetName,
                    'studio' => $studio,
                    'jumlah' => $unallocatedFree,
                    'candidate_shows' => array_map(fn ($item) => [
                        'show' => $item['show'],
                        'jam_tayang' => $item['time'],
                    ], $active),
                ];
                $warnings[] = 'Sheet '.$sheetName.' baris '.($index + 1).': '.$this->displayNumber($unallocatedFree).' tiket Free belum memiliki show dan harus ditentukan pada preview.';
            }

            foreach ($active as $item) {
                if ($item['paid'] > 0) {
                    $rows[] = $this->normalizedRow($index + 1, $reportDate, $film, $cinema, $studio, 'REGULAR', $item['time'], $item['show'], $item['paid'], $price, $sheetName);
                }
                if ($item['free'] > 0) {
                    $rows[] = $this->normalizedRow($index + 1, $reportDate, $film, $cinema, $studio, 'BOGOF', $item['time'], $item['show'], $item['free'], 0.0, $sheetName);
                }
            }
        }

        return ['cinema_name' => $cinema, 'film_name' => $film, 'report_date' => $reportDate, 'rows' => $rows, 'pending_free_assignments' => $pendingFreeAssignments, 'warnings' => $warnings];
    }

    private function normalizedRow(int $sourceRow, string $date, string $film, string $cinema, string $studio, string $ticket, string $time, int $show, float $count, float $price, string $sheet): array
    {
        return ['source_row' => $sourceRow, 'source_sheet' => $sheet, 'tgl_tayang' => $date, 'nama_film' => $film, 'source_cinema' => $cinema, 'source_city' => '', 'studio' => $studio, 'ticket_name' => $ticket, 'jam_tayang' => $time, 'show' => (string) $show, 'jumlah' => $count, 'harga' => $price, 'tax' => 0, 'net' => $price * $count];
    }

    private function findLabelValue(array $rows, string $label): ?string
    {
        foreach ($rows as $row) {
            $rowLabel = trim(rtrim($this->normalize($row[0] ?? ''), ':'));
            if ($rowLabel === $this->normalize($label)) {
                $value = trim((string) ($row[1] ?? ''));
                return $value !== '' ? $value : null;
            }
        }
        return null;
    }

    private function findRow(array $rows, callable $predicate): ?int
    {
        foreach ($rows as $index => $row) if ($predicate($row)) return $index;
        return null;
    }

    private function parseDate($value): string
    {
        try {
            if (is_numeric($value)) return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            foreach (['m/d/Y', 'd-M-y', 'd-M-Y', 'd/m/Y', 'Y-m-d'] as $format) {
                try { return Carbon::createFromFormat($format, trim((string) $value))->format('Y-m-d'); } catch (\Throwable $e) {}
            }
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Tanggal laporan NSC tidak dapat dibaca: '.$value.'.');
        }
    }

    private function normalizeTime($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') return null;
        $value = str_replace(';', ':', $value);
        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $value, $match)) return null;
        return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
    }

    private function money($value): ?float
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') return null;
        $value = str_replace(['Rp', 'rp', ' '], '', $value);
        $value = str_replace(',', '', $value);
        return is_numeric($value) ? (float) $value : null;
    }

    private function number($value): float
    {
        $value = trim((string) $value);
        return ($value === '' || $value === '-') ? 0.0 : (float) str_replace(',', '', $value);
    }

    private function displayNumber(float $value): string { return (string) ((int) $value === $value ? (int) $value : $value); }
    private function cleanName(string $value): string { return $this->normalize(preg_replace('/_+/', ' ', $value)); }
    private function normalize($value): string { return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $value)), 'UTF-8'); }
    private function hasContent(array $row): bool { return collect($row)->contains(fn ($value) => trim((string) $value) !== ''); }
    private function isPlaceholderRow(array $row): bool { return trim((string) ($row[0] ?? '')) === '' && $this->number($row[25] ?? null) === 0 && $this->number($row[26] ?? null) === 0; }
}
