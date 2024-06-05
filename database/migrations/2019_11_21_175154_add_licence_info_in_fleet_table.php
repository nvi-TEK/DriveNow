<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLicenceInfoInFleetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->string('dl_no')->nullable();
            $table->dateTime('dl_exp')->nullable();
            $table->string('dl_country')->nullable();
            $table->string('dl_state')->nullable();
            $table->string('dl_city')->nullable();
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
            $table->dropColumn('dl_no');
            $table->dropColumn('dl_exp');
            $table->dropColumn('dl_country');
            $table->dropColumn('dl_state');
            $table->dropColumn('dl_city');
        });
    }
}
