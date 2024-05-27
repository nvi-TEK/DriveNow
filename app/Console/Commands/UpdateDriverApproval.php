<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Provider;
use App\Document;
use Setting;
use DB;
use App\Http\Controllers\SendPushNotification;
use App\Helpers\Helper;

class UpdateDriverApproval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver:approval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Used to make drivers offline who online for more than 8 hours';

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
        $drivers = DB::table('providers')->where('status','approved')->where('archive','0')
                        ->where('approved_at','<=',\Carbon\Carbon::now()->subDays(14))
                        ->get();
        $documents = Document::all()->count();
        if(!empty($drivers)){
            foreach ($drivers as $key => $driver) {
                $update = Provider::find($driver->id);
                if($documents != $update->accessed_documents()){
                        $update->status = 'banned';
                        $update->save();
                        $to = $driver->country_code.$driver->mobile;
                        $from = "DriveNow Team";            
                        $content = urlencode("Hello ".$driver->first_name.", Your 14 days trial to drive on DriveNow has expired. Please upload necessary documents or contact the driver support team to be reactivated.");
                        $clientId = env("HUBTEL_API_KEY");
                        $clientSecret = env("HUBTEL_API_SECRET");            
                        $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                        (new SendPushNotification)->DriverTrialEnd($driver->id);
                } 
            }
        }

        $this->info('Cancelled Drivers Trial Period after ends');
    }
}
