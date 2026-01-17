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
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');
            $table->string('name', 191)->nullable()->index();
            $table->enum('status', [0, 1])->nullable()->index()->comment("0 => 'Inactive', 1 => 'Active'");
            $table->unsignedInteger('created_by')->nullable()->index()->comment('');
            $table->unsignedInteger('updated_by')->nullable()->comment('');
            $table->timestamps();
            $table->softDeletes()->index();

            // Composite index for filtering active categories
            $table->index(['status', 'deleted_at'], 'categories_status_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
