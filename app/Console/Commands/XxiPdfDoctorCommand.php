<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

class XxiPdfDoctorCommand extends Command
{
    protected $signature = 'xxi:pdf-doctor {--pdf= : Path PDF untuk uji ekstraksi}';

    protected $description = 'Periksa kesiapan ekstraksi PDF XXI (proc_open, pdftotext, dan hasil ekstraksi)';

    private function finish(\App\Services\Reports\RemotePdfTextExtractor $remote): int
    {
        $pdf = (string) $this->option('pdf');
        if ($pdf === '') {
            $this->line('Uji ekstraksi dilewati (tanpa --pdf).');

            return self::SUCCESS;
        }

        if (!is_readable($pdf)) {
            $this->line('<error>GAGAL</error> File tidak dapat dibaca: '.$pdf);

            return self::FAILURE;
        }

        try {
            $result = app(\App\Services\Reports\XxiPdfParser::class)->parse($pdf);
            $this->line('<info>OK</info> Ekstraksi berhasil: '.count($result['rows']).' baris, tanggal '.$result['report_date'].'.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('<error>GAGAL</error> '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function binaryIsUsable(string $binary): bool
    {
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

    private function candidateBinaries(): array
    {
        return array_values(array_filter(array_unique([
            (string) config('services.pdftotext.binary', 'pdftotext'),
            'pdftotext',
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
            '/opt/homebrew/bin/pdftotext',
            '/usr/local/poppler/bin/pdftotext',
            '/usr/local/poppler-utils/bin/pdftotext',
        ])));
    }

    public function handle(): int
    {
        $this->line('PHP '.PHP_VERSION.' ('.PHP_SAPI.')');

        $disabled = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        $required = ['proc_open', 'escapeshellarg'];
        $blocked = array_values(array_intersect($required, $disabled));
        $this->line('disable_functions: '.($disabled ? implode(', ', $disabled) : '(kosong)'));
        $this->line(function_exists('proc_open') ? '<info>OK</info> proc_open tersedia.' : '<error>GAGAL</error> proc_open tidak tersedia.');
        if ($blocked) {
            $this->line('<error>GAGAL</error> Fungsi dinonaktifkan: '.implode(', ', $blocked));
        }

        $remote = app(\App\Services\Reports\RemotePdfTextExtractor::class);
        if ($remote->configured()) {
            $this->line('Mode ekstraksi: layanan VPS ('.config('services.pdf_extract.url').')');
        } else {
            $this->line('Mode ekstraksi: binary lokal');
        }

        $binary = (string) config('services.pdftotext.binary', 'pdftotext');
        $this->line('Binary pdftotext: '.$binary);

        if ($remote->configured()) {
            return $this->finish($remote);
        }

        if (!$this->binaryIsUsable($binary)) {
            $this->line('<error>GAGAL</error> Binary pdftotext tidak ditemukan atau tidak dapat dijalankan: '.$binary);

            foreach ($this->candidateBinaries() as $candidate) {
                if ($candidate !== $binary && $this->binaryIsUsable($candidate)) {
                    $this->line('<info>PETUNJUK</info> Kandidat yang berfungsi: '.$candidate);
                    $this->line('Setel XXI_PDFTOTEXT_BINARY='.$candidate.' pada .env lalu jalankan ulang perintah ini.');
                }
            }

            $this->line('Jika tidak ada kandidat, minta hosting memasang poppler-utils (shared hosting Hostinger tidak menyediakannya).');

            return self::FAILURE;
        }

        $this->line('<info>OK</info> pdftotext dapat dijalankan: '.trim($this->binaryVersion($binary)));

        $pdf = (string) $this->option('pdf');
        if ($pdf === '') {
            $this->line('Uji ekstraksi dilewati (tanpa --pdf).');

            return self::SUCCESS;
        }

        if (!is_readable($pdf)) {
            $this->line('<error>GAGAL</error> File tidak dapat dibaca: '.$pdf);

            return self::FAILURE;
        }

        try {
            $result = app(\App\Services\Reports\XxiPdfParser::class)->parse($pdf);
            $this->line('<info>OK</info> Ekstraksi berhasil: '.count($result['rows']).' baris, tanggal '.$result['report_date'].'.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->line('<error>GAGAL</error> '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
