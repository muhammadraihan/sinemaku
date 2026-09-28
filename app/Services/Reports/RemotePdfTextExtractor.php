<?php

namespace App\Services\Reports;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Extracts PDF text from a remote extraction service.
 *
 * Shared hosting cannot install Poppler, so the VPS runs a small authenticated
 * service that shells out to `pdftotext -layout` and returns plain text.
 * Used only when `services.pdf_extract.url` is configured; otherwise the
 * parser falls back to a local binary.
 */
class RemotePdfTextExtractor
{
    public function configured(): bool
    {
        return (string) config('services.pdf_extract.url', '') !== ''
            && (string) config('services.pdf_extract.secret', '') !== '';
    }

    public function extract(string $pdfPath): string
    {
        $contents = @file_get_contents($pdfPath);
        if ($contents === false || $contents === '') {
            throw new RuntimeException('File PDF tidak dapat dibaca untuk dikirim ke layanan ekstraksi.');
        }

        $timestamp = (string) time();
        $signature = hash_hmac('sha256', $timestamp."\n".hash('sha256', $contents), (string) config('services.pdf_extract.secret'));

        try {
            $response = Http::withHeaders([
                'X-Pdf-Extract-Timestamp' => $timestamp,
                'X-Pdf-Extract-Signature' => $signature,
                'Content-Type' => 'application/pdf',
                'Accept' => 'application/json',
            ])->timeout((int) config('services.pdf_extract.timeout', 90))
                ->withBody($contents, 'application/pdf')
                ->post(rtrim((string) config('services.pdf_extract.url'), '/').'/extract');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Layanan ekstraksi PDF tidak dapat dihubungi ('.rtrim((string) config('services.pdf_extract.url'), '/').'). Periksa layanan di VPS masih hidup, lalu coba lagi.');
        } catch (Throwable $exception) {
            throw new RuntimeException('Layanan ekstraksi PDF gagal dihubungi: '.$exception->getMessage());
        }

        if ($response->status() === 422) {
            throw new RuntimeException((string) ($response->json('message') ?: 'Layanan ekstraksi menolak PDF ini.'));
        }

        if (!$response->successful()) {
            throw new RuntimeException('Layanan ekstraksi PDF gagal dihubungi (HTTP '.$response->status().').');
        }

        $text = (string) $response->json('text', '');
        if (trim($text) === '') {
            throw new RuntimeException('Layanan ekstraksi tidak mengembalikan teks.');
        }

        return $text;
    }
}
