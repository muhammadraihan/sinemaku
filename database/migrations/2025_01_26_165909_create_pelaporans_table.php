<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePelaporansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pelaporans', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('kategori')->nullable();
            $table->string('kota')->nullable();
            $table->string('nama_bioskop')->nullable();
            $table->string('nama_film')->nullable();
            $table->date('tgl_tayang')->nullable();
            $table->time('jam_tayang')->nullable();
            $table->string('show')->nullable();
            $table->string('type_tiket')->nullable();
            $table->string('harga')->nullable();
            $table->string('jumlah')->nullable();
            $table->string('gross')->nullable();
            $table->string('tax')->nullable();
            $table->string('net')->nullable();
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pelaporans');
    }
}
