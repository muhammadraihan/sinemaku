<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTixAggregationFieldsToSeatmapTables extends Migration
{
    public function up()
    {
        Schema::table('seatmap_sources', function (Blueprint $table) {
            $table->string('method', 40)->default('browser_public')->after('provider');
            $table->string('health_status', 40)->default('pending')->after('status');
            $table->timestamp('last_discovery_at')->nullable()->after('health_status');
            $table->timestamp('last_snapshot_at')->nullable()->after('last_discovery_at');
            $table->timestamp('backoff_until')->nullable()->after('last_snapshot_at');
            $table->text('last_error_category')->nullable()->after('backoff_until');
            $table->text('last_error_message')->nullable()->after('last_error_category');
            $table->json('observed_chains')->nullable()->after('last_error_message');
        });

        Schema::table('seatmap_showtimes', function (Blueprint $table) {
            $table->string('chain', 40)->nullable()->after('source_uuid');
            $table->string('external_movie_id')->nullable()->after('external_showtime_id');
            $table->string('external_schedule_id')->nullable()->after('external_movie_id');
            $table->string('external_cinema_id')->nullable()->after('external_schedule_id');
            $table->string('canonical_key', 64)->nullable()->after('external_cinema_id');
            $table->string('normalized_film_name')->nullable()->after('film_name');
            $table->string('normalized_cinema_name')->nullable()->after('cinema_name');
            $table->string('timezone', 60)->default('Asia/Jakarta')->after('show_time');
            $table->json('source_metadata')->nullable()->after('booking_url');
            $table->boolean('snapshot_eligible')->default(false)->after('status');
            $table->index(['canonical_key', 'show_date']);
            $table->index(['chain', 'show_date', 'status']);
            $table->index(['snapshot_eligible', 'show_date', 'show_time']);
        });

        Schema::table('seatmap_snapshots', function (Blueprint $table) {
            $table->string('method', 40)->default('unavailable_only')->after('showtime_uuid');
            $table->string('confidence', 20)->default('low')->after('method');
            $table->boolean('is_comparable')->default(true)->after('confidence');
            $table->string('availability_event', 40)->nullable()->after('delta_occupied');
            $table->text('collection_warning')->nullable()->after('availability_event');
        });
    }

    public function down()
    {
        Schema::table('seatmap_snapshots', function (Blueprint $table) {
            $table->dropColumn(['method', 'confidence', 'is_comparable', 'availability_event', 'collection_warning']);
        });
        Schema::table('seatmap_showtimes', function (Blueprint $table) {
            $table->dropIndex(['canonical_key', 'show_date']);
            $table->dropIndex(['chain', 'show_date', 'status']);
            $table->dropIndex(['snapshot_eligible', 'show_date', 'show_time']);
            $table->dropColumn(['chain', 'external_movie_id', 'external_schedule_id', 'external_cinema_id', 'canonical_key', 'normalized_film_name', 'normalized_cinema_name', 'timezone', 'source_metadata', 'snapshot_eligible']);
        });
        Schema::table('seatmap_sources', function (Blueprint $table) {
            $table->dropColumn(['method', 'health_status', 'last_discovery_at', 'last_snapshot_at', 'backoff_until', 'last_error_category', 'last_error_message', 'observed_chains']);
        });
    }
}
