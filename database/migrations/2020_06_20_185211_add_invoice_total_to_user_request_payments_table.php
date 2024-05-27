<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceTotalToUserRequestPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_request_payments', function (Blueprint $table) {
            $table->float('trip_fare',      10, 2)->default(0);
            $table->float('sub_total',      10, 2)->default(0);
            $table->float('driver_earnings',      10, 2)->default(0);
            $table->float('money_to_wallet',      10, 2)->default(0);
            $table->float('amount_to_collect',      10, 2)->default(0);
            $table->float('minimum_fare',      10, 2)->default(0);
            

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_request_payments', function (Blueprint $table) {
            //
        });
    }
}
