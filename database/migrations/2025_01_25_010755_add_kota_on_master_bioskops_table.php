<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKotaOnMasterBioskopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_bioskops', function (Blueprint $table){
            $table->string('kota')->nullable()->after('nama_bioskop');
            $table->string('no_telephone')->nullable()->after('kota');
            $table->string('type')->nullable()->before('kota');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_bioskop');
    }
}
