<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriverSubaccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_subaccounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id')->default(0);
            $table->integer('split_id')->default(0);
            $table->string('account_number')->nullable();
            $table->integer('bank_code')->default(0);
            $table->string('business_name')->nullable();
            $table->string('fullname')->nullable();
            $table->string('date_created')->nullable();
            $table->integer('account_id')->default(0);
            $table->integer('split_ratio')->default(0);
            $table->string('split_type')->nullable();
            $table->double('split_value')->default(0);
            $table->string('subaccount_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('country')->nullable();
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
        Schema::dropIfExists('driver_subaccounts');
    }
}
