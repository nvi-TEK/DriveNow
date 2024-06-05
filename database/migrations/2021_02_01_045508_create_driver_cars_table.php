<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverCarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_cars', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id');
            $table->string('car_registration')->nullable();
            $table->string('car_make')->nullable();
            $table->string('car_model')->nullable();
            $table->string('car_picture')->nullable();
            $table->string('mileage')->nullable();
            $table->string('car_make_year')->nullable();
            $table->date('road_worthy_expire')->nullable();
            $table->string('insurance_type')->nullable();
            $table->date('insurance_expire')->nullable();
            $table->string('insurance_file')->nullable();
            $table->string('road_worthy_file')->nullable();
            $table->integer('is_active')->default(0);
            $table->integer('status')->default(0);
            $table->softDeletes()->nullable();
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
        Schema::dropIfExists('driver_cars');
    }
}
