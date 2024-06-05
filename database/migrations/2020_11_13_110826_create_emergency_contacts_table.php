<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmergencyContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('picture')->nullable();
            $table->string('mobile')->nullable();
            $table->string('country_code')->nullable();
            $table->timestamps();
        });

        Schema::table('user_requests', function (Blueprint $table) {
            
            $table->integer('sos_alert')->nullable();
            $table->timestamp('alert_initiated')->nullable();
            $table->timestamp('accepted_at')->nullable();
        });

         Schema::table('fleets', function (Blueprint $table) {
            $table->string('referal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('emergency_contacts');
    }
}
