<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMLMUserNetworksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_l_m_user_networks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('l1')->nullable();
            $table->string('l2')->nullable();
            $table->string('l3')->nullable();
            $table->string('l4')->nullable();
            $table->string('l5')->nullable();
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
        Schema::dropIfExists('m_l_m_user_networks');
    }
}
