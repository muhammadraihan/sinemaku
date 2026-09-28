<?php

namespace App\Services\Reports;

use RuntimeException;

/**
 * Extracts layout text from a PDF without any external binary.
 *
 * The XXI distributor reports are produced by mPDF as PDF 1.4 with standard
 * (RC4) owner/permission encryption and an empty user password, so the content
 * streams are recoverable in pure PHP: derive the file key from /O + /P + /ID,
 * RC4-decrypt each stream with its object key, inflate it, then rebuild
 * text lines from the BT/Td/Tj coordinates.
 *
 * The result is intentionally shaped like `pdftotext -layout` output so that
 * XxiPdfParser::parseText() consumes it unchanged.
 */
class PdfTextExtractor
{
    /** PDF standard password padding (spec 7.6.3.3). */
    private const PAD = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08"
        ."\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";

    public function extract(string $pdfPath): string
    {
        $data = @file_get_contents($pdfPath);
        if ($data === false || $data === '') {
            throw new RuntimeException('File PDF tidak dapat dibaca untuk ekstraksi teks.');
        }

        $objects = $this->scanObjects($data);
        $dictionary = $this->encryptionDictionary($data);
        $key = $dictionary === null ? null : $this->fileKey($data, $dictionary);

        $pages = $this->pageOrder($objects);
        if ($pages === []) {
            throw new RuntimeException('Struktur halaman PDF XXI tidak dapat dikenali. Kirim berkas asli dari distributor.');
        }

        $lines = [];
        foreach ($pages as $number) {
            $content = $this->decodedStream($objects, $number, $key);
            if ($content === null) {
                continue;
            }

            foreach ($this->layoutLines($content) as $line) {
                $lines[] = $line;
            }
        }

        if ($lines === []) {
            throw new RuntimeException('Isi PDF XXI tidak memiliki teks yang dapat dibaca. Pastikan berkas bukan hasil scan.');
        }

        return implode("\n", $lines);
    }

    /**
     * Map every `N G obj` to its dictionary and raw stream bytes.
     *
     * @return array<int, array{dict: string, stream: ?string}>
     */
    private function scanObjects(string $data): array
    {
        $objects = [];
        $offset = 0;
        $length = strlen($data);

        while (preg_match('/(?:^|[\r\n>\]\s])(\d+)\s+(\d+)\s+obj\b/', $data, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $number = (int) $match[1][0];
            $start = $match[0][1] + strlen($match[0][0]);
            $end = strpos($data, 'endobj', $start);
            if ($end === false) {
                break;
            }

            $body = substr($data, $start, $end - $start);
            $streamAt = strpos($body, 'stream');
            if ($streamAt === false) {
                $objects[$number] = ['dict' => $body, 'stream' => null];
            } else {
                $dict = substr($body, 0, $streamAt);
                $payload = substr($body, $streamAt + 6);
                $payload = preg_replace('/^\r\n|^\n/', '', $payload, 1) ?? $payload;

                // Prefer /Length: the payload is binary and may itself contain
                // the bytes "endstream", so slicing by keyword alone is unsafe.
                $declared = preg_match('/\/Length\s+(\d+)/', $dict, $lengthMatch) === 1 ? (int) $lengthMatch[1] : 0;
                if ($declared > 0 && $declared <= strlen($payload)) {
                    $payload = substr($payload, 0, $declared);
                } else {
                    $endstream = strrpos($payload, 'endstream');
                    if ($endstream !== false) {
                        $payload = substr($payload, 0, $endstream);
                    }
                }

                $objects[$number] = ['dict' => $dict, 'stream' => $payload];
            }

            $offset = $end + 6;
            if ($offset >= $length) {
                break;
            }
        }

        return $objects;
    }

    /**
     * Plaintext /Encrypt dictionary, or null when the file is not encrypted.
     *
     * @return array{R: int, V: int, O: string, P: int, length: int, id: string}|null
     */
    private function encryptionDictionary(string $data): ?array
    {
        if (!preg_match('/\/Encrypt\s+(\d+)\s+\d+\s+R/', $data, $reference)) {
            return null;
        }

        $objects = $this->scanObjects($data);
        $number = (int) $reference[1];
        if (!isset($objects[$number])) {
            throw new RuntimeException('Kamus enkripsi PDF XXI tidak ditemukan.');
        }

        $dict = $objects[$number]['dict'];
        if (preg_match('/\/Filter\s*\/Standard/', $dict) !== 1) {
            throw new RuntimeException('PDF XXI memakai pengaman yang tidak didukung (bukan Standard).');
        }
        if (preg_match('/\/(?:CF|StmF|StrF)\b/', $dict) === 1) {
            throw new RuntimeException('PDF XXI memakai enkripsi AES yang belum didukung ekstraksi bawaan. Gunakan jalur layanan ekstraksi.');
        }
        if (!preg_match('/\/O\s*\((.*?)\)\s*\/U/s', $dict, $oMatch)) {
            throw new RuntimeException('Kunci enkripsi PDF XXI tidak terbaca.');
        }
        if (!preg_match('/\/P\s+(-?\d+)/', $dict, $pMatch)) {
            throw new RuntimeException('Izin PDF XXI tidak terbaca.');
        }
        if (!preg_match('/\/ID\s*\[\s*<([0-9a-fA-F]+)>/', $data, $idMatch)) {
            throw new RuntimeException('ID berkas PDF XXI tidak terbaca.');
        }

        $r = preg_match('/\/R\s+(\d+)/', $dict, $rMatch) === 1 ? (int) $rMatch[1] : 2;
        $v = preg_match('/\/V\s+(\d+)/', $dict, $vMatch) === 1 ? (int) $vMatch[1] : 1;
        $length = preg_match('/\/Length\s+(\d+)/', $dict, $lMatch) === 1 ? (int) $lMatch[1] : 40;

        return [
            'R' => $r,
            'V' => $v,
            'O' => $this->decodeLiteralString($oMatch[1]),
            'P' => (int) $pMatch[1],
            'length' => $length,
            'id' => (string) hex2bin($idMatch[1]),
        ];
    }

    /**
     * Recover the RC4 file key. Only the empty-user-password case is supported,
     * which is what the distributor generator produces.
     *
     * @param array{R: int, V: int, O: string, P: int, length: int, id: string} $dictionary
     */
    private function fileKey(string $data, array $dictionary): ?string
    {
        $padded = (int) $dictionary['P'];

        $seed = hash('md5', self::PAD.$dictionary['O'].pack('V', $padded).$dictionary['id'], true);

        if ($dictionary['R'] >= 3) {
            $length = max(5, min(16, intdiv($dictionary['length'], 8)));
            for ($i = 0; $i < 50; $i++) {
                $seed = hash('md5', substr($seed, 0, $length), true);
            }

            return substr($seed, 0, $length);
        }

        return substr($seed, 0, 5);
    }

    /**
     * Page objects in reading order, resolved through the /Pages /Kids tree.
     *
     * @param array<int, array{dict: string, stream: ?string}> $objects
     *
     * @return list<int>
     */
    private function pageOrder(array $objects): array
    {
        foreach ($objects as $object) {
            if (preg_match('/\/Type\s*\/Pages/', $object['dict']) !== 1
                || preg_match('/\/Kids\s*\[(.*?)\]/s', $object['dict'], $kids) !== 1) {
                continue;
            }

            preg_match_all('/(\d+)\s+\d+\s+R/', $kids[1], $references);
            $pages = array_map('intval', $references[1]);
            if ($pages !== []) {
                return $pages;
            }
        }

        // Fallback: keep document order of /Type /Page objects.
        $pages = [];
        foreach ($objects as $number => $object) {
            if (preg_match('/\/Type\s*\/Page\b/', $object['dict']) === 1) {
                $pages[] = (int) $number;
            }
        }

        return $pages;
    }

    /**
     * Decrypted and inflated content stream for a page object.
     *
     * @param array<int, array{dict: string, stream: ?string}> $objects
     */
    private function decodedStream(array $objects, int $page, ?string $key): ?string
    {
        if (!isset($objects[$page])) {
            return null;
        }

        if (preg_match('/\/Contents\s+(\d+)\s+\d+\s+R/', $objects[$page]['dict'], $reference) !== 1) {
            return null;
        }

        $content = (int) $reference[1];
        if (!isset($objects[$content]['stream'])) {
            return null;
        }

        $stream = $objects[$content]['stream'];
        $dict = $objects[$content]['dict'];

        if ($key !== null && $key !== '') {
            $stream = $this->rc4($this->objectKey($key, $content), $stream);
        }

        if (preg_match('/\/FlateDecode/', $dict) === 1) {
            $stream = $this->inflate($stream);
        }

        return $stream;
    }

    private function objectKey(string $key, int $number): string
    {
        // PDF object keys use the low 3 bytes of the object number, little-endian.
        $seed = $key.substr(pack('V', $number), 0, 3)."\x00\x00";

        return substr(hash('md5', $seed, true), 0, min(strlen($key) + 5, 16));
    }

    private function inflate(string $data): string
    {
        $trimmed = rtrim($data, "\r\n \t\x00");
        $out = @gzuncompress($trimmed);
        if ($out === false) {
            $out = @gzuncompress($data);
        }
        if ($out === false) {
            $out = @gzinflate($trimmed);
        }
        if ($out === false) {
            return '';
        }

        return $out;
    }

    private function rc4(string $key, string $data): string
    {
        $state = range(0, 255);
        $j = 0;
        $keyLength = strlen($key);

        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $state[$i] + ord($key[$i % $keyLength])) & 0xFF;
            $swap = $state[$i];
            $state[$i] = $state[$j];
            $state[$j] = $swap;
        }

        $out = '';
        $i = $j = 0;
        $length = strlen($data);

        for ($n = 0; $n < $length; $n++) {
            $i = ($i + 1) & 0xFF;
            $j = ($j + $state[$i]) & 0xFF;
            $swap = $state[$i];
            $state[$i] = $state[$j];
            $state[$j] = $swap;
            $out .= chr(ord($data[$n]) ^ $state[($state[$i] + $state[$j]) & 0xFF]);
        }

        return $out;
    }

    /**
     * Rebuild `pdftotext -layout` style lines from BT/Td/Tj coordinates.
     *
     * @return list<string>
     */
    private function layoutLines(string $content): array
    {
        $items = $this->textItems($content);

        if ($items === []) {
            return [];
        }

        usort($items, fn (array $a, array $b) => $a['y'] === $b['y'] ? $a['x'] <=> $b['x'] : $b['y'] <=> $a['y']);

        return $this->withParagraphBreaks($items, []);
    }

    /**
     * Every shown string with its position, in content-stream order.
     *
     * Text state is tracked across the whole stream because the same BT/ET
     * block can move (Td/Tm) and draw several strings, and blocks differ per
     * generator: mPDF emits one absolute `x y Td` per string, while the
     * synthetic A4 table fixture draws every string from a single Tm.
     *
     * @return list<array{y: float, x: float, text: string}>
     */
    private function textItems(string $content): array
    {
        $items = [];
        $x = 0.0;
        $y = 0.0;
        $leading = 0.0;
        $pending = [];

        $pattern = '/(\/F\d+\s+[\d.]+\s+Tf)|(-?[\d.]+\s+){6}Tm\b|(-?[\d.]+\s+){2}Td\b|'
            .'(-?[\d.]+)\s+TL\b|(?<![A-Za-z0-9])T\*(?![A-Za-z0-9])|'
            .'\((?:\\\\.|[^\\\\()])*\)\s*Tj|\[(?:[^\[\]]|\\\\.)*\]\s*TJ/s';

        if (preg_match_all($pattern, $content, $tokens) === false) {
            return [];
        }

        $flush = function () use (&$pending, &$items, &$x, &$y): void {
            $text = trim(implode('', $pending));
            $pending = [];
            if ($text !== '') {
                $items[] = ['y' => round($y, 1), 'x' => $x, 'text' => $text];
            }
        };

        foreach ($tokens[0] as $token) {
            if (preg_match('/Tf\b/', $token) === 1) {
                continue;
            }

            // Text leading + T*: the next line drops by TL, as the table
            // fixture does when it draws a whole page from a single Td.
            if (preg_match('/^(-?[\d.]+)\s+TL\b/', $token, $leadingToken) === 1) {
                $leading = (float) $leadingToken[1];
                continue;
            }

            if (preg_match('/^T\*$/', trim($token)) === 1) {
                $flush();
                $y -= $leading;
                continue;
            }

            if (preg_match('/(-?[\d.]+)\s+(-?[\d.]+)\s+Tm\b/', $token, $matrix) === 1) {
                $flush();
                $x = (float) $matrix[1];
                $y = (float) $matrix[2];
                continue;
            }

            if (preg_match('/(-?[\d.]+)\s+(-?[\d.]+)\s+Td\b/', $token, $move) === 1) {
                $flush();
                $x = (float) $move[1];
                $y = (float) $move[2];
                continue;
            }

            if (preg_match('/^\[(.*)\]\s*TJ$/s', $token, $array) === 1) {
                if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $array[1], $parts) !== false) {
                    foreach ($parts[0] as $part) {
                        $pending[] = $this->decodeTextString(substr($part, 1, -1));
                    }
                }
                continue;
            }

            $end = strrpos($token, ')');
            if ($end !== false) {
                $pending[] = $this->decodeTextString(substr($token, 1, $end - 1));
            }
        }

        $flush();

        return $items;
    }

    /**
     * @return list<string>
     */
    /**
     * Re-insert the blank lines that `pdftotext -layout` emits at vertical gaps.
     *
     * Downstream parsing treats a blank line as "no cinema name on the previous
     * row" and then reads the name from an adjacent line; without these breaks a
     * wrapped cinema name (name printed below its numbers, as this generator
     * does) is misread as a malformed row. The threshold is relative to the
     * tightest spacing on the page, so it adapts to each document's typography.
     *
     * @param list<array{y: float, x: float, text: string}> $items
     * @param list<string>                                  $lines
     *
     * @return list<string>
     */
    private function withParagraphBreaks(array $items, array $lines): array
    {
        $rows = [];
        $currentY = null;
        foreach ($items as $item) {
            if ($currentY === null || abs($item['y'] - $currentY) > 0.5) {
                $rows[] = ['y' => $item['y'], 'texts' => []];
                $currentY = $item['y'];
            }
            $last = count($rows) - 1;
            $rows[$last]['texts'][] = $item['text'];
        }

        $gaps = [];
        for ($i = 1, $count = count($rows); $i < $count; $i++) {
            $gap = abs($rows[$i - 1]['y'] - $rows[$i]['y']);
            if ($gap > 0.5) {
                $gaps[] = $gap;
            }
        }

        $tightest = $gaps === [] ? 0.0 : min($gaps);
        $threshold = $tightest > 0 ? $tightest * 1.4 : 0.0;

        $out = [];
        foreach ($rows as $index => $row) {
            if ($index > 0 && $threshold > 0 && abs($rows[$index - 1]['y'] - $row['y']) > $threshold) {
                $out[] = '';
            }
            $text = trim(implode(' ', $row['texts']));
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out === [] ? $lines : $out;
    }

    /**
     * Text strings in this generator use Identity-H CMaps, so the payload is
     * UTF-16BE for the glyphs that matter.
     */
    private function decodeTextString(string $raw): string
    {
        $value = $this->decodeLiteralString($raw);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, "\xFE\xFF")) {
            return $this->toUtf8(substr($value, 2));
        }

        if (strlen($value) % 2 === 0 && (str_contains($value, "\x00"))) {
            $first = ord($value[0]);
            $second = ord($value[1]);
            if (($first === 0 || $second === 0) && $this->looksLikeUtf16($value)) {
                return $this->toUtf8($value);
            }
        }

        return $value;
    }

    private function looksLikeUtf16(string $value): bool
    {
        $high = 0;
        $length = strlen($value);

        for ($i = 0; $i < $length; $i += 2) {
            if (ord($value[$i]) === 0) {
                $high++;
            }
        }

        return $high >= max(1, intdiv(intdiv($length, 2), 2));
    }

    private function toUtf8(string $utf16): string
    {
        $converted = @mb_convert_encoding($utf16, 'UTF-8', 'UTF-16BE');

        return $converted === false ? $utf16 : $converted;
    }

    /** Resolve PDF literal-string escapes. */
    private function decodeLiteralString(string $raw): string
    {
        $out = '';
        $length = strlen($raw);

        for ($i = 0; $i < $length; $i++) {
            $char = $raw[$i];
            if ($char !== '\\') {
                $out .= $char;
                continue;
            }

            $i++;
            if ($i >= $length) {
                break;
            }

            $next = $raw[$i];
            $escapes = ['n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\x0C", '(' => '(', ')' => ')', '\\' => '\\'];
            if (isset($escapes[$next])) {
                $out .= $escapes[$next];
                continue;
            }

            if ($next >= '0' && $next <= '7') {
                $octal = $next;
                while (strlen($octal) < 3 && $i + 1 < $length && $raw[$i + 1] >= '0' && $raw[$i + 1] <= '7') {
                    $octal .= $raw[++$i];
                }
                $out .= chr(octdec($octal) & 0xFF);
                continue;
            }

            $out .= $next;
        }

        return $out;
    }
}