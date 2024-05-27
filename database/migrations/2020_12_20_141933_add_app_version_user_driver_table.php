<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAppVersionUserDriverTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('android_app_version')->nullable();
            $table->string('ios_app_version')->nullable();
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->string('android_app_version')->nullable();
            $table->string('ios_app_version')->nullable();
        });

        Schema::table('fleets', function (Blueprint $table) {
            $table->integer('dispatch_method')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
