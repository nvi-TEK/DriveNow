<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->truncate();
        DB::table('settings')->insert([
            [
                'key' => 'site_title',
                'value' => 'Eganow'
            ],
            [
                'key' => 'site_logo',
                'value' => '/asset/logo.png',
            ],
            [
                'key' => 'site_mail_icon',
                'value' => '/asset/logo.png',
            ],
            [
                'key' => 'site_icon',
                'value' => '/asset/icon.jpg',
            ],
            [
                'key' => 'provider_select_timeout',
                'value' => 60
            ],
            [
                'key' => 'search_radius',
                'value' => 100
            ],
            [
                'key' => 'base_price',
                'value' => 50
            ],
            [
                'key' => 'price_per_minute',
                'value' => 50
            ],
            [
                'key' => 'tax_percentage',
                'value' => 0
            ],  
            [
                'key' => 'stripe_secret_key',
                'value' => ''
            ], 
             [
                'key' => 'stripe_publishable_key',
                'value' => ''
            ], 
            [
                'key' => 'CASH',
                'value' => 1
            ], 
            [
                'key' => 'CARD',
                'value' => 1
            ],
            [
                'key' => 'manual_request',
                'value' => 0
            ],  
            [
                'key' => 'default_lang',
                'value' => 'en'
            ], 
            [
                'key' => 'currency',
                'value' => '$'
            ], 
            [
                'key' => 'scheduled_cancel_time_exceed',
                'value' => 10
            ],
            [
                'key' => 'price_per_kilometer',
                'value' => 10
            ],
            [
                'key' => 'commission_percentage',
                'value' => 0
            ],
            [
                'key' => 'email_logo',
                'value' => ''
            ],
            [
                'key' => 'play_store_link',
                'value' => ''
            ],
            [
                'key' => 'app_store_link',
                'value' => ''
            ],
            [
                'key' => 'daily_target',
                'value' => 0
            ],
            [
                'key' => 'surge_percentage',
                'value' => 0
            ],
            [
                'key' => 'surge_trigger',
                'value' => 0
            ],
            [
                'key' => 'distance',
                'value' => 'Km'
            ],
            [
                'key' => 'demo_mode',
                'value' => 0
            ],
            [
                'key' => 'booking_prefix',
                'value' => 'FNX'
            ],
            [
                'key' => 'daily_target',
                'value' => 0
            ],
            [
                'key' => 'contact_number',
                'value' => '9876543210'
            ],
            [
                'key' => 'contact_email',
                'value' => 'admin@eganow.com'
            ],
            [
                'key' => 'page_privacy',
                'value' => ''
            ],
            [
                'key' => 'contact_text',
                'value' => 'We will Contact you soon'
            ],
            [
                'key' => 'contact_title',
                'value' => 'Eganow Help'
            ],
        ]);
    }
}
