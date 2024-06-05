<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_contracts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('content')->nullable();
            $table->integer('status')->default(0);
            $table->integer('is_default')->default(0);
            $table->timestamps();
        });
        Schema::table('official_drivers', function (Blueprint $table) {
            $table->integer('contract_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('drivenow_contacts');
    }
}
