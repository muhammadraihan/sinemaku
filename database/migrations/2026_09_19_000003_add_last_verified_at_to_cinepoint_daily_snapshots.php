<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastVerifiedAtToCinepointDailySnapshots extends Migration
{
    public function up()
    {
        Schema::table('cinepoint_daily_snapshots', function (Blueprint $table) {
            $table->timestamp('last_verified_at')->nullable()->after('finished_at');
            $table->index('last_verified_at');
        });
    }

    public function down()
    {
        Schema::table('cinepoint_daily_snapshots', function (Blueprint $table) {
            $table->dropIndex(['last_verified_at']);
            $table->dropColumn('last_verified_at');
        });
    }
}
