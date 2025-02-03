<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Uuid;

class KapasitasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filePath = storage_path('app/kapasitas_xxi.xlsx');

        // Baca file Excel
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // $master_kategori = [
        //     ['uuid' => Uuid::generate(),'name' => 'XXI'],
        //     ['uuid' => Uuid::generate(),'name' => 'CGV'],
        //     ['uuid' => Uuid::generate(),'name' => 'CINEPOLIS']
        // ];

        // DB::table('kategori_bioskops')->insert($master_kategori);

        $get_kategori = DB::table('kategori_bioskops')
                            ->select('uuid')
                            ->where('name', '=', 'XXI')
                            ->first();
        $kategori = $get_kategori->uuid;

        // Lewati header (baris pertama)
        foreach (array_slice($rows, 1) as $row) {
            $bioskop = DB::table('master_bioskops')
                        ->select('uuid', 'nama_bioskop', 'kota')
                        ->where('kota', '=', $row[0])
                        ->where('nama_bioskop', '=', $row[1])
                        ->first();

            if(!empty($bioskop)){
                $get_type = DB::table('type_tikets')
                                ->select('uuid')
                                ->where('kategori', $kategori)
                                ->where('name', $row[2])
                                ->first();
                $type_tiket = $get_type->uuid;

                DB::table('kapasitas')->insert([
                    'uuid' => Uuid::generate(),
                    'kategori' => $kategori,
                    'kota' => $row[0],
                    'nama_bioskop' => $bioskop->uuid,
                    'type_tiket' => $type_tiket,
                    'studio' => $row[3],
                    'kapasitas' => $row[4],
                    'created_at' => now()
                ]);
            }else{
                DB::table('kapasitas')->insert([
                    'uuid' => Uuid::generate(),
                    'kategori' => $kategori,
                    'kota' => $row[0],
                    'nama_bioskop' => $row[1],
                    'type_tiket' => $row[2],
                    'studio' => $row[3],
                    'kapasitas' => $row[4],
                    'created_at' => now()
                ]);
            }
        }
    }
}
