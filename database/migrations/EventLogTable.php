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
        Schema::create('event_logs', function (Blueprint $table) {
            $table->increments('id')->comment('AUTO_INCREMENT');
            $table->string('event_name', 255)->nullable();
            $table->timestamp('execution_time')->nullable()->useCurrent()->comment('CURRENT_TIMESTAMP');
            $table->text('message')->nullable();
            $table->char('type', 1)->comment('1 = Mysql Event Cron, 2 = PHP Laravel Scheduler Cron');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_logs');
    }
};
