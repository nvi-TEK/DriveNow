<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowVehicleSuppliersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_vehicle_suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('image')->nullable();
            $table->string('contact')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('acc_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->integer('status')->default(0);
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
        Schema::dropIfExists('drivenow_vehicle_suppliers');
    }
}
