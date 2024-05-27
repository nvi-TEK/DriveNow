<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSoftdeletesAlltables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('providers', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('provider_devices', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('provider_services', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('provider_profiles', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('provider_documents', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('users', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('user_locations', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('fleets', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('documents', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('promocodes', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
       Schema::table('service_types', function (Blueprint $table) {
            $table->softDeletes()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('providers', function (Blueprint $table) {
            //
        });
       Schema::table('provider_devices', function (Blueprint $table) {
            //
        });
       Schema::table('provider_services', function (Blueprint $table) {
            //
        });
       Schema::table('provider_profiles', function (Blueprint $table) {
            //
        });
       Schema::table('provider_documents', function (Blueprint $table) {
            //
        });
       Schema::table('users', function (Blueprint $table) {
            //
        });
       Schema::table('user_location', function (Blueprint $table) {
            //
        });
       Schema::table('fleet', function (Blueprint $table) {
            //
        });
       Schema::table('documents', function (Blueprint $table) {
            //
        });
       Schema::table('promocodes', function (Blueprint $table) {
            //
        });
       Schema::table('service_types', function (Blueprint $table) {
            //
        });
    }
}
