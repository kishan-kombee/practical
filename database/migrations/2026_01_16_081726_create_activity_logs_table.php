<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');
            $table->string('log_name', 191)->nullable()->index()->comment('Name of the log (e.g., model name)');
            $table->text('description')->nullable()->comment('Description of the activity');
            $table->string('subject_type', 191)->nullable()->index()->comment('Model class name (e.g., App\Models\User)');
            $table->unsignedInteger('subject_id')->nullable()->index()->comment('ID of the subject model');
            $table->string('event', 50)->nullable()->index()->comment('Event type: created, updated, deleted');
            $table->string('causer_type', 191)->nullable()->index()->comment('User model class name');
            $table->unsignedInteger('causer_id')->nullable()->index()->comment('ID of the user who performed the action');
            $table->text('properties')->nullable()->comment('JSON data of old and new values');
            $table->string('ip_address', 45)->nullable()->index()->comment('IP address of the user');
            $table->string('user_agent', 500)->nullable()->comment('User agent string');
            $table->string('url', 500)->nullable()->comment('URL where the action was performed');
            $table->string('method', 10)->nullable()->comment('HTTP method (GET, POST, etc.)');
            $table->unsignedTinyInteger('response_type')->nullable()->comment('HTTP response type (200, 201, etc.)');
            $table->timestamps();
            $table->softDeletes()->index();

            // Composite indexes for common query patterns
            $table->index(['subject_type', 'subject_id'], 'activity_logs_subject_index');
            $table->index(['causer_type', 'causer_id'], 'activity_logs_causer_index');
            $table->index(['event', 'created_at'], 'activity_logs_event_created_index');
            $table->index(['log_name', 'created_at'], 'activity_logs_logname_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
