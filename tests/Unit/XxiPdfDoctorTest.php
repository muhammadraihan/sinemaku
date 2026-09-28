<?php

namespace Tests\Unit;

use App\Services\Reports\XxiPdfParser;
use Tests\TestCase;

class XxiPdfDoctorTest extends TestCase
{
    public function test_pdf_binary_missing_reports_a_clear_environment_error(): void
    {
        config(['services.pdftotext.binary' => 'pdftotext-tidak-ada-'.uniqid()]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('pdftotext tidak tersedia pada server ini');

        $pdf = tempnam(sys_get_temp_dir(), 'xxi-doctor-').'.pdf';
        copy(base_path('tests/fixtures/xxi-report.pdf'), $pdf);

        try {
            (new XxiPdfParser())->parse($pdf);
        } finally {
            @unlink($pdf);
        }
    }
}
