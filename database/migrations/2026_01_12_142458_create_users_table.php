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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');

            $table->unsignedInteger('role_id')->nullable()->index()->comment('Roles table id');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
            $table->string('first_name', 50)->nullable()->comment('first name of user');
            $table->string('last_name', 50)->nullable()->comment('last name if user');
            $table->string('email', 320)->nullable()->unique()->index()->comment('email of user or party');
            $table->string('mobile_number', 15)->index()->nullable()->comment('number of user or party');
            $table->string('password', 255)->nullable();
            $table->char('status', 1)->index()->nullable()->default('Y')->comment('Y => Active, N => Inactive');
            $table->timestamp('last_login_at')->nullable()->index()->comment('last login date-time');
            $table->char('locale', 6)->index()->nullable()->default('en');
            $table->unsignedInteger('created_by')->nullable()->index()->comment('');
            $table->unsignedInteger('updated_by')->nullable()->comment('');
            $table->timestamps();
            $table->softDeletes()->index();

            // Composite indexes for common query patterns
            $table->index(['role_id', 'status'], 'users_role_status_index');
            $table->index(['status', 'deleted_at'], 'users_status_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
