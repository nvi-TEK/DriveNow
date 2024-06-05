<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUploadImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upload_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->integer('request_id')->nullable();
            $table->string('tempId')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });
   

        Schema::table('user_requests', function (Blueprint $table) {
            
            $table->string('delivery_image')->nullable();
            $table->enum('pay_resp', [
                    'SELF',
                    'RECEIVER'
                ]);
            $table->float('donation', 10, 2)->default(0);
        });

        Schema::table('user_request_payments', function (Blueprint $table) {
            
            $table->float('donation', 10, 2)->default(0);
        });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('upload_images');
    }
}
