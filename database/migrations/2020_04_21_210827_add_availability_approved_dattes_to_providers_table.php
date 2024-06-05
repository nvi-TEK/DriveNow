<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAvailabilityApprovedDattesToProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('available_on')->nullable();
        });
        Schema::table('service_types', function (Blueprint $table) {
            $table->integer('minimum_fare')->after('fixed')->default(0);
        });

        Schema::table('fleet_prices', function (Blueprint $table) {
            $table->integer('minimum_fare')->after('fixed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            //
        });
    }
}
