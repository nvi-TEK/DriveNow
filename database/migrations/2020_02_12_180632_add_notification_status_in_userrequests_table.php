<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotificationStatusInUserrequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->boolean('notification')->default(1);
        });
        Schema::table('marketers', function (Blueprint $table) {
            $table->boolean('user_referrals')->nullable();
            $table->boolean('driver_referrals')->nullable();
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
            $table->dropColumn('notification');
        });
         Schema::table('marketers', function (Blueprint $table) {
            $table->dropColumn('user_referrals');
            $table->dropColumn('driver_referrals');
        });
    }
}
