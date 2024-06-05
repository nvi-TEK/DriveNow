<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMLMUserCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('m_l_m_user_commissions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('request_id');
            $table->string('l1_id')->nullable();
            $table->string('l1_com')->nullable();
            $table->string('l2_id')->nullable();
            $table->string('l2_com')->nullable();
            $table->string('l3_id')->nullable();
            $table->string('l3_com')->nullable();
            $table->string('l4_id')->nullable();
            $table->string('l4_com')->nullable();
            $table->string('l5_id')->nullable();
            $table->string('l5_com')->nullable();
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
        Schema::dropIfExists('m_l_m_user_commissions');
    }
}
