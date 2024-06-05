<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMarketerReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketer_referrals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('referrer_code')->nullable();
            $table->string('marketer_id')->nullable();
            $table->string('driver_id')->nullable();
            $table->string('user_id')->nullable();
            $table->double('amount')->default(0);
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
        Schema::dropIfExists('marketer_refferals');
    }
}
