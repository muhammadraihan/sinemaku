<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCinepointRemoteCollectionContract extends Migration
{
    public function up()
    {
        Schema::table('cinepoint_daily_snapshots', function (Blueprint $table) {
            $table->string('payload_fingerprint', 64)->nullable()->after('period_date');
            $table->unique('payload_fingerprint', 'cinepoint_snapshots_payload_fingerprint_unique');
        });
        Schema::create('cinepoint_sync_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('status', 20)->default('queued');
            $table->unsignedTinyInteger('active_slot')->nullable()->unique();
            $table->string('scheduled_slot', 32)->nullable()->unique();
            $table->string('lease_token_hash', 64)->nullable();
            $table->timestamp('lease_expires_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('failure_reason')->nullable();
            $table->unsignedBigInteger('snapshot_id')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'requested_at']);
            $table->foreign('snapshot_id')->references('id')->on('cinepoint_daily_snapshots')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cinepoint_sync_requests');
        Schema::table('cinepoint_daily_snapshots', function (Blueprint $table) {
            $table->dropUnique('cinepoint_snapshots_payload_fingerprint_unique');
            $table->dropColumn('payload_fingerprint');
        });
    }
}
