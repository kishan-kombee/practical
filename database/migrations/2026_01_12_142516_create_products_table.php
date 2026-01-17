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
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');
            $table->string('item_code', 191)->nullable()->index();
            $table->string('name', 191)->nullable()->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('category_id')->index()->nullable()->comment('Category table ID');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
            $table->unsignedInteger('sub_category_id')->index()->nullable()->comment('SubCategory table ID');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('restrict');
            $table->enum('available_status', [0, 1])->nullable()->index()->comment("0 => 'Not-available', 1 => 'Available'");
            $table->integer('quantity')->nullable()->index();
            $table->unsignedInteger('created_by')->nullable()->index()->comment('');
            $table->unsignedInteger('updated_by')->nullable()->comment('');
            $table->timestamps();
            $table->softDeletes()->index();

            // Composite indexes for common query patterns
            $table->index(['category_id', 'sub_category_id'], 'products_category_subcategory_index');
            $table->index(['available_status', 'deleted_at'], 'products_status_deleted_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
