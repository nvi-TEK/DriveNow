<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowRaveTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_rave_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver_id')->nullable();
            $table->string('official_id')->nullable();
            $table->string('bill_id')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('slp_ref_id')->nullable();
            $table->string('slp_resp')->nullable();
            $table->string('network')->nullable();
            $table->string('amount')->nullable();
            $table->string('comments')->nullable();
            $table->string('status')->nullable();
            $table->string('due_before')->nullable();
            $table->string('due_after')->nullable();
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
        Schema::dropIfExists('drivenow_rave_transactions');
    }
}
