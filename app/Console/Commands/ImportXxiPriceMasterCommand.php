<?php

namespace App\Console\Commands;

use App\Models\CinemaTicketPrice;
use App\Models\KategoriBioskop;
use App\Models\MasterBioskop;
use App\Models\TypeTiket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportXxiPriceMasterCommand extends Command
{
    protected $signature = 'xxi:import-price-master {file : Workbook XLSX source} {--apply : Write mapped prices after preview} {--valid-from= : Effective date, default first day of current month}';
    protected $description = 'Preview or import Sheet1 XXI regular ticket prices by cinema and city.';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path) || !is_readable($path)) { $this->error('File workbook tidak dapat dibaca.'); return self::FAILURE; }
        $category = KategoriBioskop::whereRaw('UPPER(name) = ?', ['XXI'])->first();
        if (!$category) { $this->error('Kategori bioskop XXI belum tersedia.'); return self::FAILURE; }
        $ticket = TypeTiket::where('kategori', $category->uuid)->whereRaw('UPPER(name) = ?', ['REGULAR'])->first();
        if (!$ticket) { $this->error('Tipe tiket REGULAR untuk kategori XXI belum tersedia.'); return self::FAILURE; }
        $sheet = IOFactory::load($path)->getSheetByName('Sheet1');
        if (!$sheet) { $this->error('Sheet1 master harga tidak ditemukan.'); return self::FAILURE; }
        $validFrom = $this->option('valid-from') ?: now()->startOfMonth()->toDateString();
        $mapped=[]; $blocked=[];
        for ($row=3; $row <= $sheet->getHighestRow(); $row++) {
            $name=$this->normalizeLookup((string) $sheet->getCell('A'.$row)->getValue());
            $city=$this->normalizeLookup((string) $sheet->getCell('B'.$row)->getValue());
            if ($name === '') continue;
            $cinemas=MasterBioskop::where('type',$category->uuid)
                ->whereRaw("REGEXP_REPLACE(UPPER(TRIM(nama_bioskop)), '[[:space:]]+', ' ') = ?", [$name])
                ->whereRaw("REGEXP_REPLACE(UPPER(TRIM(kota)), '[[:space:]]+', ' ') = ?", [$city])
                ->get();
            if ($cinemas->count() !== 1) { $blocked[]="Baris $row: $name ($city) tidak memiliki tepat satu Master Bioskop XXI."; continue; }
            $mapped[]=['cinema'=>$cinemas->first(),'weekday'=>(float)$sheet->getCell('C'.$row)->getValue(),'friday'=>(float)$sheet->getCell('D'.$row)->getValue(),'weekend'=>(float)$sheet->getCell('E'.$row)->getValue()];
        }
        $this->info(count($mapped).' harga siap dipetakan; '.count($blocked).' baris diblokir.'); foreach ($blocked as $line) $this->warn($line);
        if (!$this->option('apply')) { $this->comment('Preview saja. Jalankan ulang dengan --apply setelah semua mapping dibenarkan.'); return $blocked ? self::FAILURE : self::SUCCESS; }
        if ($blocked) { $this->error('Tidak ada data ditulis karena masih ada mapping diblokir.'); return self::FAILURE; }
        foreach ($mapped as $item) {
            $overlap=CinemaTicketPrice::where('master_bioskop_uuid',$item['cinema']->uuid)->where('type_tiket_uuid',$ticket->uuid)->where('active',true)->overlapping($validFrom,null)->exists();
            if ($overlap) { $this->error('Periode harga bertabrakan untuk '.$item['cinema']->nama_bioskop.'. Tidak ada data tambahan ditulis.'); return self::FAILURE; }
        }
        DB::transaction(function () use ($mapped, $ticket, $validFrom) {
            foreach ($mapped as $item) CinemaTicketPrice::create(['uuid'=>(string)Str::uuid(),'master_bioskop_uuid'=>$item['cinema']->uuid,'type_tiket_uuid'=>$ticket->uuid,'weekday_price'=>$item['weekday'],'friday_price'=>$item['friday'],'weekend_holiday_price'=>$item['weekend'],'valid_from'=>$validFrom,'active'=>true]);
        });
        $this->info(count($mapped).' harga REGULAR XXI berhasil dibuat mulai '.$validFrom.'.'); return self::SUCCESS;
    }

    private function normalizeLookup(string $value): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\\s+/u', ' ', $value)), 'UTF-8');
    }
}
