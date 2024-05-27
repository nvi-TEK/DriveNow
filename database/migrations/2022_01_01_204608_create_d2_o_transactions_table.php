<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver_id')->nullable();
            $table->string('contract_id')->nullable();
            $table->string('amount')->nullable();
            $table->string('due')->nullable();
            $table->string('add_charge')->nullable();
            $table->string('due_date')->nullable();
            $table->string('daily_due_date')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });

         Schema::table('driver_activities', function (Blueprint $table) {
            $table->string('break_time')->nullable();
         });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_transactions');
    }
}
