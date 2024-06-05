<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOfficialDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('official_drivers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id');
            $table->string('driver_name')->nullable();
            $table->string('driver_contact')->nullable();
            $table->string('imei_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('contract_length')->nullable();
            $table->string('weekly_payment')->nullable();
            $table->string('vehicle_cost')->nullable();
            $table->string('agreement_start_date')->nullable();
            $table->string('contract_address')->nullable();
            $table->string('deposit')->nullable();
            $table->string('agreed_on')->nullable();
            $table->string('work_pay_balance')->default(0);
            $table->string('next_due')->nullable();
            $table->string('initial_amount')->nullable();
            $table->string('amount_paid')->nullable();
            $table->string('amount_due')->nullable();
            $table->string('balance_weeks')->nullable();
            $table->integer('status')->default(0);
            $table->integer('agreed')->default(0);
            $table->string('vehicle_image')->nullable();
            $table->timestamp('updated_on')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('engine_control')->default(0);
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_year')->nullable();
            $table->integer('break')->default(0);
            $table->integer('engine_status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('official_drivers');
    }
}
