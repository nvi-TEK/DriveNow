<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateIndividualPushesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('individual_pushes', function (Blueprint $table) {
            
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('user_id');
            $table->integer('sender_id');
            $table->string('message')->nullable();
            $table->string('type')->nullable();
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
        Schema::dropIfExists('individual_pushes');
    }
}
