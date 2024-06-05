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
use App\DriveNowBlockedHistory;
use App\DriverDayOff;
use App\DriveNowVehicle;
use App\DriveNowTransaction;
use App\DriveNowExtraPayment;

class DriveNowPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'driver:drivenow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drive to Own Program Engine Control using Tro Tracker';

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
        $active = Setting::get('active_hours_limit', '0'); 

        $day_start = Setting::get('day_start', '08:00').":00";
        $day_end = Setting::get('day_end', '18:00').":00";

        $drivenow_start = Setting::get('drivenow_start', '08:00').":00";
        $drivenow_end = Setting::get('drivenow_end', '18:00').":00";

        $global_engine = Setting::get('global_engine', 0);

        $drivenow_due_engine_control = Setting::get('drivenow_due_engine_control', 0);

        $break_time = Setting::get('driver_break_time','45');

        $current_time = Carbon::now();

        $tro_access_token = Setting::get('tro_access_token','');
        if($tro_access_token == ''){
            $time = Carbon::now()->timestamp;
            $account = ""; // replace with account name
            $password = ""; // replace with password
            $signature = md5(md5($password).$time);

            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

            $token_json = curl($token_url);

            $token_details = json_decode($token_json, TRUE);

            $tro_access_token = $token_details['record']['access_token'];
            Setting::set('tro_access_token', $tro_access_token);
            Setting::save();
            Log::info("Tro Access Token Called");
        }

        $official_drivers = OfficialDriver::with('provider')->where('status','!=', 1)->get();
        $daily_drivers = OfficialDriver::with('provider')->where('status','!=', 1)->where('daily_driveNow', 1)->get();
        for ($o=0; $o < count($official_drivers); $o++) {

            $OldDriverOff = DriverDayOff::where('driver_id',$official_drivers[$o]->driver_id)->whereDate('day_off', '<', Carbon::today())->update(['status' => 1]);
            $cur_day_off = DriverDayOff::where('driver_id',$official_drivers[$o]->driver_id)->whereDate('day_off', Carbon::today())->where('status',0)->first();
            if(!$cur_day_off){
                $official_drivers[$o]->day_off = 0;
                $official_drivers[$o]->save();
            }
        }

        //Daily DriveNow Invoice Generation
        if(date('H') == 17 && date('D') != 'Sun'){
            $today = date('Y-m-d');
            $present = new \DateTime($today);
            $next_due = date('Y-m-d', strtotime('tomorrow'));
                    $additional_charge = 0;
            for ($i=0; $i < count($daily_drivers); $i++) { 
                    $official_driver = OfficialDriver::where('driver_id', $daily_drivers[$i]->driver_id)->where('status', '!=', 1)->first();
                    $agreement_start_date = new \DateTime($official_driver->agreement_start_date);

                    // if($present > $agreement_start_date){
                    if($official_driver->amount_due >=0 && $official_driver->daily_due <= $official_driver->amount_due){
                        $drivenow_transaction = DriveNowTransaction::where('daily_due_date',$today)->where('driver_id', $daily_drivers[$i]->driver_id)->first();
                        
                        $extras = 0;
                        if($official_driver->extra_pay > 0){
                            $extras = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('daily_due');
                        }else{
                            $official_driver->extra_pay = 0;
                            DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1]);
                        }
                                                
                        if(!$drivenow_transaction){
                            $drivenow_transaction = new DriveNowTransaction;
                            $drivenow_transaction->due_before = $official_driver->amount_due;
                            $drivenow_transaction->daily_due_before = $official_driver->daily_due;
                            $drivenow_transaction->balance_before = $official_driver->amount_paid;
                            $official_driver->next_due = $today;
                            $official_driver->break = 0;
                            $du = $official_driver->daily_due + $official_driver->daily_payment;
                            
                            if($du >= $official_driver->amount_due){
                                $du = $official_driver->amount_due;
                                // 
                            }
                            // if($official_driver->amount_due <= $official_driver->weekly_payment){
                            //     $official_driver->daily_payment = $official_driver->weekly_payment / 6;
                            // }
                            $official_driver->daily_due = $du;
                            $official_driver->daily_due_add = $official_driver->daily_due_add + $extras;
                            $official_driver->save();
                        }

                        $drivenow_transaction->driver_id = $daily_drivers[$i]->driver_id;
                        $drivenow_transaction->contract_id = $official_driver->id;
                        $drivenow_transaction->amount = $official_driver->daily_payment + $extras;
                        $drivenow_transaction->due = $official_driver->daily_payment;
                        $drivenow_transaction->add_charge = $extras;
                        $drivenow_transaction->daily_due_date = $today;
                        $drivenow_transaction->status = 0;
                        $drivenow_transaction->save();
                    }

                        $drivenow_due_transaction = DriveNowTransaction::where('daily_due_date', '<', $today)->whereNotNull('daily_due_date')->where('driver_id', $official_driver->driver_id)->where('status',0)->update(['status' => 3]);
                    
                }
        }


        // Due Payment Engine Control
        // if(date('D') == 'Tue' && date('H') >= 4){
        if(date('H') >= 4){
            
            $imeis = '';
            //Fetching IMEI Number to feed Tro Traker api
            for ($i=0; $i < count($official_drivers); $i++) { 
                
                if($official_drivers[$i]->daily_driveNow !=1){
                   if(date('D') == 'Tue'){
                    $due_c = 0;
                    }else{
                        $due_c = $official_drivers[$i]->weekly_payment;
                    } 
                    $due = $official_drivers[$i]->amount_due; 
                }else {
                    $today = date('Y-m-d');
                    
                    if($official_drivers[$i]->next_due == $today){
                        $due_c = $official_drivers[$i]->daily_payment;
                    }else{
                        $due_c = 0;
                    }
                    
                    $due = $official_drivers[$i]->daily_due;
                }
                
                if($official_drivers[$i]->vehicle->imei !='' && $official_drivers[$i]->engine_status != 1 && $drivenow_due_engine_control != 0 && $due > $due_c && $official_drivers[$i]->due_engine_control != 1){
                    $imeis .= str_replace(' ', '',$official_drivers[$i]->vehicle->imei) .",";
                    $official_drivers[$i]->vehicle->imei = str_replace(' ', '',$official_drivers[$i]->vehicle->imei);
                    $official_drivers[$i]->save();
                }
            }
            $imeis = substr_replace($imeis,"",-1);

            if($tro_access_token !='' && $imeis !=''){
                $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                $status_json = curl($status_url);

                $status_details = json_decode($status_json, TRUE);

                if($status_details){
                    if($status_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = ""; // fill with account name
                        $password = ""; // fill with password
                        $signature = md5(md5($password).$time);

                        $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                        $token_json = curl($token_url);

                        $token_details = json_decode($token_json, TRUE);

                        $tro_access_token = $token_details['record']['access_token'];
                        Setting::set('tro_access_token', $tro_access_token);
                        Setting::save();
                        Log::info("Tro Access Token Called");
                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                        $status_json = curl($status_url);

                        $status_details = json_decode($status_json, TRUE);
                    }
                    for ($i=0; $i < count($status_details['record']); $i++) { 

                        $official_driver = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->where('status','!=',1)->first();

                        $car_speed = $status_details['record'][$i]['speed'];

                        $offline_status = $status_details['record'][$i]['datastatus'];

                        $driver = Provider::where('id', $official_driver->driver_id)->first();
                        if($status_details['record'][$i]['oilpowerstatus'] == 0){
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                            }else{
                                $official_driver->engine_status = 0;
                                $official_driver->save();
                            }
                        if($car_speed > 3){
                            $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow driver support team on 0506428043.";
                            Log::info("Car Speed Up: ". $official_driver->driver_name." ( ". $driver->id ." )");
                            (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                            $official_driver->block_try = "Speed up";
                            $official_driver->save();
                            //Send SMS Notification
                                // $content = urlencode("Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow driver support team on 0506428043");

                                $content = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow driver support team on 0506428043";

                                $mobile = $driver->mobile;
                                if($mobile[0] == 0){
                                    $receiver = "233".substr($mobile,1);
                                }else{
                                    $receiver = "233".$mobile;
                                }

                                // else{
                                //     $receiver = "0".$mobile; 
                                // }
                                // $sendMessage = sendMessageRancard($receiver, $content);
                    
                                // $client = new \GuzzleHttp\Client();

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";

                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                                
                        }else if($offline_status == 2){
                            Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                            //Turn off the Engine
                            $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->vehicle->imei."&command=RELAY,1";

                            $json = curl($url);

                            $details = json_decode($json, TRUE);

                            $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow driver support team on 0506428043";
                            Log::info($message);
                            (new SendPushNotification)->DriverEngineUpdate($driver->id,$message);

                            $td = date('Y-m-d');
                            $official_driver->engine_off_reason = 'Payment Due';
                            $official_driver->engine_off_on = Carbon::now();
                            $official_driver->engine_off_by = 0;
                            $official_driver->engine_status = 1;
                            $official_driver->save();
                            $blocked_history = DriveNowBlockedHistory::where('driver_id',$official_driver->driver_id)->whereDate('engine_off_on',$td)->first();
                            if(!$blocked_history){
                               $blocked_history = new DriveNowBlockedHistory; 
                            }
                            
                            $blocked_history->official_id = $official_driver->id;
                            $blocked_history->driver_id = $official_driver->driver_id;
                            $blocked_history->engine_off_by = 0;
                            $blocked_history->amount_due = $official_driver->amount_due;
                            $blocked_history->engine_off_on = Carbon::now();
                            $blocked_history->engine_off_reason = $official_driver->engine_off_reason;
                            $blocked_history->save();

                            //Send SMS Notification
                                // $content = urlencode("Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow driver support team on 0506428043");
                                $content = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow driver support team on 0506428043";
                                $mobile = $driver->mobile;
                                // if($mobile[0] == 0){
                                //     $receiver = $mobile;
                                // }else{
                                //     $receiver = "0".$mobile; 
                                // }
                                if($mobile[0] == 0){
                                    $receiver = "233".substr($mobile,1);
                                }else{
                                    $receiver = "233".$mobile;
                                }
                                 $sendMessage = sendMessageRancard($receiver, $content);
                                
                                // $client = new \GuzzleHttp\Client();

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";

                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                        }else{
                            // $vehicle = DriveNowVehicle::where('imei',$status_details['record'][$i]['imei'])->first();
                            // if($vehicle->sim !=''){
                            //     $mobile = $vehicle->sim;
                            //     if($mobile[0] == 0){
                            //         $receiver = $mobile;
                            //     }else{
                            //         $receiver = "0".$mobile; 
                            //     }
                            //     $content = urlencode("*22*2#");
                            //     $client = new \GuzzleHttp\Client();

                            //     $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";

                            //     $headers = ['Content-Type' => 'application/json'];
                                
                            //     $res = $client->get($url, ['headers' => $headers]);

                            //     $code = (string)$res->getBody();
                            //     $codeT = str_replace("\n","",$code);
                            //     Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                            // }
                            
                            $official_driver->block_try = "Offline";
                            $official_driver->save();
                        } 
                    }
                }
            } 
        }
        // Driver Offline Engine Control
        $start = (int)substr($drivenow_start,'0','2');
        $end = (int)substr($drivenow_end,'0','2');

        if($global_engine == 1 && (int)date('H') >= $start && (int)date('H') < $end){
            $offline_imeis = '';
            $tday = Carbon::today()->toDateString();
            $c_start = $tday.$drivenow_start;
            $c_start = Carbon::parse($c_start);
            //Fetching IMEI Number to feed Tro Traker api
            for ($i=0; $i < count($official_drivers); $i++) {  

                $driver = Provider::where('id', $official_drivers[$i]->driver_id)->first();

                $breakHours = DriverActivity::where('driver_id',$driver->id)->whereTime('start','>=',$drivenow_start)
                                            ->whereTime('end','<=',$drivenow_end)->whereDate('created_at', $tday)
                                            ->select([DB::raw("SUM(break_time) as breakHours")])->pluck('breakHours');
                if($breakHours[0] > 0){
                    Log::info($driver->first_name .' ('.$driver->id. ') - Break Mins: '. $breakHours[0]);
                }
                

                $max_break_time = $break_time + ($break_time * (10/100) );
                
                if($global_engine == 1 && $official_drivers[$i]->engine_control == 1 && $official_drivers[$i]->vehicle->imei != '' && $driver->availability == 0 && $official_drivers[$i]->day_off == 0 && $official_drivers[$i]->engine_status != 1){

                    $offline_driver = Provider::where('id',$driver->id)->where('availability', 0)->whereTime('available_on','>=',$drivenow_start)->whereDate('available_on', $tday)->first();

                    $offline_mins = $offline_left = 0;

                    $tday = Carbon::today()->toDateString();
                    $current_time = Carbon::now();
                    $c_start = $tday.$drivenow_start;
                    $c_start = Carbon::parse($c_start);

                    if($offline_driver){
                        $offline_mins = $current_time->diffInMinutes($offline_driver->available_on, true);
                    }else{
                        $offline_mins = $current_time->diffInMinutes($c_start, true);
                    }

                    $break_left = $break_time - ($offline_mins + $breakHours[0]);

                    $total_offline = $offline_mins + $breakHours[0];

                    // Log::info('Break Left: '. $break_left);
                    

                    if($break_left == 30 || $break_left == 20 || $break_left == 10 || $break_left == 5 ){
                        $message = "Alert: ".$break_left." mins offline time remaining.";
                         Log::info($message." for ". $offline_driver->first_name ."( ". $offline_driver->id .")");
                         (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                         $mobile = $driver->mobile;
                            if($mobile[0] == 0){
                                $receiver = $mobile;
                            }else{
                                $receiver = "0".$mobile; 
                            }
                            $sendMessage = sendMessageRancard($receiver, $message);
                    }

                    if($total_offline >= $break_time){
                        $offline_imeis .= str_replace(' ', '',$official_drivers[$i]->vehicle->imei) .",";
                    }
                }
            }
            $offline_imeis = substr_replace($offline_imeis,"",-1);
            
            if($tro_access_token !='' && $offline_imeis !=''){
                $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$offline_imeis;

                $status_json = curl($status_url);

                $status_details = json_decode($status_json, TRUE);

                if($status_details){
                    if($status_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "Eganowtrotrotracker.com";
                        $password = "Eganowech1T#";
                        $signature = md5(md5($password).$time);

                        $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                        $token_json = curl($token_url);

                        $token_details = json_decode($token_json, TRUE);

                        $tro_access_token = $token_details['record']['access_token'];
                        Setting::set('tro_access_token', $tro_access_token);
                        Setting::save();
                        Log::info("Tro Access Token Called");
                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$offline_imeis;

                        $status_json = curl($status_url);

                        $status_details = json_decode($status_json, TRUE);
                    }
                    
                    for ($i=0; $i < count($status_details['record']); $i++) { 

                        $car_speed = $status_details['record'][$i]['speed'];
                        $offline_status = $status_details['record'][$i]['datastatus'];
                        $official_driver = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->where('status', '!=', 1)->first();
                        $driver = Provider::where('id', $official_driver->driver_id)->first();

                        //Updating Vehicle Engine status
                        if($status_details['record'][$i]['oilpowerstatus'] == 0){
                            $official_driver->engine_status = 1;
                            $official_driver->save();
                        }else{
                            $official_driver->engine_status = 0;
                            $official_driver->save();
                        }

                        if($car_speed > 5){
                            $message = "Alert: Engine switch-off pending. You have gone over your allowed offline time. Please go online now to reset";
                            Log::info("Car Speed Up: ". $official_driver->driver_name." ( ". $driver->id ." )");
                            (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                            
                            //Send SMS
                            // $content = urlencode("Alert: Engine switch-off pending. You have gone over your allowed offline time. Please go online now to reset");
                            $content = "Alert: Engine switch-off pending. You have gone over your allowed offline time. Please go online now to reset";
                            $mobile = $driver->mobile;
                            // if($mobile[0] == 0){
                            //     $receiver = $mobile;
                            // }else{
                            //     $receiver = "0".$mobile; 
                            // }
                            if($mobile[0] == 0){
                                    $receiver = "233".substr($mobile,1);
                            }else{
                                $receiver = "233".$mobile;
                            }
                            // $sendMessage = sendMessageRancard($receiver, $content);

                            // $client = new \GuzzleHttp\Client();

                            // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";

                            // $headers = ['Content-Type' => 'application/json'];
                            
                            // $res = $client->get($url, ['headers' => $headers]);

                            // $code = (string)$res->getBody();
                            // $codeT = str_replace("\n","",$code);

                        }else if($offline_status == 2 && $official_driver->engine_status != 1){
                            Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                            //Turn off the Engine
                            $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->vehicle->imei."&command=RELAY,1";

                            $json = curl($url);

                            $details = json_decode($json, TRUE);
                            
                            //Update Engine Status
                            $official_driver->engine_off_reason = 'Offline';
                            $official_driver->engine_off_on = Carbon::now();
                            $official_driver->engine_off_by = 0;
                            $official_driver->engine_status = 1;
                            $official_driver->save();

                            $blocked_history = new DriveNowBlockedHistory;
                            $blocked_history->official_id = $official_driver->id;
                            $blocked_history->driver_id = $official_driver->driver_id;
                            $blocked_history->engine_off_by = 0;
                            $blocked_history->engine_off_on = Carbon::now();
                            $blocked_history->engine_off_reason = $official_driver->engine_off_reason;
                            $blocked_history->save();
                            
                            $message = "Alert: Vehicle deactivated. You have no offline hours remaining. Go online now to reactivate your vehicle.";
                            Log::info($message);
                            (new SendPushNotification)->DriverBreakTime($driver->id,$message);

                            $content = "Alert: Vehicle deactivated. You have no offline hours remaining. Go online now to reactivate your vehicle.";
                            $mobile = $driver->mobile;

                            if($mobile[0] == 0){
                                    $receiver = "233".substr($mobile,1);
                            }else{
                                $receiver = "233".$mobile;
                            }
                            $sendMessage = sendMessageRancard($receiver, $content);

                            // if($mobile[0] == 0){
                            //     $receiver = $mobile;
                            // }else{
                            //     $receiver = "0".$mobile; 
                            // }

                            // $client = new \GuzzleHttp\Client();

                            // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";

                            // $headers = ['Content-Type' => 'application/json'];
                            
                            // $res = $client->get($url, ['headers' => $headers]);

                            // $code = (string)$res->getBody();
                            // $codeT = str_replace("\n","",$code);

                            $vehicle = DriveNowVehicle::where('imei',$status_details['record'][0]['imei'])->first();
                            if($vehicle->sim !=''){
                                $mobile = $vehicle->sim;
                                $content = "*22*2#";
                                if($mobile[0] == 0){
                                    $receiver = "233".substr($mobile,1);
                                }else{
                                    $receiver = "233".$mobile;
                                }
                                $sendMessage = sendMessageRancard($receiver, $content);

                                // if($mobile[0] == 0){
                                //     $receiver = $mobile;
                                // }else{
                                //     $receiver = "0".$mobile; 
                                // }
                                // $content = urlencode("*22*2#");
                                // $client = new \GuzzleHttp\Client();

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";
                                // Log::info("Engine Block SMS: ". $url);
                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                                Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                            }

                        }
                        // else{
                        //     Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $official_driver->driver_id ." )");
                        //     //Turn off the Engine
                        //     $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->vehicle->imei."&command=RELAY,1";

                        //     $json = curl($url);

                        //     $details = json_decode($json, TRUE);

                        //     $vehicle = DriveNowVehicle::where('imei',$status_details['record'][0]['imei'])->first();
                        //     if($vehicle->sim !=''){
                        //         $mobile = $vehicle->sim;
                        //         // if($mobile[0] == 0){
                        //         //     $receiver = $mobile;
                        //         // }else{
                        //         //     $receiver = "0".$mobile; 
                        //         // }
                        //         $content = urlencode("*22*2#");
                        //         if($mobile[0] == 0){
                        //             $receiver = "233".substr($mobile,1);
                        //         }else{
                        //             $receiver = "233".$mobile;
                        //         }
                        //         $sendMessage = sendMessageRancard($receiver, $content);
                        //         // $client = new \GuzzleHttp\Client();

                        //         // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganowtext=".$content."&smsc=RANCARD";
                        //         // Log::info("Engine Block SMS: ". $url);
                        //         // $headers = ['Content-Type' => 'application/json'];
                                
                        //         // $res = $client->get($url, ['headers' => $headers]);

                        //         // $code = (string)$res->getBody();
                        //         // $codeT = str_replace("\n","",$code);
                        //         Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                        //     }
                            
                        //     $blocked_history = new DriveNowBlockedHistory;
                            
                        //     $official_driver->engine_off_reason = 'Offline';
                           
                        //     $official_driver->engine_off_by = "Cron";
                        //     $official_driver->engine_off_on = Carbon::now();
                            
                        //     $official_driver->engine_status = 1;
                        //     $official_driver->save();

                        //     $blocked_history->official_id = $official_driver->id;
                        //     $blocked_history->driver_id = $official_driver->driver_id;
                        //     $blocked_history->engine_off_by = "Cron";
                        //     $blocked_history->engine_off_on = Carbon::now();

                        //     $blocked_history->engine_off_reason = $official_driver->engine_off_reason;
                        //     $blocked_history->save();

                        //     return back()->with('flash_success', "Car engine turned off.");
                        // } 
                    }
                }
            }
        }

        
        $this->info('Drive to Own Program Engine Control using Tro Tracker');
    }
}
