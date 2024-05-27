<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBaseRadiusToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->integer('base_radius')->after('fixed')->default(0);
        });

        Schema::table('fleet_prices', function (Blueprint $table) {
            $table->integer('base_radius')->after('fixed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_types', function (Blueprint $table) {
            //
        });
    }
}
