<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use App\Http\Controllers\SendPushNotification;
use App\Provider;
use App\Helpers\Helper;
use App\ProviderProfile;
use Log;

class CustomCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:rides';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating the Scheduled Rides Timing';

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

        //Sending push notification to Driver whose Location Updated 15 mins ago

          $online_drivers = Provider::where('availability','1')->where('archive','0')->where('status', 'approved')->get();

        if(!empty($online_drivers)){
            foreach ($online_drivers as $key => $online_driver) {

                $update = Provider::find($online_driver->id);

                $now = Carbon::now();

                $profile = ProviderProfile::where('provider_id',$online_driver->id)->first();

                $last_update = $now->diffInMinutes($update->updated_at, true);

                
                if($last_update > 5){
                    Log::info('Location Lost Push Sent to '.$update->first_name.' ( '.$update->id.' )!');
                    (new SendPushNotification)->DriverInActivity($update->id);
                }
                

                // if($profile){
                //     if($last_update > 15){
                //         if($profile->notified == 1){
                //             $profile->notified = 2;
                //         }
                //         if($profile->notified == 2){
                //             $profile->notified = 3;
                //         }
                //         if($profile->notified == 3){
                //             $profile->notified = 4;
                //         }
                //         if($profile->notified == '' || $profile->notified ==0){
                //             $profile->notified = 1;
                //         }
                //         $profile->save();

                //         if($profile->notified != 4 ){
                //             Log::info('Location Lost Push Sent to '.$update->first_name.' ( '.$update->id.' )!');
                //             (new SendPushNotification)->DriverInActivity($update->id);
                        
                //         }
                //     }
                // }
            }
        }

        $UserRequest = DB::table('user_requests')->where('status','SCHEDULED')
                        ->where('schedule_at','<=',\Carbon\Carbon::now()->addMinutes(60))
                        ->get();

        // $hour =  \Carbon\Carbon::now()->subMinutes(60);
        $futurehours = \Carbon\Carbon::now()->addMinutes(60);
        $date =  \Carbon\Carbon::now();           


        if(!empty($UserRequest)){
            foreach($UserRequest as $ride){

                 //scehule start request push to user
                (new SendPushNotification)->user_schedule_hour($ride->user_id);
                 //scehule start request push to provider
                (new SendPushNotification)->provider_schedule_hour($ride->provider_id);
            }
        }

        $UserRequest = DB::table('user_requests')->where('status','SCHEDULED')
                        ->where('schedule_at','<=',\Carbon\Carbon::now()->addMinutes(15))
                        ->get();

        // $hour =  \Carbon\Carbon::now()->subHour();
        $futurehours = \Carbon\Carbon::now()->addMinutes(15);
        $date =  \Carbon\Carbon::now();           


        if(!empty($UserRequest)){
            foreach($UserRequest as $ride){
                DB::table('user_requests')
                        ->where('id',$ride->id)
                        ->update(['status' => 'STARTED', 'assigned_at' =>Carbon::now() , 'schedule_at' => null ]);

                 //scehule start request push to user
                (new SendPushNotification)->user_schedule($ride->user_id);
                 //scehule start request push to provider
                (new SendPushNotification)->provider_schedule($ride->provider_id);

                DB::table('provider_services')->where('provider_id',$ride->provider_id)->update(['status' =>'riding']);
            }
        }
    }
}
