<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSeatmapMonitoringTables extends Migration
{
    public function up()
    {
        Schema::create('seatmap_sources', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('provider');
            $table->string('label');
            $table->string('base_url')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();
        });

        Schema::create('seatmap_source_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('source_uuid');
            $table->string('label');
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->text('access_token')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            $table->text('last_error')->nullable();
            $table->string('created_by')->nullable();
            $table->string('edited_by')->nullable();
            $table->timestamps();

            $table->index(['source_uuid', 'status']);
        });

        Schema::create('seatmap_showtimes', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('source_uuid');
            $table->string('external_showtime_id')->nullable();
            $table->string('film_name');
            $table->string('cinema_name');
            $table->string('city')->nullable();
            $table->string('screen_name')->nullable();
            $table->date('show_date');
            $table->time('show_time');
            $table->string('booking_url')->nullable();
            $table->unsignedInteger('total_seats')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['source_uuid', 'show_date', 'film_name']);
        });

        Schema::create('seatmap_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('showtime_uuid');
            $table->timestamp('captured_at');
            $table->unsignedInteger('total_seats')->nullable();
            $table->unsignedInteger('available_seats')->nullable();
            $table->unsignedInteger('unavailable_seats')->nullable();
            $table->unsignedInteger('sold_seats')->nullable();
            $table->unsignedInteger('reserved_seats')->nullable();
            $table->unsignedInteger('blocked_seats')->nullable();
            $table->unsignedInteger('unknown_seats')->nullable();
            $table->decimal('estimated_occupancy_percent', 8, 2)->nullable();
            $table->unsignedInteger('delta_occupied')->nullable();
            $table->string('raw_hash')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['showtime_uuid', 'captured_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('seatmap_snapshots');
        Schema::dropIfExists('seatmap_showtimes');
        Schema::dropIfExists('seatmap_source_accounts');
        Schema::dropIfExists('seatmap_sources');
    }
}
