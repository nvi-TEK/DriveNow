<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDailyPaymentsToOfficialDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('official_drivers', function (Blueprint $table) {
            $table->string('daily_due')->nullable();
            $table->string('daily_payment')->nullable();
            $table->integer('daily_drivenow')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('official_drivers', function (Blueprint $table) {
            //
        });
    }
}
