<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowExtraPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_extra_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver_id')->nullable();
            $table->string('official_id')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('reason')->nullable();
            $table->string('comments')->nullable();
            $table->integer('count')->nullable();
            $table->string('type')->nullable();
            $table->integer('total')->nullable();
            $table->integer('due')->nullable();
            $table->integer('daily_due')->nullable();
            $table->integer('status')->nullable();
            $table->integer('completed')->nullable();
            $table->integer('amount_paid')->nullable();
            $table->string('started_at')->nullable();
            $table->timestamps();
        });

        Schema::table('official_drivers', function (Blueprint $table) {
            $table->string('extra_pay')->nullable();
            $table->string('daily_due_add')->nullable();
            $table->string('amount_due_add')->nullable();
        });

        Schema::table('drivenow_transactions', function (Blueprint $table) {
            $table->string('due_before')->nullable();
            $table->string('daily_due_before')->nullable();
            $table->string('balance_before')->nullable();
            $table->string('paid_date')->nullable();
            $table->string('delay')->nullable();
            $table->integer('paid_amount')->nullable();
            $table->integer('balance_amount')->nullable();
            $table->integer('balance_score')->nullable();
            $table->integer('payment_status')->nullable();
            $table->integer('pay_score')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_extra_payments');
    }
}
