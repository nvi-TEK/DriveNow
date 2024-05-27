<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCarDetailsToProviderProfilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('provider_profiles', function (Blueprint $table) {
            $table->string('car_registration')->nullable();
            $table->string('car_make')->nullable();
            $table->string('car_model')->nullable();
            $table->string('car_picture')->nullable();
            $table->string('mileage')->nullable();
            $table->string('car_make_year')->nullable();
            $table->date('road_worthy_expire')->nullable();
            $table->string('insurance_type')->nullable();
            $table->date('insurance_expire')->nullable();
            
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
