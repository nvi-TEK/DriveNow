<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreditStatusRaveTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rave_transactions', function (Blueprint $table) {
            $table->integer('credit')->default(0);
            $table->string('last_balance')->nullable();

        });

        Schema::table('providers', function (Blueprint $table) {
            $table->float('starting_balance',10,2)->default(0);
            $table->integer('fleet_driver')->default(0);
        });
        
        Schema::table('user_requests', function (Blueprint $table) {
            $table->float('estimated_fare', 10, 2)->default(0);
            $table->float('distance_price', 10, 2)->default(0);
            $table->float('time', 10, 2)->default(0);
            $table->float('time_price', 10, 2)->default(0);
            $table->float('tax_price', 10, 2)->default(0);
            $table->float('base_price', 10, 2)->default(0);
            $table->float('wallet_balance', 10, 2)->default(0);
            $table->float('discount', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rave_transactions', function (Blueprint $table) {
            //
        });
    }
}
