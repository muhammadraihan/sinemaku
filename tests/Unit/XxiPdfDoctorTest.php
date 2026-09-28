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
