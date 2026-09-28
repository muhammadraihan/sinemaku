<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Throwable;

class XxiPdfDoctorCommand extends Command
{
    protected $signature = 'xxi:pdf-doctor {--pdf= : Path PDF untuk uji ekstraksi}';

    protected $description = 'Periksa kesiapan ekstraksi PDF XXI (proc_open, pdftotext, dan hasil ekstraksi)';

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

        $binary = (string) config('services.pdftotext.binary', 'pdftotext');
        $this->line('Binary pdftotext: '.$binary);

        try {
            $version = new Process([$binary, '-v']);
            $version->setTimeout(15);
            $version->run();
            $this->line('<info>OK</info> pdftotext dapat dijalankan: '.trim($version->getOutput().$version->getErrorOutput()));
        } catch (Throwable $exception) {
            $this->line('<error>GAGAL</error> pdftotext tidak dapat dijalankan: '.$exception->getMessage());
            $this->line('Minta penyedia hosting memasang poppler-utils atau isi path absolutnya pada XXL_PDFTOTEXT_BINARY.');

            return self::FAILURE;
        }

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
