<?php

namespace App\Services\Reports;

use Symfony\Component\Process\Process;

class XxiPdfParser
{
    private const SHOWTIMES = [
        1 => '11:00',
        2 => '13:00',
        3 => '15:00',
        4 => '17:00',
        5 => '19:00',
        6 => '21:00',
        7 => '23:00',
    ];

    public function parse(string $pdfPath): array
    {
        $realPath = realpath($pdfPath);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            throw new \InvalidArgumentException('File PDF XXI tidak dapat dibaca.');
        }

        $extractor = app(RemotePdfTextExtractor::class);

        $binary = (string) config('services.pdftotext.binary', 'pdftotext');
        if ($this->binaryIsUsable($binary)) {
            return $this->parseText($this->extractWithBinary($realPath, $binary));
        }

        // No external binary (shared hosting). The distributor reports use
        // standard RC4 encryption with an empty user password, so the built-in
        // extractor recovers the text in pure PHP. An unsupported variant
        // (for example AES) throws here and falls through to the remote service.
        $builtInReason = 'Ekstraksi PDF bawaan tidak tersedia';
        if ((bool) config('services.pdftotext.builtin', true)) {
            try {
                return $this->parseText(app(PdfTextExtractor::class)->extract($realPath));
            } catch (\RuntimeException $exception) {
                $builtInReason = $exception->getMessage();
            }
        }

        if ($extractor->configured()) {
            return $this->parseText($extractor->extract($realPath));
        }

        throw new \RuntimeException($builtInReason.'. Pasang poppler-utils, atur XXI_PDFTOTEXT_BINARY ke path absolutnya, atau konfigurasikan layanan ekstraksi PDF di VPS.');
    }

    private function binaryIsUsable(string $binary): bool
    {
        if (!function_exists('proc_open') || $binary === '') {
            return false;
        }

        try {
            $process = new Process([$binary, '-v']);
            $process->setTimeout(10);
            $process->run();
        } catch (\Symfony\Component\Process\Exception\RuntimeException $exception) {
            return false;
        }

        // A missing binary makes the *shell* exit 127 without throwing, so the
        // exit code alone is not enough: a configured-but-absent path (the
        // Hostinger shared-hosting case) must be reported as unusable, or the
        // built-in extractor never gets a chance to run.
        if ($process->getExitCode() === 127) {
            return false;
        }

        if (preg_match('/(?:not found|command not found|No such file|cannot execute)/i', $process->getOutput().$process->getErrorOutput()) === 1) {
            return false;
        }

        return $process->isSuccessful();
    }

    private function extractWithBinary(string $realPath, string $binary): string
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'xxi-pdf-');
        if ($outputPath === false) {
            throw new \RuntimeException('File sementara untuk ekstraksi PDF tidak dapat dibuat.');
        }

        try {
            $process = new Process([$binary, '-layout', $realPath, $outputPath]);
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful()) {
                $error = trim($process->getErrorOutput());
                throw new \InvalidArgumentException('Isi PDF XXI tidak dapat diekstrak dengan pdftotext -layout: '.$error);
            }

            $text = file_get_contents($outputPath);
            if ($text === false || trim($text) === '') {
                throw new \InvalidArgumentException('Isi PDF XXI kosong atau tidak memiliki text layer.');
            }

            return $text;
        } finally {
            @unlink($outputPath);
        }
    }

    public function parseText(string $text): array
    {
        $physicalLines = preg_split('/\R/u', str_replace("\x0c", "\n", $text));
        $lines = array_map(function ($line) {
            return trim(preg_replace('/[\t ]+/u', ' ', (string) $line));
        }, $physicalLines);

        $filmName = $this->filmName($lines);
        $reportDate = $this->reportDate($lines);
        $printedTotals = $this->printedTotals($lines);
        $showColumns = $this->showColumnCount($lines);
        $rows = [];
        $pending = [];
        $sourcePtn = 0;
        $sourceFp = 0;
        $city = null;
        $currentCinema = null;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\*\*\s*(.+?)\s*\*\*$/u', $line, $match)) {
                $city = $this->normalizeName($match[1]);
                $currentCinema = null;
                continue;
            }

            // In vertically merged cells, the chain suffix can be printed on
            // its own line after the first cinema row. It is not a new cinema
            // and must be consumed before wrapped-row joining sees it.
            if ($line === 'XXI' && $currentCinema !== null && !str_ends_with($currentCinema, ' XXI')) {
                $completeCinema = $currentCinema.' XXI';
                foreach ($rows as &$existingRow) {
                    if ($existingRow['source_cinema'] === $currentCinema && $existingRow['source_city'] === $city) {
                        $existingRow['source_cinema'] = $completeCinema;
                    }
                }
                unset($existingRow);
                $currentCinema = $completeCinema;
                continue;
            }

            $sourceRow = $index + 1;
            $parsed = $this->sourceRow($line, $showColumns);
            if ($parsed === null && preg_match('/^\d+\s+\d[\d,]*(?:\s+(?:-|\d[\d,]*)){'.($showColumns + 2).'}$/u', $line) === 1) {
                $parsed = $this->sourceRow('__BLANK_CINEMA__ '.$line, $showColumns);
                if ($parsed !== null) $parsed['cinema'] = '';
            }
            if ($parsed === null && isset($lines[$index - 1]) && $this->isCinemaFragment($lines[$index - 1])) {
                $parsed = $this->sourceRow($lines[$index - 1].' '.$line, $showColumns);
                if ($parsed !== null) {
                    $sourceRow--;
                }
            }
            // This generator wraps long cinema names onto the row *below* the
            // numbers. Layout text normally separates the two with a blank line,
            // but a missing separator must not be fatal: join the following name
            // line in front of the numeric row. The strict row shape plus the
            // show-total/PTN reconciliation below reject any join that is not a
            // real row.
            if ($parsed === null && isset($lines[$index + 1]) && $this->isCinemaFragment($lines[$index + 1])) {
                $joined = $this->sourceRow($lines[$index + 1].' '.$line, $showColumns);
                if ($joined !== null) {
                    $parsed = $joined;
                }
            }
            if ($parsed === null) {
                if ($city !== null && $this->looksLikeMalformedSourceRow($line)) {
                    throw new \InvalidArgumentException(
                        'Baris sumber XXI '.$sourceRow.' malformed dan tidak dapat ditebak.'
                        .' Isi baris: "'.$line.'"'
                        .(($nearest = $this->nearestCinemaName($lines, $index, $showColumns)) !== null
                            ? ' Nama bioskop terdekat: "'.$nearest.'".'
                            : '')
                    );
                }
                continue;
            }
            if ($city === null) {
                throw new \InvalidArgumentException('Heading kota tidak ditemukan untuk baris sumber XXI '.$sourceRow.'.');
            }

            $cinema = $parsed['cinema'];
            if ($cinema === '') {
                $cinema = $this->mergedCellCinemaName($lines, $index, $currentCinema, $showColumns);
            }
            if ($cinema === '') {
                throw new \InvalidArgumentException('Nama cinema tidak dapat dibaca pada baris sumber XXI '.$sourceRow.'.');
            }
            $currentCinema = $cinema;

            $showTotal = array_sum($parsed['shows']);
            if ($showTotal !== $parsed['ptn']) {
                throw new \InvalidArgumentException('Jumlah show tidak sama dengan PTN pada baris sumber XXI '.$sourceRow.'.');
            }

            $candidateShows = [];
            foreach ($parsed['shows'] as $show => $count) {
                if ($count === 0) {
                    continue;
                }
                $candidateShows[] = ['show' => $show, 'jam_tayang' => self::SHOWTIMES[$show]];
                $rows[] = [
                    'source_row' => $sourceRow,
                    'tgl_tayang' => $reportDate,
                    'nama_film' => $filmName,
                    'source_cinema' => $cinema,
                    'source_city' => $city,
                    'studio' => (string) $parsed['studio'],
                    'capacity' => $parsed['capacity'],
                    'ticket_name' => 'REGULAR',
                    'jam_tayang' => self::SHOWTIMES[$show],
                    'show' => (string) $show,
                    'jumlah' => $count,
                    'harga' => 0.0,
                    'tax' => 0.0,
                    'net' => 0.0,
                ];
            }

            if ($parsed['fp'] > 0) {
                if (!$candidateShows) {
                    throw new \InvalidArgumentException('FP tidak memiliki kandidat show valid pada baris sumber XXI '.$sourceRow.'.');
                }
                $pending[] = [
                    'key' => 'XXI-'.$sourceRow,
                    'source_row' => $sourceRow,
                    'source_cinema' => $cinema,
                    'source_city' => $city,
                    'jumlah' => $parsed['fp'],
                    'candidate_shows' => $candidateShows,
                ];
            }

            $sourcePtn += $parsed['ptn'];
            $sourceFp += $parsed['fp'];
        }

        if (!$rows) {
            throw new \InvalidArgumentException('Tidak ada baris penonton XXI yang dapat diparse.');
        }
        if ($printedTotals !== null && ($sourcePtn !== $printedTotals['ptn'] || $sourceFp !== $printedTotals['fp'])) {
            throw new \InvalidArgumentException('Total detail XXI tidak sama dengan TOTAL PTN/FP sumber.');
        }

        return [
            'film_name' => $filmName,
            'report_date' => $reportDate,
            'rows' => $rows,
            'pending_free_assignments' => $pending,
            'source_totals' => $printedTotals ?? ['ptn' => $sourcePtn, 'fp' => $sourceFp],
        ];
    }

    private function filmName(array $lines): string
    {
        foreach ($lines as $line) {
            if (preg_match('/^FILM\s+(.+)$/iu', $line, $match)) {
                $film = $this->normalizeName($match[1]);
                if ($film !== '') {
                    return $film;
                }
            }
        }

        throw new \InvalidArgumentException('Nama film tidak dapat dibaca dari PDF XXI.');
    }

    private function reportDate(array $lines): string
    {
        $months = [
            'JANUARY' => 1, 'JANUARI' => 1,
            'FEBRUARY' => 2, 'FEBRUARI' => 2,
            'MARCH' => 3, 'MARET' => 3,
            'APRIL' => 4,
            'MAY' => 5, 'MEI' => 5,
            'JUNE' => 6, 'JUNI' => 6,
            'JULY' => 7, 'JULI' => 7,
            'AUGUST' => 8, 'AGUSTUS' => 8,
            'SEPTEMBER' => 9,
            'OCTOBER' => 10, 'OKTOBER' => 10,
            'NOVEMBER' => 11,
            'DECEMBER' => 12, 'DESEMBER' => 12,
        ];

        foreach ($lines as $line) {
            if (!preg_match('/^(?:SHOW|REPORT\s*DATE|TANGGAL\s*LAPORAN)\s*:\s*(?:[\p{L}]+\s*,?\s*)?(\d{1,2})\s+([\p{L}]+)\s+(\d{4})$/iu', $line, $match)) {
                continue;
            }
            $monthName = mb_strtoupper($match[2], 'UTF-8');
            $month = $months[$monthName] ?? null;
            $day = (int) $match[1];
            $year = (int) $match[3];
            if ($month === null || !checkdate($month, $day, $year)) {
                break;
            }
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        throw new \InvalidArgumentException('Tanggal show/laporan tidak dapat dibaca dari PDF XXI.');
    }

    /**
     * Number of show columns in this report, read from the `1 2 3 ... PTN FP`
     * header row.
     *
     * The generator emits six show columns when the report is generated before
     * a midnight screening is scheduled and seven once it is, so the count must
     * come from the source rather than being assumed.
     */
    private function showColumnCount(array $lines): int
    {
        foreach ($lines as $line) {
            // The header may be its own line ("1 2 3 4 5 6 PTN FP") or part of the
            // CINEMA/St/Kp header line, so it is matched anywhere before "PTN FP".
            if (preg_match('/(?:^|\s)(1\s+2(?:\s+3(?:\s+4(?:\s+5(?:\s+6(?:\s+7)?)?)?)?)?)\s+PTN\s+FP$/u', $line, $match) === 1) {
                return count(preg_split('/\s+/', trim($match[1])));
            }
        }

        return 7;
    }

    private function sourceRow(string $line, int $showColumns): ?array
    {
        $token = '(?:-|\d[\d,]*)';
        $pattern = '/^(.*?)\s+(\d+)\s+(\d[\d,]*)\s+'
            .implode('\s+', array_fill(0, $showColumns, '('.$token.')'))
            .'\s+(\d[\d,]*)\s+('.$token.')$/u';

        if (!preg_match($pattern, $line, $match)) {
            return null;
        }

        $shows = [];
        for ($show = 1; $show <= $showColumns; $show++) {
            $shows[$show] = $this->integerToken($match[$show + 3]);
        }

        return [
            'cinema' => $this->normalizeName($match[1]),
            'studio' => (int) $match[2],
            'capacity' => $this->integerToken($match[3]),
            'shows' => $shows,
            'ptn' => $this->integerToken($match[$showColumns + 4]),
            'fp' => $this->integerToken($match[$showColumns + 5]),
        ];
    }

    private function printedTotals(array $lines): ?array
    {
        $found = null;
        foreach ($lines as $line) {
            if (preg_match('/\bTOTAL\s+([\d,]+)\s+(-|[\d,]+)\s*$/iu', $line, $match)) {
                $found = ['ptn' => $this->integerToken($match[1]), 'fp' => $this->integerToken($match[2])];
            }
        }
        return $found;
    }

    private function mergedCellCinemaName(array $lines, int $rowIndex, ?string $currentCinema, int $showColumns): string
    {
        // In layout extraction, a vertically merged cinema label may be placed
        // between its first and later numeric rows. Prefer the next explicit
        // label within the same contiguous group over the previous cinema.
        for ($offset = 1; $offset <= 3; $offset++) {
            $candidate = $lines[$rowIndex + $offset] ?? null;
            if ($candidate === null) break;
            if ($candidate === '') continue;
            if (preg_match('/^(?:\*\*|TOTAL\b|FILM\b|SHOW\b)/iu', $candidate) === 1) break;
            $next = $this->sourceRow($candidate, $showColumns);
            if ($next !== null && $next['cinema'] !== '') return $next['cinema'];
            if ($this->isCinemaFragment($candidate)) return $this->normalizeName($candidate);
        }
        return $this->wrappedCinemaName($lines, $rowIndex, $currentCinema, $showColumns);
    }

    private function wrappedCinemaName(array $lines, int $rowIndex, ?string $currentCinema, int $showColumns): string
    {
        $parts = [];
        for ($offset = -2; $offset <= 2; $offset++) {
            if ($offset === 0 || !isset($lines[$rowIndex + $offset])) {
                continue;
            }
            $candidate = $lines[$rowIndex + $offset];
            if ($candidate === '' || $this->sourceRow($candidate, $showColumns) !== null || !$this->isCinemaFragment($candidate)) {
                continue;
            }
            if ($candidate === 'XXI' && $currentCinema !== null && str_ends_with($currentCinema, ' XXI')) {
                continue;
            }
            $parts[] = $candidate;
        }
        $name = $this->normalizeName(implode(' ', array_unique($parts)));
        if ($name !== '') {
            return $name;
        }
        return $currentCinema ?? '';
    }

    /**
     * Best-effort cinema name near a malformed row, for the error message only.
     *
     * Text after the row is searched before text above it because this
     * generator prints a wrapped cinema name *below* its numbers; the parser
     * reads it from `P+1`, so reporting what the parser would have joined makes
     * the failure diagnosable without the PDF at hand.
     */
    private function nearestCinemaName(array $lines, int $rowIndex, int $showColumns): ?string
    {
        foreach ([[1, 2], [-1, -2]] as [$from, $to]) {
            $parts = [];
            for ($offset = $from; $offset !== $to + ($to > 0 ? 1 : -1); $offset += $from) {
                $index = $rowIndex + $offset;
                if (!isset($lines[$index])) {
                    break;
                }
                $candidate = $lines[$index];
                if ($candidate === '' || $this->sourceRow($candidate, $showColumns) !== null || !$this->isCinemaFragment($candidate)) {
                    break;
                }
                $parts[] = $candidate;
            }

            $name = $this->normalizeName(implode(' ', array_unique($parts)));
            if ($name !== '') {
                return $name;
            }
        }

        return null;
    }

    private function isCinemaFragment(string $line): bool
    {
        return preg_match('/\d/', $line) !== 1
            && preg_match('/^\*\*/', $line) !== 1
            && preg_match('/^(?:FILM|RELEASE|SHOW|GROUP|CINEMA|TOTAL|Halaman|Created|Catatan)/iu', $line) !== 1;
    }

    private function looksLikeMalformedSourceRow(string $line): bool
    {
        if ($line === '' || preg_match('/^(?:FILM|RELEASE|SHOW|GROUP|CINEMA|TOTAL|Halaman|Created|Catatan)/iu', $line)) {
            return false;
        }
        return preg_match('/(?:^|\s)(?:-|\d[\d,]*)(?:\s+(?:-|\d[\d,]*)){4,}\s*$/u', $line) === 1;
    }

    private function integerToken(string $value): int
    {
        if ($value === '-') {
            return 0;
        }
        return (int) str_replace(',', '', $value);
    }

    private function normalizeName(string $value): string
    {
        return mb_strtoupper(trim(preg_replace('/\s+/u', ' ', $value)), 'UTF-8');
    }
}
