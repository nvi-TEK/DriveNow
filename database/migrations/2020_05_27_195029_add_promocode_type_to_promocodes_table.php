<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPromocodeTypeToPromocodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promocodes', function (Blueprint $table) {
            $table->string('type')->nullable();
            $table->integer('count_max')->defalut(0);
            $table->integer('driver_contribution')->defalut(0);
            $table->integer('admin_contribution')->defalut(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promocodes', function (Blueprint $table) {
            //
        });
    }
}
