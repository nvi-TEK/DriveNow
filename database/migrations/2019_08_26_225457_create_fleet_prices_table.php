<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFleetPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fleet_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fleet_id');
            $table->integer('service_id');
            $table->double('fixed',10,2)->default(0);
            $table->double('price',10,2)->default(0);
            $table->double('time',10,2)->default(0);
            $table->enum('calculator', [
                    'FLEXI',
                    'FIXED'
                ]);
            $table->integer('status')->default(0);
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::table('user_requests', function (Blueprint $table) {
            $table->integer('fleet_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fleet_prices');
    }
}
