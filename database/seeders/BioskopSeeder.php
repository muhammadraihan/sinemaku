<?php

namespace Database\Seeders;

use Faker\Provider\Uuid as ProviderUuid;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Uuid;

class BioskopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Path ke file Excel
        $filePath = storage_path('app/daftar_bioskop.xlsx');

        // Baca file Excel
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $master_kategori = [
            ['uuid' => Uuid::generate(),'name' => 'XXI'],
            ['uuid' => Uuid::generate(),'name' => 'CGV'],
            ['uuid' => Uuid::generate(),'name' => 'CINEPOLIS']
        ];

        DB::table('kategori_bioskops')->insert($master_kategori);

        // Lewati header (baris pertama)
        foreach (array_slice($rows, 1) as $row) {
            $kategori = DB::table('kategori_bioskops')
                        ->select('uuid', 'name')
                        ->where('name', '=', $row[3])
                        ->first();
            
            DB::table('master_bioskops')->insert([
                'uuid' => Uuid::generate(),
                'nama_bioskop' => $row[0], // Sesuaikan dengan kolom Excel Anda
                'kota' => $row[1], // Sesuaikan dengan kolom Excel Anda
                'no_telephone' => $row[2], // Sesuaikan dengan kolom Excel Anda
                'type' => $kategori->uuid,
                'created_at' => now(),
            ]);
        }
    }
}
