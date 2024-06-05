<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowAdditionalTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_additional_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tran_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->integer('official_id')->nullable();
            $table->double('paid_amount')->nullable();
            $table->integer('type')->nullable();
            $table->double('amount')->nullable();
            $table->timestamps();
        });

        Schema::table('drivenow_extra_payments', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_additional_transactions');
    }
}
