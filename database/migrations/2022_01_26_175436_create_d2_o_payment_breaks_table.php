<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowPaymentBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_payment_breaks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('driver_id')->nullable();
            $table->string('official_id')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('reason')->nullable();
            $table->string('type')->nullable();
            $table->string('comments')->nullable();
            $table->integer('count')->nullable();
            $table->timestamps();
        });

        Schema::table('official_drivers', function (Blueprint $table) {
            $table->string('engine_off_reason')->nullable();
            $table->integer('engine_off_by')->nullable();
            $table->timestamp('engine_off_on')->nullable();
            $table->string('engine_restore_reason')->nullable();
            $table->integer('engine_restore_by')->nullable();
            $table->timestamp('engine_restore_on')->nullable();
            $table->string('terminated_reason')->nullable();
            $table->timestamp('terminated_on')->nullable();
            $table->integer('due_engine_control')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_payment_breaks');
    }
}
