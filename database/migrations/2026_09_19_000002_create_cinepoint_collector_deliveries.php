<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCinepointCollectorDeliveries extends Migration
{
    public function up()
    {
        Schema::create('cinepoint_collector_deliveries', function (Blueprint $table) {
            $table->string('delivery_id', 32)->primary();
            $table->string('request_fingerprint', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cinepoint_collector_deliveries');
    }
}
