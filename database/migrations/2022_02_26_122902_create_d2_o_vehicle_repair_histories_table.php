<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowVehicleRepairHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_vehicle_repair_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('car_id')->nullable();
            $table->string('amount')->nullable();
            $table->string('reason')->nullable();
            $table->string('type')->nullable();
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->string('date')->nullable();
            $table->string('description')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('paid_by')->nullable();
            $table->integer('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_vehicle_repair_histories');
    }
}
