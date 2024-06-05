<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Provider;
use Setting;

class DriverMonthlyCommission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'month:commission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used to get commission from drivers';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $drivers = Provider::where('status', 'approved')->where('archive','0')->get();
        if(!empty($drivers)){
            foreach ($drivers as $key => $driver) {
                $update = Provider::find($driver->id);
                $update->wallet_balance -= Setting::get('monthly_commission', 200);
                $update->save();
            }
        }

        $this->info('Monthly Commission Updated');
    }
}
