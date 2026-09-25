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
        $dayTotals = $this->parseSourceTotals($text);
        $screenTotals = $this->parseScreenTotals($text);
        $isTicketClassProfile = preg_match('/Ticket\s+Detail\s+Level\s*:\s*Ticket\s+Class/i', $text) === 1;
        $sourceTotals = $isTicketClassProfile
            ? ($screenTotals ?? $dayTotals)
            : $dayTotals;
        $reconciliationTotals = $sourceTotals;
        if (
            $totals['admits'] !== $reconciliationTotals['admits']
            || abs($totals['gross'] - $reconciliationTotals['gross']) > 0.02
            || abs($totals['tax_amount'] - $reconciliationTotals['tax_amount']) > 0.02
            || abs($totals['net'] - $reconciliationTotals['net']) > 0.02
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
                'text' => implode("\n", array_slice($lines, $start['index'], $end - $start['index'])),
            ];
        }

        return $blocks;
    }

    private function parseBlockRows(array $block, string $reportDate): array
    {
        $lines = $this->lines($block['text']);
        $headerIndex = $this->findLineIndex($lines, function ($line) {
            return stripos($line, 'Attribute') !== false && stripos($line, 'Admits') !== false;
        });
        $detailLines = $headerIndex === null ? $lines : array_slice($lines, $headerIndex + 1);
        $money = '[\d.,]+';
        $ticketBeforePrice = '/^(?:(\d{1,2}:\d{2})\s+)?([A-Z][A-Z0-9\- ]*?)\s+('.$money.')\s+(\d+)\s+('.$money.')\s+('.$money.')\s+('.$money.')\s*(2D|3D)\s*$/i';
        $ticketAfterAttribute = '/^(?:(\d{1,2}:\d{2})\s+)?('.$money.')\s+(\d+)\s+('.$money.')\s+('.$money.')\s+('.$money.')\s*(2D|3D)\s*([A-Z][A-Z0-9\- ]+)\s*$/i';

        $rows = [];
        $currentTime = null;
        $showByTime = [];
        $wrappedTicketWords = [];
        foreach ($detailLines as $line) {
            // Vista may wrap a Ticket Type on separate uppercase lines before its
            // numeric detail (for example "COMPLIMENTRY" / "VOUCHER"). Rejoin
            // only that known shape; summary lines are still rejected below.
            if (preg_match('/^[A-Z][A-Z0-9\- ]*$/i', $line)
                && stripos($line, 'TOTAL') === false
                && stripos($line, 'CINEMA') === false) {
                $wrappedTicketWords[] = $line;
                continue;
            }
            if ($wrappedTicketWords && preg_match('/^(?:\d{1,2}:\d{2}\s+)?[\d.,]+\s+\d+\b/', $line)) {
                $line = implode(' ', $wrappedTicketWords) . ' ' . $line;
            }
            $wrappedTicketWords = [];
            if (stripos($line, 'Day Total') !== false || stripos($line, 'Total for Film') !== false) {
                break;
            }

            $layout = null;
            if (preg_match($ticketBeforePrice, $line, $match)) {
                $layout = 'ticket_before_price';
            } elseif (preg_match($ticketAfterAttribute, $line, $match)) {
                $layout = 'ticket_after_attribute';
            } else {
                continue;
            }

            if ($match[1] !== '') {
                $currentTime = $match[1];
                if (!isset($showByTime[$currentTime])) {
                    $showByTime[$currentTime] = count($showByTime) + 1;
                }
            }
            if (!$currentTime) {
                continue;
            }

            if ($layout === 'ticket_after_attribute') {
                $ticketType = $match[8];
                $price = $this->parseMoney($match[2]);
                $admits = $this->parseInteger($match[3]);
                $gross = $this->parseMoney($match[4]);
                $taxAmount = $this->parseMoney($match[5]);
                $net = $this->parseMoney($match[6]);
                $attribute = strtoupper($match[7]);
            } else {
                $ticketType = $match[2];
                $price = $this->parseMoney($match[3]);
                $admits = $this->parseInteger($match[4]);
                $gross = $this->parseMoney($match[5]);
                $taxAmount = $this->parseMoney($match[6]);
                $net = $this->parseMoney($match[7]);
                $attribute = strtoupper($match[8]);
            }

            if ($price === null || $admits === null || $gross === null || $taxAmount === null || $net === null) {
                continue;
            }
            // Some complimentary Ticket Class rows print a zero ticket price while
            // retaining their attributed gross. The printed gross/tax/net remains
            // authoritative and is reconciled against the source screen total.
            if (($price > 0 && abs(($price * $admits) - $gross) > 0.02) || abs(($taxAmount + $net) - $gross) > 0.02) {
                throw new \InvalidArgumentException('Detail nominal PDF tidak konsisten pada jam ' . $currentTime . '.');
            }
            $rows[] = [
                'tanggal' => $reportDate,
                'jam_tayang' => $currentTime,
                'show' => $showByTime[$currentTime],
                'studio' => $this->normalizeStudioNumber($block['studio']),
                'type_tiket' => $this->normalizeName($ticketType),
                'harga' => $price,
                'jumlah' => $admits,
                'gross' => $gross,
                'tax_amount' => $taxAmount,
                'tax_rate' => $gross > 0 ? round(($taxAmount / $gross) * 100, 4) : 0.0,
                'net' => $net,
                'attribute' => $attribute,
            ];
        }

        return $rows;
    }

    private function parseScreenTotals(string $text): ?array
    {
        $money = '(?:\d{1,3}(?:\.\d{3})*,\d{2}|\d{1,3}(?:,\d{3})*\.\d{2})';
        preg_match_all('/('.$money.')(\d+)\s+('.$money.')('.$money.')\s*Total\s+for\s+Film\s+this\s+Screen/i', $text, $matches, PREG_SET_ORDER);
        if (!$matches) {
            return null;
        }

        $totals = ['admits' => 0, 'gross' => 0.0, 'tax_amount' => 0.0, 'net' => 0.0];
        foreach ($matches as $match) {
            // Flattened Vista order: tax, admits, net, gross.
            $totals['tax_amount'] += $this->parseMoney($match[1]);
            $totals['admits'] += $this->parseInteger($match[2]);
            $totals['net'] += $this->parseMoney($match[3]);
            $totals['gross'] += $this->parseMoney($match[4]);
        }

        return [
            'admits' => $totals['admits'],
            'gross' => round($totals['gross'], 2),
            'tax_amount' => round($totals['tax_amount'], 2),
            'net' => round($totals['net'], 2),
        ];
    }

    private function parseSourceTotals(string $text): array
    {
        preg_match_all('/Day\s+Total\s+Paid\s+(\d+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)/i', $text, $matches, PREG_SET_ORDER);
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
        // Vista can print complementary totals in either order. The Ticket Type
        // profile puts admits first; Ticket Class may flatten it as net, tax,
        // gross, admits immediately before "Day Total Complementory".
        preg_match_all('/(?:Day\s+Total\s+Complement(?:o|a)ry\s+(\d+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)|([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)\s+(\d+)\s*Day\s+Total\s+Complement(?:o|a)ry)/i', $text, $complimentaryMatches, PREG_SET_ORDER);
        foreach ($complimentaryMatches as $match) {
            if (!empty($match[1])) {
                $totals['admits'] += $this->parseInteger($match[1]);
                $totals['gross'] += $this->parseMoney($match[2]);
                $totals['tax_amount'] += $this->parseMoney($match[3]);
                $totals['net'] += $this->parseMoney($match[4]);
            } else {
                $totals['net'] += $this->parseMoney($match[5]);
                $totals['tax_amount'] += $this->parseMoney($match[6]);
                $totals['gross'] += $this->parseMoney($match[7]);
                $totals['admits'] += $this->parseInteger($match[8]);
            }
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
        $value = trim($value);
        if (!preg_match('/^-?[\d.,]+$/', $value)) {
            return null;
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');
        if ($lastComma !== false && $lastDot !== false) {
            // The last separator is the decimal separator: support both 1,234.56 and 1.234,56.
            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($lastComma !== false) {
            $fraction = strlen($value) - $lastComma - 1;
            $value = $fraction <= 2 ? str_replace(',', '.', $value) : str_replace(',', '', $value);
        } elseif (substr_count($value, '.') > 1) {
            $value = str_replace('.', '', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function parseInteger(string $value): ?int
    {
        if (!preg_match('/^\d+$/', trim($value))) {
            return null;
        }

        return (int) $value;
    }
}
