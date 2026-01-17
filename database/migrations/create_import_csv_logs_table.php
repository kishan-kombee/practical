<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_csv_logs', function (Blueprint $table) {
            $table->increments('id')->index()->comment('AUTO_INCREMENT');
            $table->string('file_name', 255)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('model_name', 255)->nullable();
            $table->unsignedInteger('user_id')->nullable()->index()->comment('User table id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->char('status', 1)->nullable()->comment('Y = Success, N = Fail, P = Pendind, S = Processing');
            $table->char('import_flag', 1)->nullable()->index()->comment('P = Pending, Y = Success');
            $table->string('voucher_email', 191)->nullable();
            $table->string('redirect_link', 191)->nullable();
            $table->unsignedInteger('no_of_rows')->nullable()->comment('No of csv rows');
            $table->longText('error_log')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_csv_logs');
    }
};
