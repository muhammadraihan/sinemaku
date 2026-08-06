<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateMasterFilmsTable extends Migration
{
    public function up()
    {
        Schema::create('master_films', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name')->unique();
            // Data lama yang tidak memiliki tanggal tetap ikut masuk saat backfill.
            // Form Master Film tetap mewajibkan tanggal untuk data baru/perubahan.
            $table->date('tgl_tayang')->nullable();
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('pelaporans')) {
            return;
        }

        $now = now();
        DB::table('pelaporans')
            ->selectRaw('UPPER(TRIM(nama_film)) AS name, MIN(tgl_tayang) AS tgl_tayang')
            ->whereNotNull('nama_film')
            ->whereRaw("TRIM(nama_film) <> ''")
            ->groupBy(DB::raw('UPPER(TRIM(nama_film))'))
            ->orderBy('name')
            ->chunk(500, function ($films) use ($now) {
                $rows = [];

                foreach ($films as $film) {
                    $rows[] = [
                        'uuid' => (string) Str::uuid(),
                        'name' => $film->name,
                        'tgl_tayang' => $film->tgl_tayang,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows) {
                    DB::table('master_films')->insert($rows);
                }
            });
    }

    public function down()
    {
        Schema::dropIfExists('master_films');
    }
}
