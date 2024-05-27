<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFleetToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('fleet')->nullable();
        });
        
        Schema::table('fleets', function (Blueprint $table) {
            $table->string('acc_no')->nullable();
            $table->string('acc_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->integer('bank_name_id')->nullable();
            $table->string('bank_code')->nullable();
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
