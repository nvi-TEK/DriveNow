<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOfficeExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('office_expenses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('exp_id')->nullable();
            $table->string('paid_to')->nullable();
            $table->string('category')->nullable();
            $table->string('amount')->nullable();
            $table->string('description')->nullable();
            $table->integer('added_by')->nullable();
            $table->integer('approved_by')->nullable();
            $table->integer('car_id')->nullable();
            $table->integer('status')->nullable();
            $table->string('date')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_name_id')->nullable();
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
        Schema::dropIfExists('office_expenses');
    }
}
