<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowCreditScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_credit_scores', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id')->nullable();
            $table->integer('official_id')->nullable();
            $table->integer('vehicle_id')->nullable();
            $table->integer('oia')->nullable();
            $table->integer('aia')->nullable();
            $table->integer('ops')->nullable();
            $table->integer('aps')->nullable();
            $table->integer('c_score')->nullable();
            $table->integer('p_score')->nullable();
            $table->integer('month')->nullable();
            $table->integer('year')->nullable();
            $table->timestamps();
        });
        Schema::table('providers', function (Blueprint $table) {
            $table->integer('delete_acc')->default(0);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->integer('delete_acc')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_credit_scores');
    }
}
