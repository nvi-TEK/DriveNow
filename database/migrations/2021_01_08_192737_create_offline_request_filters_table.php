<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOfflineRequestFiltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offline_request_filters', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_id');
            $table->integer('user_id');
            $table->integer('provider_id');
            $table->integer('status')->default(0);
            $table->double('latitude',15,8)->nullable();
            $table->double('longitude',15,8)->nullable();
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
        Schema::dropIfExists('offline_request_filters');
    }
}
