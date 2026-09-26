<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportUploadHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('report_upload_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('uuid')->unique();
            $table->string('provider', 50)->index();
            $table->string('original_filename');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 40)->index();
            $table->unsignedInteger('preview_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->text('message')->nullable();
            $table->uuid('uploaded_by')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_upload_histories');
    }
}
