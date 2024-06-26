<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_id');
            $table->integer('user_id');
            $table->integer('fleet_id')->nullable();
            $table->integer('provider_id');
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
        Schema::dropIfExists('request_activities');
    }
}
