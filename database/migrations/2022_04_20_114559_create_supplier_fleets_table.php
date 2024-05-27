<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSupplierFleetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('supplier_fleets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('contact')->nullable();
            $table->string('vehicle_cost')->nullable();
            $table->string('initial_amount')->nullable();
            $table->integer('due_length')->nullable();
            $table->string('monthly_due')->nullable();
            $table->integer('due_date')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('acc_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('amount_due')->nullable();
            $table->string('amount_paid')->nullable();
            $table->integer('status')->default(0);
            $table->integer('supplier_id');
            $table->timestamps();
        });

         Schema::table('drivenow_vehicles', function (Blueprint $table) {
            $table->integer('fleet_id')->default(0);
        });

        Schema::table('drivenow_vehicle_payments', function (Blueprint $table) {
                $table->integer('fleet_id')->default(0);
            });
    }

   

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('supplier_fleets');
    }
}
