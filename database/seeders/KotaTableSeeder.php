<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kota;
use App\Models\Province;
use App\Models\User;

class KotaTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      //use stream option for ssl verify false on file_get_contents function
      $stream_opts = [
      "ssl" => [
      "verify_peer"=>false,
      "verify_peer_name"=>false,
      ]];

      //url data
      // $url = "https://wilayah.id/api/regencies/[PROVINCE_CODE].json";
      $province = Province::select('uuid','nama','kode')->get();
      // Ask for mendownload data, default is no
        if ($this->command->confirm('Anda yakin mendownload data ?')) {
          foreach ($province as $key => $value) {
            $code = $value->kode;
            $url = "https://wilayah.id/api/regencies/". $code .".json";
            $json = file_get_contents($url, false, stream_context_create($stream_opts));
            $data = json_decode($json);
            $this->command->info('Downloading data kota from province '.$value->nama);
            //progress bar
            $this->command->getOutput()->createProgressBar(count($data->data));
            $this->command->getOutput()->progressStart();
            foreach ($data->data as $object) {
                Kota::create(array(
                  'nama' => $object->name,
                  'kode' => $object->code,
                  'provinsi_id' => $value->uuid,
                ));
                $this->command->getOutput()->progressAdvance();
            }
            $this->command->getOutput()->progressFinish();
            $this->command->info('Status: OK');
          }
          $this->command->warn('Data inserted to database. :)');
        }
    }
}
