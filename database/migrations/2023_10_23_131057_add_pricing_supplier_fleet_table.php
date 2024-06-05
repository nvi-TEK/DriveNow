<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPricingSupplierFleetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('supplier_fleets', function (Blueprint $table) {
            $table->integer('weekly')->nullable();
            $table->integer('company_share')->nullable();
            $table->integer('maintenance_fee')->nullable();
            $table->integer('insurance_fee')->nullable();
            $table->integer('road_worthy_fee')->nullable();
            $table->integer('management_fee')->nullable();
            
        });

        Schema::table('drivenow_rave_transactions', function (Blueprint $table) {
            $table->integer('weekly')->nullable();
            $table->integer('company_share')->nullable();
            $table->integer('maintenance_fee')->nullable();
            $table->integer('insurance_fee')->nullable();
            $table->integer('road_worthy_fee')->nullable();
            $table->integer('management_fee')->nullable();
            $table->integer('total_before')->nullable();
            $table->integer('total_after')->nullable();
            $table->integer('vehicle_id')->nullable();
            
        });

        Schema::table('official_drivers', function (Blueprint $table) {
            $table->integer('weekly')->nullable();
            $table->integer('company_share')->nullable();
            $table->integer('maintenance_fee')->nullable();
            $table->integer('insurance_fee')->nullable();
            $table->integer('road_worthy_fee')->nullable();
            $table->integer('management_fee')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
