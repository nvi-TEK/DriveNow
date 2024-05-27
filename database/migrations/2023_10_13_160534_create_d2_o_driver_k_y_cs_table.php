<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDriveNowDriverKYCsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('drivenow_driver_k_y_cs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('driver_id');
            $table->integer('official_id')->nullable();
            $table->string('ghana_card_name')->nullable();
            $table->string('ghana_card_number')->nullable();
            $table->string('house_address')->nullable();
            $table->double('house_latitude', 15, 8)->nullable();
            $table->double('house_longitude',15,8)->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('ghana_card_image')->nullable();
            $table->string('ghana_card_image_back')->nullable();
            $table->string('residence_image')->nullable();
            $table->string('water_bill_image')->nullable();
            $table->string('eb_bill_image')->nullable();
            $table->string('g1_name')->nullable();
            $table->string('g1_mobile')->nullable();
            $table->string('g1_profile_image')->nullable();
            $table->string('g1_ghana_card_no')->nullable();
            $table->string('g1_ghana_card_image')->nullable();
            $table->string('g1_ghana_card_image_back')->nullable();
            $table->string('g1_house_address')->nullable();
            $table->string('g1_house_gps')->nullable();
            $table->double('g1_house_latitude', 15, 8)->nullable();
            $table->double('g1_house_longitude',15,8)->nullable();
            $table->string('g2_name')->nullable();
            $table->string('g2_mobile')->nullable();
            $table->string('g2_profile_image')->nullable();
            $table->string('g2_ghana_card_no')->nullable();
            $table->string('g2_ghana_card_image')->nullable();
            $table->string('g2_ghana_card_image_back')->nullable();
            $table->string('g2_house_address')->nullable();
            $table->string('g2_house_gps')->nullable();
            $table->double('g2_house_latitude', 15, 8)->nullable();
            $table->double('g2_house_longitude',15,8)->nullable();
            $table->integer('uploaded_by')->nullable();
            $table->timestamp('uploaded_on')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_on')->nullable();
            $table->integer('status')->nullable();
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
        Schema::dropIfExists('drivenow_driver_k_y_cs');
    }
}
