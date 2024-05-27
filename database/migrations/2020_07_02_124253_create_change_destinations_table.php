<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChangeDestinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('change_destinations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->integer('request_id')->nullable();
            $table->double('latitude',15,8)->nullable();
            $table->double('longitude',15,8)->nullable();
            $table->string('title')->nullable();
            $table->string('address')->nullable();
            $table->float('fare', 10, 2)->default(0);
            $table->integer('status')->is_default(0);
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
        Schema::dropIfExists('change_destinations');
    }
}
