<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverDepositsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_deposits', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('amount')->nullable();
            $table->integer('added_by');
            $table->string('remarks');
            $table->integer('status')->default(0);
            $table->integer('refund')->nullable();
            $table->integer('refunded_by')->nullable();
            $table->string('refund_reason')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('acc_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
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
        Schema::dropIfExists('driver_deposits');
    }
}
