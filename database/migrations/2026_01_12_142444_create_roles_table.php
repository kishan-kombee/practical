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
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');
            $table->string('name', 50)->nullable()->unique()->index()->comment('Role name');
            $table->char('status', 1)->index()->nullable()->default('Y')->comment('Y => Active, N => Inactive');
            $table->unsignedInteger('created_by')->nullable()->index()->comment('');
            $table->unsignedInteger('updated_by')->nullable()->comment('');
            $table->timestamps();
            $table->softDeletes()->index();

            // Composite index for filtering active roles
            $table->index(['status', 'deleted_at'], 'roles_status_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('roles');
    }
};
