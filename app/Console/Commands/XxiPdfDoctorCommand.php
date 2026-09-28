<?php

namespace App\Console\Commands;

use App\Services\Reports\PdfTextExtractor;
use App\Services\Reports\RemotePdfTextExtractor;
use App\Services\Reports\XxiPdfParser;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

class XxiPdfDoctorCommand extends Command
{
    protected $signature = 'xxi:pdf-doctor {--pdf= : Path PDF untuk uji ekstraksi (boleh nama berkas saja bila diletakkan di storage/app)}';

    protected $description = 'Periksa kesiapan ekstraksi PDF XXI (proc_open, pdftotext, ekstraktor bawaan, dan hasil ekstraksi)';

    public function handle(): int
    {
        $this->line('PHP '.PHP_VERSION.' ('.PHP_SAPI.')');

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        $blocked = array_values(array_intersect(['proc_open', 'escapeshellarg'], $disabled));
        $this->line('disable_functions: '.($disabled ? implode(', ', $disabled) : '(kosong)'));
        $this->line(function_exists('proc_open')
            ? '<info>OK</info> proc_open tersedia.'
            : '<error>GAGAL</error> proc_open tidak tersedia (hanya dibutuhkan bila memakai binary pdftotext).');
        if ($blocked) {
            $this->line('<error>GAGAL</error> Fungsi dinonaktifkan: '.implode(', ', $blocked));
        }

        $builtin = (bool) config('services.pdftotext.builtin', true);
        $remote = app(RemotePdfTextExtractor::class);
        $binary = (string) config('services.pdftotext.binary', 'pdftotext');
        $binaryOk = $this->binaryIsUsable($binary);

        if ($binaryOk) {
            $this->line('Jalur 1 (binary pdftotext): <info>OK</info> '.$binary.' — '.trim($this->firstLine($this->binaryVersion($binary))));
        } else {
            $this->line('Jalur 1 (binary pdftotext): tidak tersedia ('.$binary.')');
        }

        $this->line($builtin
            ? 'Jalur 2 (ekstraktor bawaan PHP): <info>AKTIF</info> — tidak butuh pdftotext maupun VPS'
            : 'Jalur 2 (ekstraktor bawaan PHP): dimatikan (XXI_PDFTOTEXT_BUILTIN=false)');

        $this->line($remote->configured()
            ? 'Jalur 3 (layanan VPS): <info>AKTIF</info> — '.config('services.pdf_extract.url')
            : 'Jalur 3 (layanan VPS): tidak dikonfigurasi');

        if (!$binaryOk && !$builtin && !$remote->configured()) {
            $this->line('');
            $this->line('<error>GAGAL</error> Tidak ada jalur ekstraksi yang aktif. Aktifkan XXI_PDFTOTEXT_BUILTIN=true atau konfigurasikan layanan VPS.');

            return self::FAILURE;
        }

        $this->line('');
        $this->line('Cara memakai: '.$this->signatureHint());

        $pdf = (string) $this->option('pdf');
        if ($pdf === '') {
            $this->line('');
            $this->line('Uji ekstraksi dilewati (tanpa --pdf). Kesiapan lingkungan di atas sudah cukup untuk memastikan import dapat berjalan.');
            $this->lineAvailability();

            return self::SUCCESS;
        }

        $resolved = $this->resolvePdfPath($pdf);
        if ($resolved === null) {
            $this->line('');
            $this->line('<error>GAGAL</error> Berkas tidak ditemukan atau tidak dapat dibaca: '.$pdf);
            $this->line('Dibaca dari: '.$pdf.' (relatif terhadap '.base_path().')');
            $this->lineAvailability();

            return self::FAILURE;
        }

        $this->line('Berkas uji: '.$resolved);

        try {
            $result = app(XxiPdfParser::class)->parse($resolved);
            $this->line('');
            $this->line('<info>OK</info> Ekstraksi berhasil: '.count($result['rows']).' baris, tanggal '.$result['report_date'].'.');
            $this->line('Import PDF XXI siap dipakai.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('');
            $this->line('<error>GAGAL</error> '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Accept an absolute path, a path relative to the project, or a bare file
     * name dropped into storage/app so the operator never has to guess where
     * the hosting account's home directory is.
     */
    private function resolvePdfPath(string $pdf): ?string
    {
        $candidates = [$pdf];

        if (!str_starts_with($pdf, DIRECTORY_SEPARATOR)) {
            $candidates[] = storage_path('app/'.$pdf);
            $candidates[] = base_path($pdf);
            $candidates[] = storage_path('app/private/'.$pdf);
        }

        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real !== false && is_file($real) && is_readable($real)) {
                return $real;
            }
        }

        return null;
    }

    private function signatureHint(): string
    {
        return 'php artisan xxi:pdf-doctor --pdf=laporan-xxi.pdf';
    }

    /**
     * Show exactly which folders are acceptable, with their real absolute
     * paths, so the operator can copy one instead of guessing.
     */
    private function lineAvailability(): void
    {
        $this->line('');
        $this->line('Tempel PDF Anda di salah satu folder ini, lalu sebutkan nama berkasnya saja:');

        foreach ([storage_path('app'), base_path()] as $directory) {
            $this->line('  '.$directory.(is_dir($directory) ? ' (ada)' : ' (tidak ada)'));
        }

        $this->line('');
        $this->line('Contoh lengkap:');
        $this->line('  php artisan xxi:pdf-doctor --pdf=laporan-xxi.pdf');
        $this->line('  php artisan xxi:pdf-doctor --pdf='.storage_path('app/laporan-xxi.pdf'));
    }

    private function firstLine(string $value): string
    {
        $lines = preg_split('/\R/', trim($value));

        return (string) ($lines[0] ?? '');
    }

    private function binaryIsUsable(string $binary): bool
    {
        if (!function_exists('proc_open') || $binary === '') {
            return false;
        }

        try {
            $process = new Process([$binary, '-v']);
            $process->setTimeout(15);
            $process->run();
        } catch (Throwable $exception) {
            return false;
        }

        $output = $process->getOutput().$process->getErrorOutput();

        return $process->getExitCode() !== 127
            && preg_match('/(?:not found|command not found|No such file)/i', $output) !== 1;
    }

    private function binaryVersion(string $binary): string
    {
        $process = new Process([$binary, '-v']);
        $process->setTimeout(15);
        $process->run();

        return $process->getOutput().$process->getErrorOutput();
    }
}
