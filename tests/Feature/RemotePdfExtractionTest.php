<?php

namespace Tests\Feature;

use App\Services\Reports\XxiPdfParser;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemotePdfExtractionTest extends TestCase
{
    private string $pdf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdf = tempnam(sys_get_temp_dir(), 'xxi-remote-').'.pdf';
        copy(base_path('tests/Fixtures/xxi-report.pdf'), $this->pdf);

        config([
            'services.pdf_extract.url' => 'https://extract.example.test',
            'services.pdf_extract.secret' => str_repeat('a', 48),
            'services.pdf_extract.timeout' => 30,
            'services.pdftotext.binary' => 'pdftotext-tidak-ada',
            'services.pdftotext.builtin' => false,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->pdf);

        parent::tearDown();
    }

    private function layoutText(): string
    {
        return implode("\n", [
            'FILM MEMBURU PEMANGSA',
            'SHOW: SABTU, 26 SEPTEMBER 2026',
            '** JAKARTA **',
            'BLOK M XXI 3 314 - 17 33 75 66 71 - 262 4',
            '** BANDUNG **',
            'BRAGA XXI 3 124 - - - - - - 28 28 -',
            'TOTAL 290 4',
        ]);
    }

    public function test_parser_uses_the_remote_extractor_when_configured(): void
    {
        Http::fake(['extract.example.test/*' => Http::response([
            'ok' => true,
            'text' => $this->layoutText(),
        ])]);

        $result = (new XxiPdfParser())->parse($this->pdf);

        $this->assertSame('2026-09-26', $result['report_date']);
        $this->assertSame(6, count($result['rows']));
        $this->assertSame('BLOK M XXI', $result['rows'][0]['source_cinema']);
        $this->assertSame(['ptn' => 290, 'fp' => 4], $result['source_totals']);
        $this->assertSame(1, count($result['pending_free_assignments']));

        Http::assertSent(function ($request) {
            $timestamp = $request->header('X-Pdf-Extract-Timestamp')[0] ?? '';
            $signature = $request->header('X-Pdf-Extract-Signature')[0] ?? '';
            $expected = hash_hmac('sha256', $timestamp."\n".hash('sha256', $request->body()), str_repeat('a', 48));

            return $request->url() === 'https://extract.example.test/extract'
                && $request->method() === 'POST'
                && preg_match('/^\d{10}$/', $timestamp) === 1
                && hash_equals($expected, $signature);
        });
    }

    public function test_remote_extractor_rejection_stays_explicit(): void
    {
        Http::fake(['extract.example.test/*' => Http::response([
            'ok' => false,
            'message' => 'pdftotext tidak tersedia pada VPS (/usr/bin/pdftotext).',
        ], 422)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('pdftotext tidak tersedia pada VPS');

        (new XxiPdfParser())->parse($this->pdf);
    }

    public function test_remote_extractor_failure_does_not_fall_back_silently(): void
    {
        Http::fake(['extract.example.test/*' => Http::response('', 503)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Layanan ekstraksi PDF gagal dihubungi (HTTP 503).');

        (new XxiPdfParser())->parse($this->pdf);
    }

    public function test_remote_extractor_connection_failure_is_reported_plainly(): void
    {
        Http::fake(['extract.example.test/*' => function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 7: Failed to connect');
        }]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Layanan ekstraksi PDF tidak dapat dihubungi (https://extract.example.test).');

        (new XxiPdfParser())->parse($this->pdf);
    }
}

