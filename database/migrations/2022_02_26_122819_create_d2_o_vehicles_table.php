<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_vehicles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('year')->nullable();
            $table->string('reg_no')->nullable();
            $table->string('imei')->nullable();
            $table->string('chasis_no')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->string('transmission_type')->nullable();
            $table->string('car_picture')->nullable();
            $table->string('image1')->nullable();
            $table->string('image2')->nullable();
            $table->string('image3')->nullable();
            $table->string('image4')->nullable();
            $table->string('image5')->nullable();
            $table->string('vehicle_cost')->nullable();
            $table->string('initial_amount')->nullable();
            $table->integer('maintenance_date')->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('insurance_file')->nullable();
            $table->timestamp('insurance_expire')->nullable();
            $table->string('road_worthy_file')->nullable();
            $table->timestamp('road_worthy_expire')->nullable();
            $table->integer('due_length')->nullable();
            $table->string('monthly_due')->nullable();
            $table->integer('due_date')->nullable();
            $table->string('status')->nullable();
            $table->integer('driver_id');
            $table->integer('official_id');
            $table->timestamp('allocated_date')->nullable();
            $table->integer('supplier_id');
            $table->timestamps();
        });

        Schema::table('official_drivers', function (Blueprint $table) {
            
            $table->integer('vehicle_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_vehicles');
    }
}
