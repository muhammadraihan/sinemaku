<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Smalot\PdfParser\Parser as PdfParser;

class PlatinumPdfParser
{
    public function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('File PDF tidak dapat dibaca.');
        }

        try {
            $text = (new PdfParser())->parseFile($path)->getText();
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Isi PDF tidak dapat diekstrak. Pastikan file bukan PDF scan atau terenkripsi.', 0, $exception);
        }

        return $this->parseText($text);
    }

    public function parseText(string $text): array
    {
        $normalized = preg_replace('/\s+/u', ' ', str_replace(["\r", "\n", "\t", "\x0c"], ' ', $text));
        $normalized = trim($normalized);

        if (!preg_match('/Screening\s+Period\s+(\d{2})-(\d{2})-(\d{4})\s+\d{2}:\d{2}\s+[AP]M\s+TO/i', $normalized, $period)) {
            throw new \InvalidArgumentException('Tanggal laporan tidak dapat dibaca dari PDF.');
        }
        $reportDate = Carbon::createFromFormat('d-m-Y', "{$period[1]}-{$period[2]}-{$period[3]}")->toDateString();

        $cinemaName = $this->extractCinemaName($text);
        if ($cinemaName === '') {
            throw new \InvalidArgumentException('Nama bioskop Platinum tidak dapat dibaca dari PDF.');
        }

        if (!preg_match('/Movie\s+Sessions\s+Admits\s+Gross\s+Net\s+Tax\s+(.+?)\s+(\d+)\s+(\d+)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/i', $normalized, $movie)) {
            throw new \InvalidArgumentException('Nama film atau ringkasan film tidak dapat dibaca dari PDF.');
        }
        $filmName = $this->normalizeName($movie[1]);
        $sourceTotals = [
            'admits' => (int) $movie[3],
            'gross' => $this->money($movie[4]),
            'net' => $this->money($movie[5]),
            'tax_amount' => $this->money($movie[6]),
        ];

        $rowPattern = '/(\d{2}-[A-Za-z]{3}-\d{4})\s*STUDIO\s*(\d+)\s+(\d{1,2}:\d{2})\s*([ap]m)\s+(\d+)\s*([A-Z][A-Z0-9 -]*?)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/i';
        preg_match_all($rowPattern, $normalized, $matches, PREG_SET_ORDER);
        if (!$matches) {
            throw new \InvalidArgumentException('Tidak ada detail tiket Platinum yang dapat diparse dari PDF.');
        }

        $rows = [];
        $profiles = [];
        foreach ($matches as $index => $match) {
            $admits = (int) $match[5];
            $ticketType = $this->normalizeName($match[6]);
            $price = $this->money($match[7]);
            $gross = $this->money($match[8]);
            $net = $this->money($match[9]);
            $taxAmount = $this->money($match[10]);
            if ($admits === 0 && $gross == 0.0 && $price == 0.0) {
                continue;
            }
            if (abs(($price * $admits) - $gross) > 0.02) {
                throw new \InvalidArgumentException('Harga × admits tidak sama dengan gross pada studio '.$match[2].' jam '.$match[3].'.');
            }
            $inclusive = abs($net - $gross) <= 0.02;
            $taxExclusive = abs(($net + $taxAmount) - $gross) <= 1.00;
            if (!$inclusive && !$taxExclusive) {
                throw new \InvalidArgumentException('Net + tax tidak sama dengan gross pada studio '.$match[2].' jam '.$match[3].'.');
            }
            $profiles[$inclusive ? 'gross_inclusive_net' : 'standard_tax_exclusive_net'] = true;
            $time = Carbon::createFromFormat('g:i A', strtoupper($match[3].' '.$match[4]))->format('H:i');
            $rows[] = [
                'tanggal' => $reportDate,
                'jam_tayang' => $time,
                'show' => count($rows) + 1,
                'studio' => $this->normalizeStudio($match[2]),
                'type_tiket' => $ticketType,
                'harga' => $price,
                'jumlah' => $admits,
                'gross' => $gross,
                'tax_amount' => $taxAmount,
                'tax_rate' => $gross > 0 ? round(($taxAmount / $gross) * 100, 4) : 0,
                'net' => $net,
            ];
        }
        if (!$rows) {
            throw new \InvalidArgumentException('Tidak ada detail tiket Platinum dengan penjualan.');
        }

        $totals = [
            'admits' => array_sum(array_column($rows, 'jumlah')),
            'gross' => round(array_sum(array_column($rows, 'gross')), 2),
            'tax_amount' => round(array_sum(array_column($rows, 'tax_amount')), 2),
            'net' => round(array_sum(array_column($rows, 'net')), 2),
        ];
        $totals['tax_rate'] = $totals['gross'] > 0 ? round(($totals['tax_amount'] / $totals['gross']) * 100, 4) : 0;
        foreach (['admits', 'gross', 'tax_amount', 'net'] as $field) {
            $tolerance = $field === 'admits' ? 0 : 1.00;
            if (abs($totals[$field] - $sourceTotals[$field]) > $tolerance) {
                throw new \InvalidArgumentException('Total detail PDF tidak sama dengan ringkasan film sumber.');
            }
        }
        $sourceTotals['tax_rate'] = $sourceTotals['gross'] > 0 ? round(($sourceTotals['tax_amount'] / $sourceTotals['gross']) * 100, 4) : 0;
        $financialProfile = count($profiles) === 1 ? array_key_first($profiles) : 'mixed_source_semantics';

        return [
            'cinema_name' => $cinemaName,
            'film_name' => $filmName,
            'studio' => $rows[0]['studio'],
            'report_date' => $reportDate,
            'rows' => $rows,
            'totals' => $totals,
            'source_totals' => $sourceTotals,
            'financial_profile' => $financialProfile,
            'warnings' => $financialProfile === 'gross_inclusive_net'
                ? ['PDF Platinum mencetak Net sama dengan Gross; nilai sumber dipertahankan dan tidak dihitung ulang dari Gross - Tax.']
                : [],
        ];
    }

    private function extractCinemaName(string $text): string
    {
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if (preg_match('/^(Platinum\s+Cineplex\s+[\p{L}][\p{L} .\'-]*)$/iu', $line, $match)) {
                return $this->normalizeName($match[1]);
            }
        }

        return '';
    }

    private function money(string $value): float
    {
        return (float) str_replace(',', '', $value);
    }

    private function normalizeStudio(string $value): string
    {
        return (string) ((int) preg_replace('/[^0-9]/', '', $value));
    }

    private function normalizeName(string $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', $value)), 'UTF-8');
    }
}
