<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Smalot\PdfParser\Parser as PdfParser;

class CinepolisPdfParser
{
    public function parse(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException('File PDF tidak dapat dibaca.');
        }

        try {
            $text = (new PdfParser())->parseFile($path)->getText();
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Isi PDF tidak dapat diekstrak. Pastikan file bukan PDF scan atau terenkripsi.');
        }

        return $this->parseText($text);
    }

    public function parseText(string $text): array
    {
        $lines = $this->lines($text);
        $reportIndex = $this->findLineIndex($lines, function ($line) {
            return stripos($line, 'Detailed Distributors Report') !== false;
        });
        $periodIndex = $this->findLineIndex($lines, function ($line) {
            return stripos($line, 'From ') === 0;
        });

        if ($periodIndex === null) {
            throw new \InvalidArgumentException('Tanggal laporan tidak dapat dibaca dari PDF.');
        }

        $cinemaName = $this->normalizeName($lines[0] ?? '');
        if ($cinemaName === '' || ($reportIndex !== null && $reportIndex === 0)) {
            throw new \InvalidArgumentException('Nama bioskop tidak dapat dibaca dari PDF.');
        }

        $periodLine = $lines[$periodIndex] ?? '';
        if (!preg_match('/From\\s+\\w+\\s+(\\d{2}\\/\\d{2}\\/\\d{4})/i', $periodLine, $dateMatch)) {
            throw new \InvalidArgumentException('Tanggal laporan tidak dapat dibaca dari PDF.');
        }
        $reportDate = Carbon::createFromFormat('d/m/Y', $dateMatch[1])->toDateString();

        $blocks = $this->screenBlocks($lines);
        if (!$blocks) {
            throw new \InvalidArgumentException('Studio/screen tidak dapat dibaca dari PDF.');
        }

        $filmName = $blocks[0]['film_name'];
        $studio = $blocks[0]['studio'];
        $rows = [];
        foreach ($blocks as $block) {
            $rows = array_merge($rows, $this->parseBlockRows($block, $reportDate));
        }
        if (!$rows) {
            throw new \InvalidArgumentException('Tidak ada detail tiket yang dapat diparse dari PDF.');
        }

        $totals = [
            'admits' => array_sum(array_column($rows, 'jumlah')),
            'gross' => round(array_sum(array_column($rows, 'gross')), 2),
            'tax_amount' => round(array_sum(array_column($rows, 'tax_amount')), 2),
            'net' => round(array_sum(array_column($rows, 'net')), 2),
        ];
        $totals['tax_rate'] = $totals['gross'] > 0 ? round(($totals['tax_amount'] / $totals['gross']) * 100, 4) : 0.0;
        $sourceTotals = $this->parseSourceTotals($text);
        if (
            $totals['admits'] !== $sourceTotals['admits']
            || abs($totals['gross'] - $sourceTotals['gross']) > 0.02
            || abs($totals['tax_amount'] - $sourceTotals['tax_amount']) > 0.02
            || abs($totals['net'] - $sourceTotals['net']) > 0.02
        ) {
            throw new \InvalidArgumentException('Total detail PDF tidak sama dengan Day Total sumber.');
        }

        return [
            'cinema_name' => $cinemaName,
            'film_name' => $filmName,
            'studio' => $studio,
            'report_date' => $reportDate,
            'rows' => $rows,
            'totals' => $totals,
            'source_totals' => $sourceTotals,
        ];
    }

    private function screenBlocks(array $lines): array
    {
        $starts = [];
        foreach ($lines as $index => $line) {
            if (preg_match('/^(.+?)\s+CINEMA\s*(\d+)$/i', $line, $match)) {
                $filmName = $this->normalizeName($match[1]);
                if ($filmName !== '') {
                    $starts[] = ['index' => $index, 'film_name' => $filmName, 'studio' => $match[2]];
                }
            }
        }

        $blocks = [];
        foreach ($starts as $position => $start) {
            $end = $starts[$position + 1]['index'] ?? count($lines);
            $blocks[] = [
                'film_name' => $start['film_name'],
                'studio' => $start['studio'],
                'text' => implode(' ', array_slice($lines, $start['index'], $end - $start['index'])),
            ];
        }

        return $blocks;
    }

    private function parseBlockRows(array $block, string $reportDate): array
    {
        $raw = preg_replace('/\\s+/', ' ', str_replace("\\t", ' ', $block['text']));
        $raw = preg_replace('/.*?Attribute\\s+/s', '', $raw, 1);
        $raw = preg_replace('/Day Total.*$/s', '', $raw);
        $rowPattern = '/(?:(\\d{1,2}:\\d{2})\\s+)?(REGULAR(?:-O)?)\\s+([\\d,]+(?:\\.\\d{1,2})?)\\s+(\\d+)\\s+([\\d,]+(?:\\.\\d{1,2})?)\\s+([\\d,]+(?:\\.\\d{1,2})?)\\s+([\\d,]+(?:\\.\\d{1,2})?)\\s*2D/i';
        preg_match_all($rowPattern, $raw, $matches, PREG_SET_ORDER);

        $rows = [];
        $currentTime = null;
        $showByTime = [];
        foreach ($matches as $match) {
            if ($match[1] !== '') {
                $currentTime = $match[1];
                if (!isset($showByTime[$currentTime])) {
                    $showByTime[$currentTime] = count($showByTime) + 1;
                }
            }
            if (!$currentTime) {
                continue;
            }
            $price = $this->parseMoney($match[3]);
            $admits = $this->parseInteger($match[4]);
            $gross = $this->parseMoney($match[5]);
            $taxAmount = $this->parseMoney($match[6]);
            $net = $this->parseMoney($match[7]);
            if ($price === null || $admits === null || $gross === null || $taxAmount === null || $net === null) {
                continue;
            }
            if (abs(($price * $admits) - $gross) > 0.02 || abs(($taxAmount + $net) - $gross) > 0.02) {
                throw new \InvalidArgumentException('Detail nominal PDF tidak konsisten pada jam ' . $currentTime . '.');
            }
            $rows[] = [
                'tanggal' => $reportDate,
                'jam_tayang' => $currentTime,
                'show' => $showByTime[$currentTime],
                'studio' => $this->normalizeStudioNumber($block['studio']),
                'type_tiket' => $this->normalizeName($match[2]),
                'harga' => $price,
                'jumlah' => $admits,
                'gross' => $gross,
                'tax_amount' => $taxAmount,
                'tax_rate' => round(($taxAmount / $gross) * 100, 4),
                'net' => $net,
                'attribute' => '2D',
            ];
        }

        return $rows;
    }

    private function parseSourceTotals(string $text): array
    {
        preg_match_all('/Day\s+Total\s+Paid\s+(\d+)\s+([\d,]+(?:\.\d{1,2})?)\s+([\d,]+(?:\.\d{1,2})?)\s+([\d,]+(?:\.\d{1,2})?)/i', $text, $matches, PREG_SET_ORDER);
        if (!$matches) {
            throw new \InvalidArgumentException('Total harian sumber tidak dapat dibaca dari PDF.');
        }

        $totals = ['admits' => 0, 'gross' => 0.0, 'tax_amount' => 0.0, 'net' => 0.0];
        foreach ($matches as $match) {
            $totals['admits'] += $this->parseInteger($match[1]);
            $totals['gross'] += $this->parseMoney($match[2]);
            $totals['tax_amount'] += $this->parseMoney($match[3]);
            $totals['net'] += $this->parseMoney($match[4]);
        }

        return [
            'admits' => $totals['admits'],
            'gross' => round($totals['gross'], 2),
            'tax_amount' => round($totals['tax_amount'], 2),
            'net' => round($totals['net'], 2),
        ];
    }

    private function lines(string $text): array
    {
        return array_values(array_filter(array_map(function ($line) {
            return trim(preg_replace('/\s+/', ' ', $line));
        }, preg_split('/\R/u', $text)), function ($line) {
            return $line !== '';
        }));
    }

    private function findLineIndex(array $lines, callable $matches): ?int
    {
        foreach ($lines as $index => $line) {
            if ($matches($line)) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeName(string $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function isTicketType(string $value): bool
    {
        return preg_match('/^[A-Z][A-Z0-9\- ]+$/i', $value) === 1
            && stripos($value, 'TOTAL') === false
            && stripos($value, 'CINEMA') === false;
    }

    private function normalizeStudioNumber(string $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', $value);
        return $digits === '' ? '' : (string) ((int) $digits);
    }

    private function parseMoney(string $value): ?float
    {
        if (!preg_match('/^-?[\d,]+(?:\.\d{1,2})?$/', trim($value))) {
            return null;
        }

        return (float) str_replace(',', '', trim($value));
    }

    private function parseInteger(string $value): ?int
    {
        if (!preg_match('/^\d+$/', trim($value))) {
            return null;
        }

        return (int) $value;
    }
}
