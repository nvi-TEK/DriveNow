<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Provider;
use App\Document;
use Setting;
use DB;
use Log;
use App\Http\Controllers\SendPushNotification;
use App\Helpers\Helper;
use App\DriverRequestReceived;
use App\OnlineCredit;
use App\UserRequests;
use App\DriverActivity;
use Carbon\Carbon;
use App\ProviderProfile;
use App\OfficialDriver;

class UpdateDriverAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver:availability';

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
        $ten_hours = \Carbon\Carbon::now()->subHours(10);
        $twelve_hours = \Carbon\Carbon::now()->subHours(12);
        $active = Setting::get('active_hours_limit', '0'); //10 Hours

            $day_start = Setting::get('day_start', '08:00').":00";
            $day_end = Setting::get('day_end', '18:00').":00";

            $drivenow_start = Setting::get('drivenow_start', '08:00').":00";
            $drivenow_end = Setting::get('drivenow_end', '18:00').":00";

            $global_engine = Setting::get('global_engine', 0);

            $drivenow_due_engine_control = Setting::get('drivenow_due_engine_control', 0);

            $break_time = Setting::get('driver_break_time','45');

            // $tro_access_token = Setting::get('tro_access_token','');
            // if($tro_access_token == ''){
            //     $time = Carbon::now()->timestamp;
            //     $account = "";
            //     $password = "";
            //     $signature = md5(md5($password).$time);

            //     $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

            //     $token_json = curl($token_url);

            //     $token_details = json_decode($token_json, TRUE);

            //     $tro_access_token = $token_details['record']['access_token'];
            //     Setting::set('tro_access_token', $tro_access_token);
            // }

            $current_time = Carbon::now();

            // $providers = Provider::where('archive','0')->where('status', 'approved')->where('ambassador', 1)->orWhere('promo_driver', 1)->orwhere('official_drivers',1)->get();
             $providers = Provider::where('archive','0')->where('status', 'approved')->where('official_drivers',1)->get();

            

            if(!empty($providers)){
                foreach ($providers as $key => $driver) {

                    $update = Provider::find($driver->id);
                    $official_driver = OfficialDriver::where('driver_id', $driver->id)->where('status', '!=', 1)->first();

                    // 6 AM Cron 
                    // if(date('D') == 'Tue' && date('H') >= 4 && $official_driver->engine_status != 1 && $drivenow_due_engine_control != 0){
                    //     if($official_driver->imei_number != ''){
                    //         $due = $official_driver->amount_due;
                    //         if($due > 0 ){

                    //             for ($i=0; $i < count($official_drivers); $i++) {  

                    //             if($official_drivers[$i]->imei_number !=''){
                    //                 if($i == $over){
                    //                     $imeis .= $official_drivers[$i]->imei_number;
                    //                 } else{
                    //                     $imeis .= $official_drivers[$i]->imei_number .",";
                    //                 } 
                                    
                    //             }
                    //         }
                                
                    //             if($tro_access_token !=''){
                    //                 //Checking the Car motion status

                    //                 $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->imei_number;

                    //                 $status_json = curl($status_url);

                    //                 $status_details = json_decode($status_json, TRUE);
                    //                 if($status_details){
                    //                     if($status_details['code']== '10012'){
                    //                         $time = Carbon::now()->timestamp;
                    //                         $account = "";
                    //                         $password = "";
                    //                         $signature = md5(md5($password).$time);

                    //                         $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                    //                         $token_json = curl($token_url);

                    //                         $token_details = json_decode($token_json, TRUE);

                    //                         $tro_access_token = $token_details['record']['access_token'];
                    //                         Setting::set('tro_access_token', $tro_access_token);
                    //                     }else{
                    //                         $car_speed = $status_details['record'][0]['speed'];
                    //                         $offline_status = $status_details['record'][0]['datastatus'];

                    //                         if($car_speed > 3){
                    //                             $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043.";
                    //                             Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                    //                             (new SendPushNotification)->DriverBreakTime($driver->id,$message);

                    //                             //Send SMS Notification
                    //                                 $content = urlencode("Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043");
                    //                                 $mobile = $driver->mobile;
                    //                                 if($mobile[0] == 0){
                    //                                     $receiver = $mobile;
                    //                                 }else{
                    //                                     $receiver = "0".$mobile; 
                    //                                 }

                    //                                 $client = new \GuzzleHttp\Client();

                    //                                 $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                    //                                 $headers = ['Content-Type' => 'application/json'];
                                                    
                    //                                 $res = $client->get($url, ['headers' => $headers]);

                    //                                 $code = (string)$res->getBody();
                    //                                 $codeT = str_replace("\n","",$code);
                                                    
                    //                         }else if($offline_status == 2){
                    //                             Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                    //                             //Turn off the Engine
                    //                             $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,1";

                    //                             $json = curl($url);

                    //                             $details = json_decode($json, TRUE);

                    //                             $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
                    //                             Log::info($message);
                    //                             (new SendPushNotification)->DriverEngineUpdate($driver->id,$message);


                    //                             $official_driver->engine_off_reason = 'Payment Due';
                               
                    //                             $official_driver->engine_off_on = Carbon::now();
                    //                             $official_driver->engine_off_by = 0;
                    //                             $official_driver->engine_status = 1;
                    //                             $official_driver->save();

                    //                             //Send SMS Notification
                    //                                 $content = urlencode("Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043");
                    //                                 $mobile = $driver->mobile;
                    //                                 if($mobile[0] == 0){
                    //                                     $receiver = $mobile;
                    //                                 }else{
                    //                                     $receiver = "0".$mobile; 
                    //                                 }

                    //                                 $client = new \GuzzleHttp\Client();

                    //                                 $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                    //                                 $headers = ['Content-Type' => 'application/json'];
                                                    
                    //                                 $res = $client->get($url, ['headers' => $headers]);

                    //                                 $code = (string)$res->getBody();
                    //                                 $codeT = str_replace("\n","",$code);
                    //                         } 
                    //                     }
                                        
                    //                 }
                                    
                    //             }  
                    //         }
                    //     }
                    // }
                    

                    //Checking no Incoming Request for last 12 hours
                    $request = DriverRequestReceived::where('provider_id', $update->id)->where('created_at', '>=', Carbon::today())->count();

                    //No. of Request completed in last 24 hours
                    $completed_request = UserRequests::where('provider_id', $update->id)->where('created_at', '>=', Carbon::today())->where('status', 'COMPLETED')->count();

                    $elligible = $request - $completed_request;

                    //Chekcing Online credit for last 12 hours
                    $credit = OnlineCredit::where('driver_id', $update->id)->where('status',0)->where('created_at', '>=', Carbon::today())->count();

                    //Cumulating the total Working hours of Driver
                    $activeHours = DriverActivity::where('driver_id', $update->id)->where('created_at', '>=', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');

                    //Adding Driver Credit for being online for 10 hours
                    $activeHours = $activeHours[0] / 60;
                    // if($driver->ambassador == 1){
                    //    if($elligible < 3 && $credit == 0 && $activeHours > $active){
                    //         $OnlineCredit = new OnlineCredit;
                    //         $OnlineCredit->driver_id = $update->id;
                    //         $OnlineCredit->status = 0;
                    //         $OnlineCredit->save();
                    //         $update->save();
                    //     } 
                    // }else if($driver->promo_driver == 1){
                    //     if($credit == 0 && $activeHours > $active){
                    //         $OnlineCredit = new OnlineCredit;
                    //         $OnlineCredit->driver_id = $update->id;
                    //         $OnlineCredit->status = 0;
                    //         $OnlineCredit->save();
                    //     }
                    // }else 
                    // if($driver->official_drivers == 1){
                        if($credit == 0 && $activeHours > $active){
                            $OnlineCredit = new OnlineCredit;
                            $OnlineCredit->driver_id = $update->id;
                            $OnlineCredit->status = 0;
                            $OnlineCredit->save();
                        }
                    // }
                        // Log::info("DriveNow Driver Credit Added to ". $update->first_name." (".$update->id.")");

                    // Checking the Driver last online activity and make offline if driver online for more than 12 hours

                    $Driveractivity = DriverActivity::where('driver_id', $driver->id)->where('is_active', 1)
                        ->where('start','<=',\Carbon\Carbon::now()->subHours(12))
                        ->first();

                    if($Driveractivity){
                        $Driveractivity->is_active = 0;
                        $Driveractivity->end = Carbon::now();
                        $min = $Driveractivity->end->diffInMinutes($Driveractivity->start, true);

                        $Driveractivity->working_time = $min;
                        $Driveractivity->save();

                        $update->available_on = Carbon::now();
                        $update->availability = 0;
                        $update->save();
                    }


                    $breakHours = DriverActivity::where('driver_id',$driver->id)->whereTime('start','>=',$drivenow_start)
                                                ->whereTime('end','<=',$drivenow_end)->whereDate('created_at', Carbon::today())
                                                ->select([DB::raw("SUM(break_time) as breakHours")])->pluck('breakHours');
                    $max_break_time = $break_time + ($break_time * (10/100) );
                    $break_left = $break_time - $breakHours[0];

                    // if($global_engine == 1 && $official_driver->engine_control == 1 && $official_driver->imei_number != '' && $driver->availability == 0){

                    //     $offline_driver = Provider::where('id',$driver->id)->where('availability', 0)->first();

                    //     $offline_mins = $offline_left = 0;

                    //     $offline_mins = $current_time->diffInMinutes($offline_driver->available_on, true);
                        
                    //     if($offline_mins > 0 && $offline_mins < $break_time){
                    //         $offline_left = $break_time - $offline_mins;
                    //     }
                        
                    //     if($break_left > 0 || $offline_left > 0){
                    //         $message = "Alert: ".$break_left." mins offline time remaining.";
                    //          Log::info($message);
                    //          (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                    //     }

                    //     if($breakHours[0] >= $break_time || $offline_mins >= $break_time){
                    //         // Get Access Token of TroTro Tracker
                    //         $time = Carbon::now()->timestamp;
                    //         $account = "Eganow@trotrotracker.com";
                    //         $password = "EganowTech1T#";
                    //         $signature = md5(md5($password).$time);

                    //         $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                    //         $token_json = curl($token_url);

                    //         $token_details = json_decode($token_json, TRUE);

                    //         $tro_access_token = $token_details['record']['access_token'];

                    //         if($tro_access_token !=''){
                    //             //Checking the Car motion status

                    //             $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->imei_number;

                    //             $status_json = curl($status_url);

                    //             $status_details = json_decode($status_json, TRUE);
                    //             if($status_details){
                    //                 $car_speed = $status_details['record'][0]['speed'];
                    //                 $offline_status = $status_details['record'][0]['datastatus'];

                    //                 if($car_speed > 3){
                    //                     $message = "Alert: Engine switch-off pending. You have gone over your allowed offline time. Please go online now to reset";
                    //                     (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                    //                 }else if($offline_status == 2){
                    //                     Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                    //                     //Turn off the Engine
                    //                     $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,1";

                    //                     $json = curl($url);

                    //                     $details = json_decode($json, TRUE);
                                        
                    //                     //Update Engine Status
                    //                     $official_driver->engine_off_reason = 'Offline';
                    //                     $official_driver->engine_off_on = Carbon::now();
                    //                     $official_driver->engine_off_by = 0;
                    //                     $official_driver->engine_status = 1;
                    //                     $official_driver->save();
                                        
                    //                     $message = "Alert: You have no offline hours remaining. Going offline may engage engine switch-off";
                    //                     Log::info($message);
                    //                     (new SendPushNotification)->DriverEngineUpdate($driver->id,$message);
                    //                 } 
                    //             }
                                
                    //         }
                    //     }
                    // }
                }
            }


        $drivers = Provider::where('availability','1')->where('archive','0')->where('status', 'approved')
                        ->where('available_on','<=',\Carbon\Carbon::now()->subHours(12))
                        ->get();
            
        if(!empty($drivers)){
            foreach ($drivers as $key => $driver) {

                $update = Provider::find($driver->id);
                

                $Driveractivity = DriverActivity::where('driver_id', $driver->id)->where('is_active', 1)
                        ->where('start','<=',\Carbon\Carbon::now()->subHours(12))
                        ->first();

                    if($Driveractivity){
                        $Driveractivity->is_active = 0;
                        $Driveractivity->end = Carbon::now();
                        $min = $Driveractivity->end->diffInMinutes($Driveractivity->start, true);

                        $Driveractivity->working_time = $min;
                        $Driveractivity->save();

                        $update->available_on = Carbon::now();
                        $update->availability = 0;
                        $update->save();
                    }else{
                        $update->available_on = Carbon::now();
                        $update->availability = 0;
                        $update->save();
                    }
                // (new SendPushNotification)->DriverOffline($driver->id);
            }
        }

        // $Driveractivities = DriverActivity::where('is_active', 1)
        //                 ->where('start','<=',\Carbon\Carbon::now()->subHours(12))
        //                 ->where('end', '=', '')
        //                 ->orWhereNull('end')
        //                 ->get();
        //     foreach ($Driveractivities as $Driveractivity) {
        //          if($Driveractivity){
        //                 $Driveractivity->is_active = 0;
        //                 $Driveractivity->end = Carbon::now();
        //                 $min = $Driveractivity->end->diffInMinutes($Driveractivity->start, true);

        //                 $Driveractivity->working_time = $min;
        //                 $Driveractivity->save();
        //             }
        //     }

        // $online_drivers = Provider::where('availability','1')->where('archive','0')->where('status', 'approved')->get();

        // if(!empty($online_drivers)){
        //     foreach ($online_drivers as $key => $online_driver) {

        //         $update = Provider::find($online_driver->id);

        //         $now = Carbon::now();

        //         $profile = ProviderProfile::where('provider_id',$online_driver->id)->first();

        //         $last_update = $now->diffInMinutes($update->updated_at, true);

        //         if($last_update > 5){

        //             (new SendPushNotification)->DriverInActivity($online_driver->id);

        //         }

        //         // if($profile){
        //         //     if($last_update > 15){
        //         //         if($profile->notified == 1){
        //         //             $profile->notified = 2;
        //         //         }
        //         //         if($profile->notified == 2){
        //         //             $profile->notified = 3;
        //         //         }
        //         //         if($profile->notified == 3){
        //         //             $profile->notified = 4;
        //         //         }
        //         //         if($profile->notified == '' || $profile->notified ==0){
        //         //             $profile->notified = 1;
        //         //         }
        //         //         $profile->save();

        //         //         if($profile->notified != 4 || $profile->notified == '' || $profile->notified == 0 ){

        //         //             (new SendPushNotification)->DriverInActivity($online_driver->id);
                        
        //         //         }
        //         //     }
        //         // }
        //     }
        // }
                   

        $this->info('Made Drivers offline after being online for 8 hours ');
    }
}
