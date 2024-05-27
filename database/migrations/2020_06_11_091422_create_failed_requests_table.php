<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFailedRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('failed_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('booking_id');
            $table->integer('user_id');
            $table->integer('fleet_id');
            $table->integer('service_type_id');
            $table->enum('payment_mode', [
                    'CASH',
                    'CARD',
                    'MOBILE'
                ]);
            $table->float('distance', 10, 2)->default(0);
            $table->string('s_title')->nullable();
            $table->string('s_address')->nullable();
            $table->double('s_latitude', 15, 8);
            $table->double('s_longitude', 15, 8);
            
            $table->string('d_title')->nullable();
            $table->string('d_address')->nullable();
            $table->double('d_latitude', 15, 8)->default(0);
            $table->double('d_longitude', 15, 8)->default(0);
            
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('schedule_at')->nullable();
            $table->boolean('use_wallet')->default(0);

            $table->string('receiver_name')->nullable();
            $table->string('receiver_mobile')->nullable();
            $table->string('pickup_instruction')->nullable();
            $table->string('delivery_instruction')->nullable();
            $table->string('package_type')->nullable();
            $table->string('package_details')->nullable();
            $table->string('confirmation_code')->nullable();

            $table->float('discount',   10, 2)->default(0);
            $table->float('total',      10, 2)->default(0);
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
        Schema::dropIfExists('failed_requests');
    }
}
