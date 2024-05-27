<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMarketerColumnInProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->integer('marketer')->after('fleet')->default(0);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('marketer')->after('fleet')->default(0);
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
            $table->dropColumn('marketer');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('marketer');
        });
    }
}
