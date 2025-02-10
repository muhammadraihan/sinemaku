<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKapasitasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kapasitases', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('kategori')->nullable();
            $table->string('nama_bioskop')->nullable();
            $table->string('kota')->nullable();
            $table->string('type_tiket')->nullable();
            $table->string('studio')->nullable();
            $table->string('kapasitas')->nullable();
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
        Schema::dropIfExists('kapasitases');
    }
}
