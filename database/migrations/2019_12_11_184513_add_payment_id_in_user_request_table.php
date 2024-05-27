<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaymentIdInUserRequestTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->integer('owner_payment_id')->after('paid')->default(0);
            $table->boolean('owner_payout')->after('paid')->default(0);
            $table->integer('driver_payment_id')->after('paid')->default(0);
            $table->boolean('driver_payout')->after('paid')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->dropColumn('owner_payment_id');
            $table->dropColumn('owner_payout');
            $table->dropColumn('driver_payment_id');
            $table->dropColumn('driver_payout');
        });
    }
}
