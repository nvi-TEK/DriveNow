<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowBlockedHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_blocked_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver_id')->nullable();
            $table->string('official_id')->nullable();
            $table->string('engine_off_reason')->nullable();
            $table->integer('engine_off_by')->nullable();
            $table->timestamp('engine_off_on')->nullable();
            $table->integer('amount_due')->nullable();
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
        Schema::dropIfExists('drivenow_blocked_histories');
    }
}
