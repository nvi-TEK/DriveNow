<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverRequestReceivedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_request_receiveds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_id');
            $table->integer('user_id');
            $table->integer('provider_id');
            $table->integer('service_id');
            $table->integer('status')->default(0);
            $table->string('distance')->nullable();
            $table->string('duration')->nullable();
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
        Schema::dropIfExists('driver_request_receiveds');
    }
}
