<?php

use Illuminate\Database\Seeder;

use Carbon\Carbon;

class ServiceTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	DB::table('service_types')->truncate();
        DB::table('service_types')->insert([
            [
                'name' => 'Electrician',
                'provider_name' => 'Electrician',
                'fixed' => 20,
                'price' => 10,
                'status' => 1,
                'image' => url('asset/img/services/electrician.jpg'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Plumbing',
                'provider_name' => 'Plumber',
                'fixed' => 20,
                'price' => 10,
                'status' => 1,
                'image' => url('asset/img/services/plumbing.jpg'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Carpenter',
                'provider_name' => 'Carpenter',
                'fixed' => 20,
                'price' => 10,
                'status' => 1,
                'image' => url('asset/img/services/carpenter.jpg'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Mechanic',
                'provider_name' => 'Mechanic',
                'fixed' => 20,
                'price' => 10,
                'status' => 1,
                'image' => url('asset/img/services/mechanic.jpg'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
