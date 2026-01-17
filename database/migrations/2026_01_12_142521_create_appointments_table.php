<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');
            $table->string('patient_name', 50)->nullable()->index()->comment('Patient Name');
            $table->string('clinic_location', 200)->nullable()->index()->comment('Clinic Location');
            $table->unsignedInteger('clinician_id')->index()->nullable()->comment('Clinician (User ID)');
            $table->foreign('clinician_id')->references('id')->on('users')->onDelete('restrict');
            $table->date('appointment_date')->nullable()->index()->comment('Appointment Date');
            $table->char('status', 1)->index()->nullable()->default('B')->comment('B => Booked, D => Completed, N => Cancelled');
            $table->unsignedInteger('created_by')->nullable()->index()->comment('');
            $table->unsignedInteger('updated_by')->nullable()->comment('');
            $table->timestamps();
            $table->softDeletes()->index();

            // Composite indexes for common query patterns
            $table->index(['clinician_id', 'status'], 'appointments_clinician_status_index');
            $table->index(['appointment_date', 'status'], 'appointments_date_status_index');
            $table->index(['status', 'deleted_at'], 'appointments_status_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('appointments');
    }
};
