<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowVehiclePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_vehicle_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('supplier_id')->nullable();
            $table->integer('car_id')->nullable();
            $table->string('amount')->nullable();
            $table->integer('status')->nullable();
            $table->integer('approved_by')->nullable();
            $table->string('due_on')->nullable();
            $table->timestamp('paid_on')->nullable();
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
        Schema::dropIfExists('drivenow_vehicle_payments');
    }
}
