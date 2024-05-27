<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomPushesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_pushes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('sender_id');
            $table->integer('receiver_count');
            $table->string('message')->nullable();
            $table->string('group')->nullable();
            $table->string('type')->nullable();
            $table->string('range')->nullable();
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
        Schema::dropIfExists('custom_pushes');
    }
}
