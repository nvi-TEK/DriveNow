<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id')->nullable();
            $table->integer('official_id')->nullable();
            $table->date('agreement_start_date')->nullable();
            $table->integer('contract_id')->nullable();
            $table->integer('status')->default(0);
            $table->string('remarks')->nullable();
            $table->timestamp('agreed_on')->nullable();
            $table->timestamp('cancelled_on')->nullable();
            $table->integer('cancelled_by')->nullable();
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
        Schema::dropIfExists('driver_contracts');
    }
}
