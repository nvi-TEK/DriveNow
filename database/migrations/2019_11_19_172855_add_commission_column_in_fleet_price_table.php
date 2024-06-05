<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCommissionColumnInFleetPriceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fleet_prices', function (Blueprint $table) {
            $table->double('commission')->after('time')->default(0);
            $table->double('drivercommission')->after('time')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fleet_prices', function (Blueprint $table) {
            $table->dropColumn('commission');
            $table->dropColumn('drivercommission');
        });
    }
}
