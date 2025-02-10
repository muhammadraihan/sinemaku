<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Province;

class ProvinceTableSeeder extends Seeder
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
      $url = "https://wilayah.id/api/provinces.json";
      $json = file_get_contents($url, false, stream_context_create($stream_opts));
      // Ask for mendownload data, default is no
        if ($this->command->confirm('Anda yakin mendownload data ?')) {
            $data = json_decode($json);
            // dd($data);
            //progress bar
            $this->command->getOutput()->createProgressBar(count($data->data));
            $this->command->getOutput()->progressStart();
            foreach ($data->data as $object) {
                Province::create(array(
                  'nama' => $object->name,
                  'kode' => $object->code,
                ));
                $this->command->getOutput()->progressAdvance();
            }
            $this->command->getOutput()->progressFinish();
            $this->command->info('Here is your datasource:');
            $this->command->warn($url);
            $this->command->info('Status:');
            $this->command->warn('Data inserted to database. :)');
        }
    }
}