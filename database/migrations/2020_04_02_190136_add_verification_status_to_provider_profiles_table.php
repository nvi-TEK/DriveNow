<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddVerificationStatusToProviderProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->integer('bank_status')->default(0);
            $table->integer('license_status')->default(0);
            $table->integer('vehicle_status')->default(0);
            $table->integer('notified')->default(0);
            $table->integer('account_saved')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            //
        });
    }
}
