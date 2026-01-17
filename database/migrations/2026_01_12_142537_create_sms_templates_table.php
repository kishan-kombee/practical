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
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->increments('id')->unique()->index()->comment('AUTO_INCREMENT');
            $table->string('type', 100)->index()->nullable()->comment('SMS type');
            $table->string('label', 100)->nullable()->comment('Menu display name');
            $table->longText('message')->nullable()->comment('Message');
            $table->string('dlt_message_id', 200)->nullable()->comment('DLT Template ID');
            $table->char('status', 1)->nullable()->default('Y')->comment('Y => Active, N => InActive');
            $table->unsignedInteger('created_by')->nullable()->comment('');
            $table->unsignedInteger('updated_by')->nullable()->comment('');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('sms_templates');
    }
};
