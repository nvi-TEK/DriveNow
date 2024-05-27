<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAutopayoutColumnInFleetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->boolean('auto_payout')->after('dl_city')->default(0);
            $table->boolean('driver_payout')->after('dl_city')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn('auto_payout');
            $table->dropColumn('driver_payout');
        });
    }
}
