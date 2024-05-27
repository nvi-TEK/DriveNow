<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserRequestPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_request_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('request_id');
            $table->integer('promocode_id')->nullable();
            
            $table->string('payment_id')->nullable();
            $table->string('payment_mode')->nullable();

            $table->float('fixed',      10, 2)->default(0);
            $table->float('time',   10, 2)->default(0);
            $table->float('distance_taken',   10, 2)->default(0);
            $table->float('time_taken',   10, 2)->default(0);
            $table->float('commision',  10, 2)->default(0);
            $table->float('time_price', 10, 2)->default(0);
            $table->float('distance_price', 10, 2)->default(0);
            $table->float('discount',   10, 2)->default(0);
            $table->float('tax',        10, 2)->default(0);
            $table->float('wallet',     10, 2)->default(0);
            $table->float('total',      10, 2)->default(0);

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
        Schema::dropIfExists('user_request_payments');
    }
}
