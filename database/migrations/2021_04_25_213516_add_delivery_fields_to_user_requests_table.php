<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDeliveryFieldsToUserRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_requests', function (Blueprint $table) {
            $table->string('pickup_add_flat')->nullable();
            $table->string('pickup_add_area')->nullable();
            $table->string('pickup_add_landmark')->nullable();
            $table->string('delivery_add_flat')->nullable();
            $table->string('delivery_add_area')->nullable();
            $table->string('delivery_add_landmark')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_requests', function (Blueprint $table) {
            //
        });
    }
}
