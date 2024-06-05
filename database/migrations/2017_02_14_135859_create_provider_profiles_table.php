<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProviderProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('provider_profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('provider_id');
            $table->string('language')->nullable();
            $table->string('address')->nullable();
            $table->string('address_secondary')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('acc_no')->nullable();
            $table->string('acc_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->integer('bank_name_id')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('dl_city')->nullable();
            $table->integer('dl_city_id')->nullable();
            $table->string('dl_country')->nullable();
            $table->string('dl_state')->nullable();
            $table->string('dl_state_id')->nullable();
            $table->string('dl_exp')->nullable();
            $table->string('dl_no')->nullable();
            $table->string('postal_code')->nullable();
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
        Schema::dropIfExists('provider_profiles');
    }
}
