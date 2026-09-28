<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarHolidaysTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();

            $table->index(['holiday_date', 'active'], 'calendar_holidays_lookup_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_holidays');
    }
}
