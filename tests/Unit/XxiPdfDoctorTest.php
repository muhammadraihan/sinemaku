<?php

namespace Tests\Unit;

use App\Services\Reports\PdfTextExtractor;
use App\Services\Reports\XxiPdfParser;
use Tests\TestCase;

class XxiPdfDoctorTest extends TestCase
{
    /**
     * With every extraction route disabled, the operator must still get an
     * actionable message naming the cause — never a silent success or a raw
     * shell error.
     */
    public function test_every_extraction_route_disabled_reports_a_clear_environment_error(): void
    {
        config([
            'services.pdftotext.binary' => 'pdftotext-tidak-ada-'.uniqid(),
            'services.pdftotext.builtin' => false,
            'services.pdf_extract.url' => '',
            'services.pdf_extract.secret' => '',
        ]);

        $this->expectException(\RuntimeException::class);

        $pdf = tempnam(sys_get_temp_dir(), 'xxi-doctor-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report.pdf'), $pdf);

        try {
            (new XxiPdfParser())->parse($pdf);
        } finally {
            @unlink($pdf);
        }
    }

    /**
     * The whole point of the built-in extractor: shared hosting has no Poppler,
     * so the report must still parse with no binary and no remote service.
     */
    public function test_built_in_extractor_parses_without_any_external_binary(): void
    {
        $pdf = tempnam(sys_get_temp_dir(), 'xxi-builtin-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report.pdf'), $pdf);

        try {
            $text = (new PdfTextExtractor())->extract($pdf);
            $result = (new XxiPdfParser())->parseText($text);

            $this->assertSame('2026-09-26', $result['report_date']);
            $this->assertSame('MEMBURU PEMANGSA', $result['film_name']);
            $this->assertSame(6, count($result['rows']));
            $this->assertSame(['ptn' => 290, 'fp' => 4], $result['source_totals']);
            $this->assertSame('BLOK M XXI', $result['rows'][0]['source_cinema']);
        } finally {
            @unlink($pdf);
        }
    }

    /**
     * A layout variant without blank-line separators must parse identically.
     *
     * The generator prints long cinema names on the row below their numbers. A
     * missing blank line between them previously aborted the import with
     * "baris sumber malformed"; the parser now joins the adjacent name line.
     */
    public function test_layout_without_blank_separators_parses_identically(): void
    {
        $withBreaks = implode("\n", [
            'FILM MEMBURU PEMANGSA',
            'SHOW: SABTU, 26 SEPTEMBER 2026',
            '** JAKARTA **',
            'BLOK M XXI 3 314 - 17 33 75 66 71 - 262 4',
            '** KLATEN **',
            '2 134 - - - - - - 9 9 -',
            'KLATEN TOWN SQUARE XXI',
            '3 134 - - 13 - 14 - - 27 -',
            'TOTAL 298 4',
        ]);

        // Same content with every blank line removed.
        $withoutBreaks = implode("\n", array_values(array_filter(
            preg_split('/\R/u', $withBreaks),
            fn (string $line) => trim($line) !== ''
        )));

        $parser = new XxiPdfParser();
        $parsed = $parser->parseText($withoutBreaks);

        $this->assertSame('MEMBURU PEMANGSA', $parsed['film_name']);
        $this->assertSame('2026-09-26', $parsed['report_date']);
        $this->assertSame(['ptn' => 298, 'fp' => 4], $parsed['source_totals']);
        $klaten = array_values(array_filter($parsed['rows'], fn (array $row) => $row['source_cinema'] === 'KLATEN TOWN SQUARE XXI'));

        $this->assertNotEmpty($klaten, 'Baris KLATEN TOWN SQUARE XXI harus terbaca.');
        $this->assertSame('KLATEN', $klaten[0]['source_city']);
        $this->assertSame(['7', '3', '5'], array_values(array_map(fn (array $row) => $row['show'], $klaten)));
    }

    /**
     * The malformed-row error must name the offending content, otherwise the
     * operator cannot tell which layout variant broke.
     */
    public function test_malformed_row_error_includes_the_offending_content(): void
    {
        $text = implode("\n", [
            'FILM MEMBURU PEMANGSA',
            'SHOW: SABTU, 26 SEPTEMBER 2026',
            '** JAKARTA **',
            'BLOK M XXI 3 314 - 17 33 75 66 71 - 262 4',
            // Leading columns are numbers, the tail is all dashes so the row
            // looks like data, but PTN is a dash where a number is required —
            // so neither adjacency join can rescue it.
            'ANEH XXI 3 100 - - - - - - - - -',
        ]);

        try {
            (new XxiPdfParser())->parseText($text);
            $this->fail('Kesalahan baris malformed seharusnya dilempar.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Isi baris:', $exception->getMessage());
        }
    }
    /**
     * Reports generated before a midnight screening is scheduled carry six show
     * columns instead of seven. Assuming seven read PTN as a show count and
     * aborted with "baris malformed", so the count must come from the header.
     */
    public function test_report_with_six_show_columns_parses_correctly(): void
    {
        $pdf = tempnam(sys_get_temp_dir(), 'xxi-six-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report-six-shows.pdf'), $pdf);

        try {
            $result = (new XxiPdfParser())->parse($pdf);

            $this->assertSame('2026-09-27', $result['report_date']);
            $this->assertSame(4, count($result['rows']));
            $this->assertSame(['ptn' => 149, 'fp' => 0], $result['source_totals']);

            // Only shows 1-6 exist in this report; 23:00 (show 7) must not appear.
            $shows = array_values(array_unique(array_map(fn (array $row) => (int) $row['show'], $result['rows'])));
            sort($shows);
            $this->assertSame([4, 5, 6], $shows);

            $showtimes = array_values(array_unique(array_map(fn (array $row) => $row['jam_tayang'], $result['rows'])));
            $this->assertNotContains('23:00', $showtimes);

            // The parsed detail must reconcile with the printed source total.
            $this->assertSame(
                $result['source_totals']['ptn'],
                array_sum(array_map(fn (array $row) => $row['jumlah'], $result['rows']))
            );
            $this->assertSame('ARION XXI', $result['rows'][0]['source_cinema']);
        } finally {
            @unlink($pdf);
        }
    }

    /**
     * Both layouts must be read from the same code path: the header decides the
     * column count, so each report keeps its own show range.
     */
    public function test_seven_column_report_still_reads_show_seven(): void
    {
        $pdf = tempnam(sys_get_temp_dir(), 'xxi-seven-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report.pdf'), $pdf);

        try {
            $result = (new XxiPdfParser())->parse($pdf);

            $this->assertSame(['ptn' => 290, 'fp' => 4], $result['source_totals']);
        } finally {
            @unlink($pdf);
        }
    }

    /** The built-in path must be what the parser uses when no binary exists. */
    public function test_parser_prefers_built_in_extractor_over_a_missing_binary(): void
    {
        config([
            'services.pdftotext.binary' => 'pdftotext-tidak-ada-'.uniqid(),
            'services.pdftotext.builtin' => true,
        ]);

        $pdf = tempnam(sys_get_temp_dir(), 'xxi-fallback-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report.pdf'), $pdf);

        try {
            $result = (new XxiPdfParser())->parse($pdf);

            $this->assertSame(['ptn' => 290, 'fp' => 4], $result['source_totals']);
            $this->assertSame(6, count($result['rows']));
        } finally {
            @unlink($pdf);
        }
    }

    /**
     * The production case: a path is configured (the default "pdftotext") but no
     * binary exists. A missing binary exits 127 through the shell without
     * throwing, so this must fall through to the built-in extractor rather than
     * reporting an extraction failure.
     */
    public function test_configured_but_absent_binary_still_uses_the_built_in_extractor(): void
    {
        config([
            'services.pdftotext.binary' => '/tidak-ada/pdftotext',
            'services.pdftotext.builtin' => true,
            'services.pdf_extract.url' => '',
            'services.pdf_extract.secret' => '',
        ]);

        $pdf = tempnam(sys_get_temp_dir(), 'xxi-absent-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report.pdf'), $pdf);

        try {
            $result = (new XxiPdfParser())->parse($pdf);

            $this->assertSame(['ptn' => 290, 'fp' => 4], $result['source_totals']);
            $this->assertSame(6, count($result['rows']));
        } finally {
            @unlink($pdf);
        }
    }
}
