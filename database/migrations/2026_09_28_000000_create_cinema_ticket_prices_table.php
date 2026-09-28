<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCinemaTicketPricesTable extends Migration
{
    public function up()
    {
        Schema::create('cinema_ticket_prices', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('master_bioskop_uuid');
            $table->string('type_tiket_uuid');
            $table->decimal('weekday_price', 15, 2);
            $table->decimal('friday_price', 15, 2);
            $table->decimal('weekend_holiday_price', 15, 2);
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();

            $table->index(['master_bioskop_uuid', 'type_tiket_uuid', 'active'], 'cinema_ticket_prices_lookup_index');
            $table->index(['valid_from', 'valid_until'], 'cinema_ticket_prices_validity_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cinema_ticket_prices');
    }
}
