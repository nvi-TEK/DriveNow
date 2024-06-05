<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAgreementTermsToProviders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('contract_length')->nullable();
            $table->string('weekly_payment')->nullable();
            $table->string('vehicle_cost')->nullable();
            $table->string('agreement_start_date')->nullable();
            $table->string('contract_address')->nullable();
            $table->string('deposit')->nullable();
            $table->string('agreed_on')->nullable();
            $table->string('work_pay_balance')->default(0);
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
