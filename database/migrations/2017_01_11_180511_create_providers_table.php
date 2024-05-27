<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile')->nullable();
            $table->string('password');
            $table->string('avatar')->nullable();
            $table->string('referal')->nullable();
            $table->string('description')->nullable();
            $table->decimal('rating', 4, 2)->default(5);
            $table->enum('status', ['onboarding', 'approved', 'banned']);
            $table->double('latitude', 15, 8)->nullable();
            $table->double('longitude', 15, 8)->nullable();
            $table->float('wallet_balance',10,2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->integer('otp')->default(0);
            $table->string('social_unique_id')->nullable();
            $table->integer('otp_activation')->default(0);
            $table->integer('document_uploaded')->default(0);
            $table->string('country_code')->nullable();
            $table->integer('availability')->default(0);
            // $table->string('social_unique_id')->nullable(); //I commented out this line because it is duplicate Line 31 == Line 35 -Olabode Abesin
            $table->rememberToken();
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
        Schema::drop('providers');
    }
}
