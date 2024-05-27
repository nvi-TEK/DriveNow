<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverDayOffsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_day_offs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('official_id');
            $table->integer('status');
            $table->timestamp('day_off')->nullable();
            $table->timestamps();
        });

        Schema::table('official_drivers', function (Blueprint $table) {
            $table->integer('day_off')->default(0);
        });

        Schema::table('drivenow_vehicles', function (Blueprint $table) {
            $table->string('sim')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('driver_day_offs');
    }
}
