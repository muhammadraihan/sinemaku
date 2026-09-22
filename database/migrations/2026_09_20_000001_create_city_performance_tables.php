<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCityPerformanceTables extends Migration
{
    public function up()
    {
        Schema::create('city_performance_sources', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('key')->unique();
            $table->string('name');
            $table->text('public_url')->nullable();
            $table->text('attribution')->nullable();
            $table->string('capability_version')->nullable();
            $table->unsignedInteger('rate_limit_per_minute')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('city_performance_cities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('province')->nullable();
            $table->timestamps();
        });

        Schema::create('city_performance_cinemas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('city_id');
            $table->string('source_cinema_id');
            $table->string('name');
            $table->string('chain')->nullable();
            $table->timestamps();
            $table->foreign('source_id')->references('id')->on('city_performance_sources');
            $table->foreign('city_id')->references('id')->on('city_performance_cities');
            $table->unique(['source_id', 'source_cinema_id'], 'city_perf_cinema_source_identity_unique');
            $table->index(['city_id', 'chain']);
        });

        Schema::create('city_performance_movie_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('source_id');
            $table->string('source_movie_id');
            $table->string('source_title');
            $table->unsignedBigInteger('master_film_id')->nullable();
            $table->string('cinepoint_source_movie_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('match_method', 30)->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('source_id')->references('id')->on('city_performance_sources');
            $table->unique(['source_id', 'source_movie_id'], 'city_perf_movie_source_identity_unique');
            $table->index(['status', 'cinepoint_source_movie_id'], 'city_perf_mapping_status_movie_idx');
        });

        Schema::create('city_performance_collection_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('source_id');
            $table->date('period_date');
            $table->string('method_version', 40);
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('requested_city_count')->default(0);
            $table->unsignedInteger('collected_city_count')->default(0);
            $table->unsignedInteger('failed_city_count')->default(0);
            $table->unsignedInteger('observed_row_count')->default(0);
            $table->unsignedInteger('valid_row_count')->default(0);
            $table->decimal('coverage_ratio', 8, 6)->nullable();
            $table->decimal('valid_row_ratio', 8, 6)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->foreign('source_id')->references('id')->on('city_performance_sources');
            $table->index(['period_date', 'status']);
            $table->index(['source_id', 'period_date']);
        });

        Schema::create('city_performance_city_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('collection_run_id');
            $table->unsignedBigInteger('city_id');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('observed_row_count')->default(0);
            $table->unsignedInteger('valid_row_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->foreign('collection_run_id')->references('id')->on('city_performance_collection_runs')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city_performance_cities');
            $table->unique(['collection_run_id', 'city_id'], 'city_perf_run_city_unique');
            $table->index(['status', 'city_id']);
        });

        Schema::create('city_performance_showtimes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('collection_run_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('cinema_id');
            $table->unsignedBigInteger('movie_mapping_id')->nullable();
            $table->string('source_movie_id');
            $table->string('identity_hash', 64);
            $table->date('show_date');
            $table->time('starts_at');
            $table->string('studio')->nullable();
            $table->string('format')->nullable();
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('weight', 10, 4)->default(1);
            $table->json('source_metadata')->nullable();
            $table->timestamps();
            $table->foreign('collection_run_id')->references('id')->on('city_performance_collection_runs')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city_performance_cities');
            $table->foreign('cinema_id')->references('id')->on('city_performance_cinemas');
            $table->foreign('movie_mapping_id')->references('id')->on('city_performance_movie_mappings');
            $table->unique(['collection_run_id', 'identity_hash'], 'city_perf_run_showtime_identity_unique');
            $table->index(['show_date', 'source_movie_id']);
            $table->index(['city_id', 'cinema_id']);
        });

        Schema::create('city_performance_estimates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('collection_run_id');
            $table->unsignedBigInteger('city_id');
            $table->unsignedBigInteger('movie_mapping_id');
            $table->string('cinepoint_source_movie_id');
            $table->string('method_version', 40);
            $table->decimal('weighted_showtimes', 14, 4);
            $table->decimal('city_share', 10, 8);
            $table->unsignedBigInteger('national_daily_admissions');
            $table->unsignedBigInteger('estimated_admissions_lower');
            $table->unsignedBigInteger('estimated_admissions_mid');
            $table->unsignedBigInteger('estimated_admissions_upper');
            $table->decimal('lpi', 10, 4)->nullable();
            $table->string('lpi_label', 20)->nullable();
            $table->string('confidence', 20);
            $table->decimal('coverage_ratio', 8, 6);
            $table->timestamps();
            $table->foreign('collection_run_id')->references('id')->on('city_performance_collection_runs')->onDelete('cascade');
            $table->foreign('city_id')->references('id')->on('city_performance_cities');
            $table->foreign('movie_mapping_id')->references('id')->on('city_performance_movie_mappings');
            $table->unique(['collection_run_id', 'city_id', 'movie_mapping_id'], 'city_perf_estimate_identity_unique');
            $table->index(['confidence', 'city_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('city_performance_estimates');
        Schema::dropIfExists('city_performance_showtimes');
        Schema::dropIfExists('city_performance_city_runs');
        Schema::dropIfExists('city_performance_collection_runs');
        Schema::dropIfExists('city_performance_movie_mappings');
        Schema::dropIfExists('city_performance_cinemas');
        Schema::dropIfExists('city_performance_cities');
        Schema::dropIfExists('city_performance_sources');
    }
}
