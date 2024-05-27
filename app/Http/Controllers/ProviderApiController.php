<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Exception;
use Storage;
use Setting;
use Log;
use DB;

use Carbon\Carbon;
use App\UserRequests;
use App\User;
use App\Chat;
use App\Provider;
use App\FleetPrice;
use App\Fleet;
use App\ServiceType;
use App\ProviderService;
use App\DriverLocation;
use App\PromocodeUsage;
use App\Notification;
use App\RaveTransaction;
use App\ProviderProfile;
use App\ChangeDestination;
use App\Promocode;
use App\Marketers;
use App\MarketerReferrals;
use App\OnlineCredit;
use App\EmergencyContact;
use App\DriverRequestReceived;
use App\DriverCars;
use App\DriverAccounts;
use App\UserRequestPayment;
use App\ProviderDocument;
use App\DriverActivity;
use App\OfficialDriver;
use App\DriveNowTransaction;
use App\DriveNowRaveTransaction;
use App\DriveNowPaymentBreak;
use App\DriveNowBlockedHistory;
use App\DriveNowVehicle;
use Paystack;// Paystack package
use App\DriveNowContracts;
use App\DriverDayOff;
use App\DriverDeposit;
use App\OfficeExpense;
use App\DriverContracts;
use App\DriveNowExtraPayment;
use App\DriveNowAdditionalTransactions;
use App\DriveNowDriverKYC;
use App\DriveNowVehicleSupplier;
use App\SupplierFleet;
use App\DriveNowVehicleRepairHistory;

use App\Http\Controllers\SendPushNotification;

class ProviderApiController extends Controller
{

    /**
     * Show the services.
     *
     * @return \Illuminate\Http\Response
     */

    public function services() {
        $Services = ServiceType::where('type',0)->get();
        if($Services) {
            foreach ($Services as $key => $value) {

                $price = ProviderService::where('provider_id',Auth::user()->id)
                            ->where('service_type_id',$value->id)
                            ->first();
                if(count($price)>0){
                    if($price->service_type_id == $value->id){
                        $Services[$key]->available = true;
                    }else{
                        $Services[$key]->available = false;
                    }
                }else{
                        $Services[$key]->available = false;
                }
            }
            return response()->json(['success' => TRUE, 'services'=> $Services], 200);
        } else {
            return response()->json(['error' => 'No Services!'], 200);
        }

    }
    public function drive_own(){
        return redirect()->route('provider.drivenow');
    }

    public function services_coda() {
        $Services = ServiceType::where('type',1)->get();
        if($Services) {
            foreach ($Services as $key => $value) {

                $price = ProviderService::where('provider_id',Auth::user()->id)
                            ->where('service_type_id',$value->id)
                            ->first();
                if(count($price)>0){
                    if($price->service_type_id == $value->id){
                        $Services[$key]->available = true;
                    }else{
                        $Services[$key]->available = false;
                    }
                }else{
                        $Services[$key]->available = false;
                }
            }
            return response()->json(['success' => TRUE, 'services'=> $Services], 200);
        } else {
            return response()->json(['error' => 'No Services!'], 200);
        }

    }

    public function notifications(){
        try{
            $notifications = Notification::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => TRUE, 'notifications'=> $notifications], 200);
        }
        catch (Exception $e) {
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function delete_notification(Request $request){
        try{
            $delete_notification = Notification::where('id', $request->not_id)->first();
            if($delete_notification){
                $delete_notification->delete();
            }
            $notifications = Notification::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => TRUE, 'notifications'=> $notifications, 'message' => 'Notification has been removed'], 200);
        }
        catch (Exception $e) {
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }


    public function join_promotion(Request $request){
        try{
            $promo_count = Setting::get('promotion_count', '100');
            $promo_drivers = Provider::where('promo_driver', '1')->where('status', 'approved')->count();
            if($promo_drivers >= $promo_count){
                return back()->with('flash_warning', "Thanks. You successfully enrolled with Eganow Guarantee Promotion");
            }
        $Provider = Provider::where('email', $request->email)->first();
        // dd($Provider);
        if($Provider) {
            if($Provider->promo_driver != 1 && $Provider->status != 'approved'){
                return back()->with('flash_not_approved', "You driver account is not activated. Please submit your driver documents in app to drive with Eganow. Once activated, you can try again to join promotion.");
            }
            if ($Provider->promo_driver == 1) {
               return back()->with('flash_exist', "Thanks. You successfully enrolled with Eganow Guarantee Promotion");
            }
                $Provider->promo_driver = 1;
                $Provider->promo_added_at = Carbon::now();
                $Provider->save();
                return back()->with('flash_success', "Thanks. You successfully enrolled with Eganow Guarantee Promotion");
            } else {
                return back()->with('flash_error', "Sorry! You're not registered with us, Please download our app to Drive with Eganow!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function check_status(Request $request){
        try{

        $Provider = Provider::where('email', $request->email)->first();
        // dd($Provider);
        if($Provider) {
            if($Provider->promo_driver != 1 && $Provider->status != 'approved'){
                return back()->with('flash_not_approved', "You driver account is not activated. Please submit your driver documents in app to drive with Eganow. Once activated, you can try again to join promotion.");
            }
            if ($Provider->promo_driver == 1) {
                $user_referred = User::where('driver_referred', $Provider->referal)->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->get();
                $Provider->user_referral = count($user_referred);
                $Provider->driver_referral = Provider::where('driver_referred', $Provider->referal)->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count();
                $Provider->online_credit = OnlineCredit::where('driver_id', $Provider->id)->where('status', 0)->count();
                return redirect('/join_promotion/#get_status')->with('flash_status', $Provider);
            }

                
            } else {
                return back()->with('flash_error', "Sorry! You're not registered with us, Please download our app to Drive with Eganow!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }


    public function sendOTP(Request $request){
        try{
            $otp = rand(100000, 999999);
            $to = $request->to;
            $to = str_replace(" ", "", $to);
            $cc = (substr($to, 0, 3));
            $from = "Eganow";
            Log::info("Driver OTP: ". $otp ." - ". $to);
            if(str_contains($cc,"23") == true){
                $content = urlencode("[#] Eganow Driver: Your verification code is ".$otp.". Drive on Eganow, Drive for your future AaIrz7s/a2I");
                $clientId = env("HUBTEL_API_KEY");
                $clientSecret = env("HUBTEL_API_SECRET");

                // $sendSms =  (new HubtelMessage)
                // ->from($from)
                // ->to($to)
                // ->content($content);

                $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                if(count($sendSms) > 1){
                    return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Hubtel'], 200);
                }
                else if(count($sendSms) == 1 || $sendSms == FALSE){
                    $content = "[#] Eganow Driver: Your verification code is ".$otp.". Drive on Eganow, Drive for your future AaIrz7s/a2I";
                    // if(str_contains($to,"+233") == true){
                    //     $mobile = substr($to, 4);
                    // }else{
                    //     $mobile = substr($to, 3);
                    // }

                    if(str_contains($to,"+233") == true){
                        $mobile = substr($to, 1);
                    }else{
                        $mobile = $to;
                    }
                    $sendMessage = sendMessageRancard($mobile, $content);
                    
                    if($sendMessage['code'] == "200"){
                        return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Rancard'], 200);
                    }
                    // if($mobile[0] == 0){
                    //     $receiver = $mobile;
                    // }else{
                    //     $receiver = "0".$mobile; 
                    // }


                    // $client1 = new \GuzzleHttp\Client();

                    // $url1 = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&getBalance=true";

                    // $headers1 = ['Content-Type' => 'application/json'];
                    
                    // $res1 = $client1->get($url1, ['headers' => $headers1]);

                    // $data = json_decode($res1->getBody());

                    // $balance = round(str_replace("Messaging balance for API User: f3En@x is","", $data));

                    // $client = new \GuzzleHttp\Client();

                    // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";
                    // Log::info($url);

                    // $headers = ['Content-Type' => 'application/json'];
                    
                    // $res = $client->get($url, ['headers' => $headers]);

                    // $code = (string)$res->getBody();
                    // $codeT = str_replace("\n","",$code);
                
                    // if($codeT == "000"){
                    //     return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Rancard'], 200);
                    // }
                    // else{
                    
                    //     $content = "[#] Eganow Driver: Your verification code is ".$otp.". Drive on Eganow, Drive for your future AaIrz7s/a2I";
                    //     $sendTwilio = sendMessageTwilio($to, $content);
                    //     //Log::info($sendTwilio);
                    //     if($sendTwilio){
                    //        return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Twilio'], 200); 
                    //     }else{
                    //         return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                    //     }
                    // }
                }
                
                
            }
            else{
                $content = "[#] Eganow Driver: Your verification code is ".$otp.". Drive on Eganow, Drive for your future AaIrz7s/a2I";
                $sendTwilio = sendMessageTwilio($to, $content);
                //Log::info($sendTwilio);
                if($sendTwilio){
                   return response()->json(['success' => TRUE, 'otp' => $otp, 'data' => $sendTwilio], 200); 
                }else{
                    return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                }
            }
        }
        catch (Exception $e) {
            Log::info($e);
             return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function FleetServices() {
        $provider = Provider::find(Auth::user()->id);
        if($Services = FleetPrice::where('fleet_id', $provider->fleet)->where('status',1)->get()) {
            foreach ($Services as $key => $value) {

                $price = ProviderService::where('provider_id',Auth::user()->id)
                            ->where('service_type_id',$value->id)
                            ->first();
                if(count($price)>0){
                    if($price->service_type_id == $value->id){
                        $Services[$key]->available = true;
                    }else{
                        $Services[$key]->available = false;
                    }
                }else{
                        $Services[$key]->available = false;
                }
            }
            return response()->json(['success' => TRUE, 'services'=> $Services], 200);
        } else {
            return response()->json(['error' => 'No Services!'], 200);
        }

    }

    public function fleets(Request $request)
    {

            $this->validate($request, [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
        
            try{
                $distance = Setting::get('fleet_search_radius', '100');
                $latitude = $request->latitude;
                $longitude = $request->longitude;

                $fleets = Fleet::whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                            ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"),'fleets.*')
                            
                    ->orderBy('distance')
                    ->get();
                if(!$fleets)
                {
                    return response()->json(['success' => FALSE,'message' => 'No Fleets available nearby'], 200);   
                }else{
                    
                        return response()->json(['success' => TRUE,'fleets' => $fleets], 200);
                    
                }
                        
            } catch(Exception $e) {
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
            }
    }

    /**
     * Update the services.
     *
     * @return \Illuminate\Http\Response
     */

    public function update_services(Request $request) {

        $this->validate($request, [
                'services' => 'required',
            ]);

        try{
            $checked_services = array();
            $checked_services = explode(',',$request->services);
            $checked_services = array_map('trim',$checked_services);

            
            for ($i=0; $i < count($checked_services); $i++) { 
           
            $ProviderService = ProviderService::where('service_type_id',$checked_services[$i])->where('provider_id',Auth::user()->id)->first();

                if($ProviderService){
                     
                    $ProviderService->status = 'active';
                }
                else{
                    $ProviderService = new ProviderService;
                    $ProviderService->status = 'active';
                }

                   
                    $ProviderService->provider_id = Auth::user()->id;
                    $ProviderService->service_type_id = $checked_services[$i];
                    $ProviderService->save();
            }


            return response()->json(['success'=>TRUE, 'message' => "Services Updated"], 200); 


    } catch(Exception $e) { 

            if($request->ajax()){
                return response()->json(['error' => "try again later"], 500);
            }else{
                return back()->with('flash_error', 'Something went wrong');
            }
            
        }

    }


      /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */


    public function upcoming_request() {

        try{

            $Jobs = UserRequests::where('provider_id',Auth::user()->id)
                    ->where('status','SCHEDULED')
                    ->with('user', 'provider', 'service_type', 'payment', 'rating', 'provider_profiles')
                    ->get();
           

            return response()->json(['success' => TRUE, 'jobs'=> $Jobs], 200);
            
        }

        catch(Exception $e) { 
            return response()->json(['error' => "Something Went Wrong"]);
        }

    }


    public function target(){

        try{

            $rides = UserRequests::where('provider_id',Auth::user()->id)
                        ->where('status','COMPLETED')
                        ->where('created_at', '>=', Carbon::today())
                        ->with('payment','service_type')
                        ->orderBy('created_at','desc')
                        ->get();

            return response()->json([
                    'rides' => $rides, 
                    'rides_count' => $rides->count(), 
                    'target' => Setting::get('daily_target','0')]);
        }   
        catch(Exception $e) { 
            return response()->json(['error' => "Something Went Wrong"]);
        }
    }

    public function otp_activation(Request $request){

        $this->validate($request, [
                'otp' => 'required',
            ]);
        $User = Auth::user();
        if($request->otp)
        {
            $User->otp_activation = 1;
            $User->save();
            return response()->json(['success' => TRUE, 'User'=> $User], 200);
        }

        else
        {
            return response()->json(['error' => 'Wrong OTP']);
        }

    }

    /**
     * Show the user.
     *
     * @return \Illuminate\Http\Response
     */

    public function user(Request $request) {

        $this->validate($request, [
                'user_id' => 'required|numeric|exists:users,id'
            ]);

        if($User = User::find($request->user_id)) {
            return response()->json(['success' => TRUE, 'data'=> $User], 200);
        } else {
            return response()->json(['error' => 'No User Found!'], 500);
        }

    }

    public function earning_metrics(Request $request)
    {
        $earnings = UserRequests::where('provider_id',Auth::user()->id)->has('payment')->leftJoin('user_request_payments', 'user_requests.id', 'user_request_payments.request_id')->select(DB::Raw("SUM(user_request_payments.driver_earnings) as amount"),DB::raw("DATE_FORMAT(user_request_payments.created_at,'%d-%m-%Y') as date"))->groupBy(DB::raw("DATE_FORMAT(user_request_payments.created_at,'%d %m %Y')"))->orderBy('user_request_payments.created_at','desc')->get();
        $last_trip = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->first();

        return response()->json(['success' => TRUE, 'earnings'=> $earnings, 'last_trip' => $last_trip], 200);
    }

    public function earnings(Request $request)
    {
        $week_earning = $week_tot = $week_com = $week_can = $month_earning = $month_tot = $month_com = $month_can = $today_earning = $today_tot = $today_com = $today_can = $total_earning = $total_tot = $total_com = $total_can = 0;
        $week = $month = $today = $total = array();
        $provider = Provider::where('id',Auth::user()->id)
                    ->with('service','accepted','cancelled')
                    ->first();

        $weekly = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::now()->subWeekdays(7))
                    ->get();
        $today = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::today())
                    ->get();

        $fully = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment','service_type')
                     ->where('created_at', '>=', Carbon::now()->subMonth(30))
                    ->get();

        
        $wallet_balance = $provider->wallet_balance;

        if($request->has('from')){
            $total_req = UserRequests::whereBetween('created_at',[date($request->from), date($request->to)])->where('provider_id',Auth::user()->id)
                    ->with('payment','service_type')
                    ->orderBy('created_at', 'desc')
                    ->get();
            
        }else{
            $total_req = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment','service_type')
                    ->orderBy('created_at', 'desc')
                    ->get();  
        }
        
        

        //Weekly Earnings

        for($i=0; $i < count($weekly); $i++) {
            $week_earning += ($weekly[$i]['payment']['driver_earnings']);
            if($weekly[$i]['status'] == 'COMPLETED'){
                $week_com +=1;
            }
            if($weekly[$i]['status'] == 'CANCELLED'){
                $week_can +=1;
            }
            $week_tot = count($weekly);
        }

        $week['earnings'] = $week_earning;
        $week['total_request'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('created_at', '>=', Carbon::now()->subWeekdays(7))->count();
        $week['completed_request'] = $week_com;
        $week['cancelled_request'] = $week_can;

       

        $week['missed_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',0)->where('created_at', '>=', Carbon::now()->subWeekdays(7))->count();
        $week['rejected_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',2)->where('created_at', '>=', Carbon::now()->subWeekdays(7))->count();

         //Active Working Hours for the week by Driver
        $activeHours = DriverActivity::where('driver_id', Auth::user()->id)->select([DB::raw("SUM(working_time) as activeHours")])->where('created_at', '>=', Carbon::now()->subWeekdays(7))->pluck('activeHours');
        if($activeHours[0] > 0){ 

            if($activeHours[0] >= 60){
                $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
            }else{
                $activeHour = number_format($activeHours[0], 2) . " mins";
            }

            $week['active_hours_format'] = $activeHour;
            $week['active_hours'] = $activeHours[0] / 60;
        }else{
            $week['active_hours_format'] = "N / A";
            $week['active_hours'] = 0;
        }

       //Monthly Earnings

       for($i=0; $i < count($fully); $i++) {
            $month_earning += ($fully[$i]['payment']['driver_earnings']);
            if($fully[$i]['status'] == 'COMPLETED'){
                $month_com +=1;
            }
            if($fully[$i]['status'] == 'CANCELLED'){
                $month_can +=1;
            }
            $month_tot = count($fully);
        }
       $month['earnings'] = $month_earning;
       $month['total_request'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('created_at', '>=', Carbon::now()->subMonth(30))->count();
       $month['completed_request'] = $month_com;
       $month['cancelled_request'] = $month_can;

       $month['missed_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',0)->where('created_at', '>=', Carbon::now()->subMonth(30))->count();
        $month['rejected_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',2)->where('created_at', '>=', Carbon::now()->subMonth(30))->count();

        //Active Working Hours for the month by Driver
        $activeHours = DriverActivity::where('driver_id', Auth::user()->id)->select([DB::raw("SUM(working_time) as activeHours")])->where('created_at', '>=', Carbon::now()->subMonth(30))->pluck('activeHours');
        if($activeHours[0] > 0){ 

            if($activeHours[0] >= 60){
                $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
            }else{
                $activeHour = number_format($activeHours[0], 2) . " mins";
            }

            $month['active_hours_format'] = $activeHour;
            $month['active_hours'] = $activeHours[0] / 60;
        }else{
            $month['active_hours_format'] = "N / A";
            $month['active_hours'] = 0;
        }

       //Daily Earnings

       for($i=0; $i < count($today); $i++) {
            $today_earning += ($today[$i]['payment']['driver_earnings']);
            if($today[$i]['status'] == 'COMPLETED'){
                $today_com +=1;
            }
            if($today[$i]['status'] == 'CANCELLED'){
                $today_can +=1;
            }
            $today_tot = count($today);
        }
       $today['earnings'] = $today_earning;
       $today['total_request'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('created_at', '>=', Carbon::today())->count();
       $today['completed_request'] = $today_com;
       $today['cancelled_request'] = $today_can;

       $today['missed_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',0)->where('created_at', '>=', Carbon::today())->count();
        $today['rejected_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',2)->where('created_at', '>=', Carbon::today())->count();

        //Active Working Hours for today by Driver
        $activeHours = DriverActivity::where('driver_id', Auth::user()->id)->select([DB::raw("SUM(working_time) as activeHours")])->where('created_at', '>=', Carbon::today())->pluck('activeHours');
        if($activeHours[0] > 0){ 

            if($activeHours[0] >= 60){
                $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
            }else{
                $activeHour = number_format($activeHours[0], 2) . " mins";
            }

            $today['active_hours_format'] = $activeHour;
            $today['active_hours'] = $activeHours[0] / 60;
        }else{
            $today['active_hours_format'] = "N / A";
            $today['active_hours'] = 0;
        }

       //Total Earnings
       $total_earnings = array();
       $j=0;
       for($i=0; $i < count($total_req); $i++) {
            $total_earning += ($total_req[$i]['payment']['driver_earnings']);
            if($total_req[$i]['status'] == 'COMPLETED'){
                $total_com +=1;
            }
            if($total_req[$i]['status'] == 'CANCELLED'){
                $total_can +=1;
            }
            $total_tot = count($total_req);
            if($total_req[$i]['payment']['driver_earnings'] > 0){
                $total_earnings[$j]['earnings'] = $total_req[$i]['payment']['driver_earnings'];
                $total_earnings[$j]['date_time'] = date('d-m-Y H:i:s', strtotime($total_req[$i]['payment']['created_at']));
                $j++;
            }

        }
        $total['earnings'] = $total_earning;
        $total['total_request'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->count();
        $total['completed_request'] = $total_com;
        $total['cancelled_request'] = $total_can;
        $total['missed_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',0)->count();
        $total['rejected_rides'] = DriverRequestReceived::where('provider_id', Auth::user()->id)->where('status',2)->count();

        $activeHours = DriverActivity::where('driver_id', Auth::user()->id)->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
        if($activeHours[0] > 0){ 

            if($activeHours[0] >= 60){
                $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
            }else{
                $activeHour = number_format($activeHours[0], 2) . " mins";
            }

            $total['active_hours_format'] = $activeHour;
            $total['active_hours'] = $activeHours[0] / 60;
        }else{
            $total['active_hours_format'] = "N / A";
            $total['active_hours'] = 0;
        }

         $last_trip = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->first();
        if(!$last_trip){
            $last_trip = array();
        }
        return response()->json(['success' => TRUE,  'wallet_balance' => $wallet_balance,'today'=> $today, 'weekly'=> $week, 'month'=> $month, 'total'=> $total, 'earnings' => $total_earnings, 'last_trip'=> $last_trip], 200);
    }

    public function add_money(Request $request){

        $this->validate($request, [
                'amount' => 'required'
            ]);

        try{
                if($request->status == "successful"){
                        $client = new \GuzzleHttp\Client();

                        $url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify";

                        $body['SECKEY'] = env('RAVE_SECRET_KEY');
                        $body['txref'] = $request->payment_id;
                        

                        // $headers = ['Authorization' => 'Bearer '. $access_token, 'Content-Type' => 'application/vnd.identity-specs.v2+json', 'Accept' => 'application/vnd.identity-specs.v2+json'];
                        

                        $res = $client->post($url, ['json' => $body]);

                        // $code = $res->getStatusCode();
                        $transfer = array();
                        $transfer = json_decode($res->getBody(),'true');

                        if($transfer['status'] == "success" && $transfer['data']['status'] == "successful"){

                            

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->last_balance = $update_user->wallet_balance;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            if($request->has('payment_id')){
                                $rave_transactions->rave_ref_id = $request->payment_id;
                            }
                            if($request->has('flwref')){
                                $rave_transactions->flwref = $request->flwref;
                            }
                            $rave_transactions->narration = "Wallet Topup";
                            $rave_transactions->amount = number_format($request->amount,2);
                            if($request->has('app_fee')){
                                $rave_transactions->transaction_fee = $request->app_fee;
                            }
                            $rave_transactions->type = "credit";
                            $rave_transactions->credit = 0;
                            $rave_transactions->status = 1;
                            $rave_transactions->save();

                            $update_user = Provider::find(Auth::user()->id);
                            $update_user->wallet_balance += $request->amount;
                            $update_user->save();
                            
                            $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            //sending push on adding wallet money
                            $response_array =  array('success' => TRUE , 'message' => 'Topup successful', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }else if($transfer['status'] == "success" && $transfer['data']['status'] == "failed"){

                            $update_user = Provider::find(Auth::user()->id);

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            if($request->has('payment_id')){
                                $rave_transactions->rave_ref_id = $request->payment_id;
                            }
                            if($request->has('flwref')){
                                $rave_transactions->flwref = $request->flwref;
                            }
                            $rave_transactions->narration = "Wallet Topup";
                            $rave_transactions->amount = number_format($request->amount,2);
                            if($request->has('app_fee')){
                                $rave_transactions->transaction_fee = $request->app_fee;
                            }
                            $rave_transactions->type = "credit";
                            $rave_transactions->status = 0;
                            $rave_transactions->save();

                            $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            $response_array =  array('success' => FALSE , 'message' => 'Topup failed. Please try later', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }else{

                            $update_user = Provider::find(Auth::user()->id);

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            if($request->has('payment_id')){
                                $rave_transactions->rave_ref_id = $request->payment_id;
                            }
                            if($request->has('flwref')){
                                $rave_transactions->flwref = $request->flwref;
                            }
                            $rave_transactions->narration = "Wallet Topup";
                            $rave_transactions->amount = number_format($request->amount,2);
                            if($request->has('app_fee')){
                                $rave_transactions->transaction_fee = $request->app_fee;
                            }
                            $rave_transactions->type = "credit";
                            $rave_transactions->status = 2;
                            $rave_transactions->save();

                            $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            $response_array =  array('success' => FALSE , 'message' => 'Topup pending. Status will be updated within 24 hours', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }
                }else{
                       $update_user = Provider::find(Auth::user()->id);

                        $code = rand(1000, 9999);
                        $name = substr($update_user->first_name, 0, 2);
                        $reference = "AWD".$code.$name;

                        $rave_transactions = new RaveTransaction;
                        $rave_transactions->driver_id = Auth::user()->id;
                        $rave_transactions->reference_id = $reference;
                        if($request->has('payment_id')){
                            $rave_transactions->rave_ref_id = $request->payment_id;
                        }
                        if($request->has('flwref')){
                            $rave_transactions->flwref = $request->flwref;
                        }
                        $rave_transactions->narration = "Wallet Topup";
                        $rave_transactions->amount = number_format($request->amount,2);
                        if($request->has('app_fee')){
                            $rave_transactions->transaction_fee = $request->app_fee;
                        }
                        $rave_transactions->type = "credit";
                        $rave_transactions->status = 2;
                        $rave_transactions->save();

                        $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                        $response_array =  array('success' => FALSE , 'message' => 'Topup pending. Status will be updated within 24 hours', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                }
            

        } catch(Exception $e) { 
            Log:info($e);
            $response_array = array('success' => 'false' , 'error' => 'Something Went Wrong!');
        }
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function wallet_balance()
    {
        $driver = Provider::find(Auth::user()->id);
            
            //Verify the Wallet Topup Pending transaction
            $credit_pending_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('rave_ref_id', '!=', '')->where('status', 2)->where('type', 'credit')->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {

                $client = new \GuzzleHttp\Client();

                $url = "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify";

                $body['SECKEY'] = env('RAVE_SECRET_KEY');
                $body['txref'] = $credit_pending_transaction->rave_ref_id;
                

                // $headers = ['Authorization' => 'Bearer '. $access_token, 'Content-Type' => 'application/vnd.identity-specs.v2+json', 'Accept' => 'application/vnd.identity-specs.v2+json'];
                

                $res = $client->post($url, ['json' => $body]);

                // $code = $res->getStatusCode();
                $transfer = array();
                $transfer = json_decode($res->getBody(),'true');

                if($transfer['status'] == "success" && $transfer['data']['status'] == "successful"){

                    $credit_pending_transaction->last_balance = $driver->wallet_balance;
                    $driver = Provider::find(Auth::user()->id);
                    $driver->wallet_balance += $transfer['amount'];
                    $driver->save();
                    $credit_pending_transaction->driver_id = Auth::user()->id;
                    $credit_pending_transaction->rave_ref_id = $transfer['txref'];
                    $credit_pending_transaction->flwref = $transfer['flwref'];
                    $credit_pending_transaction->narration = "Wallet Topup";
                    $credit_pending_transaction->amount = $transfer['amount'];
                    $credit_pending_transaction->transaction_fee = $transfer['appfee'];
                    $credit_pending_transaction->status = 1;
                    $credit_pending_transaction->save();
                }else if($transfer['status'] == "success" && $transfer['data']['status'] == "failed"){
                    $credit_pending_transaction->status = 0;
                    $credit_pending_transaction->narration = "Wallet topup failed";
                }
                $credit_pending_transaction->save();
            }

            //Verify Withdraw pending transaction status

            $debit_pending_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('rave_ref_id', '!=', '')->where('status', 2)->where('type', 'debit')->orderBy('created_at', 'desc')->get();
            foreach ($debit_pending_transactions as $debit_pending_transaction) {

                $client = new \GuzzleHttp\Client();

                $url = "https://api.flutterwave.com/v3/transfers?id=".$debit_pending_transaction->rave_ref_id;

                $headers = ['Authorization' => 'Bearer '. env('RAVE_SECRET_KEY'), 'Content-Type' => 'application/json'];
                

                $res = $client->get($url, ['headers' => $headers]);

                // $code = $res->getStatusCode();
                $result = array();
                $result = json_decode($res->getBody(),'true');

                if($result['status'] == 'success' && count($result['data']) > 0){
                    if($result['data'][0]['status'] == 'SUCCESSFUL'){
                        $debit_pending_transaction->last_balance = $driver->wallet_balance;
                        $debit_pending_transaction->narration = "Withdrawal of ".currency($result['data'][0]['amount'])." successful";
                        $debit_pending_transaction->amount = number_format($result['data'][0]['amount'],2);
                        $debit_pending_transaction->transaction_fee = $result['data'][0]['fee'];
                        $debit_pending_transaction->status = 1;
                        $debit_pending_transaction->type = "debit";
                        $debit_pending_transaction->save();
                        $Provider = Provider::find(Auth::user()->id);
                        $Provider->wallet_balance = $Provider->wallet_balance - ($result['data'][0]['amount'] +  $result['data'][0]['fee']);
                       
                        $Provider->save();
                    }else if($result['data'][0]['status'] == 'FAILED'){
                        $debit_pending_transaction->last_balance = $driver->wallet_balance;
                        $debit_pending_transaction->narration = currency($result['data'][0]['amount'])." withdrawal failed";
                        $debit_pending_transaction->amount = number_format($result['data'][0]['amount'],2);
                        $debit_pending_transaction->transaction_fee = $result['data'][0]['fee'];
                        $debit_pending_transaction->status = 0;
                        $debit_pending_transaction->type = "debit";
                        $debit_pending_transaction->save();
                        $Provider = Provider::find(Auth::user()->id);
                        $Provider->wallet_balance += $result['data'][0]['amount'] +  $result['data'][0]['fee'];
                        
                        $Provider->save();

                    }else if($result['data'][0]['status'] == 'PENDING'){

                        $debit_pending_transaction->narration = currency($result['data'][0]['amount'])." withdrawal pending";
                        $debit_pending_transaction->amount = number_format($result['data'][0]['amount'],2);
                        $debit_pending_transaction->transaction_fee = $result['data'][0]['fee'];
                        $debit_pending_transaction->status = 2;
                        $debit_pending_transaction->type = "debit";
                        $debit_pending_transaction->save();
                    }else if($result['data'][0]['status'] == 'NEW'){

                        $debit_pending_transaction->narration = currency($result['data'][0]['amount'])." withdrawal pending";
                        $debit_pending_transaction->amount = number_format($result['data'][0]['amount'],2);
                        $debit_pending_transaction->transaction_fee = $result['data'][0]['fee'];
                        $debit_pending_transaction->status = 2;
                        $debit_pending_transaction->type = "debit";
                        $debit_pending_transaction->save();
                    }
                }
            }
            
        

        $available_balance_duration = Setting::get('available_balance_time', '24');
        $credit = $debit = 0;

        //Transactions for available balance calculation
        $available_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('status', 1)->where('credit', '!=', 1)->where('created_at', '<=', Carbon::now()->subHours($available_balance_duration))->orderBy('created_at', 'desc')->get();

        foreach ($available_transactions as $available_transaction) {
                // if($available_transaction->type == 'credit'){
                    $credit += $available_transaction->amount;
                // }else if($available_transaction->type == 'debit'){
                //     $debit += $available_transaction->amount;
                // }
                $available_transaction->credit = 1;
                $available_transaction->last_balance = $driver->wallet_balance;
                $available_transaction->save();      
        }
        $available_balance = $credit;
        $driver->available_balance += $available_balance;  
        $driver->save();

        $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
        $earnings = RaveTransaction::where('driver_id', Auth::user()->id)->where('type', 'credit')->sum('amount');
        $withdraw = RaveTransaction::where('driver_id', Auth::user()->id)->where('type', 'debit')->sum('amount');
        $today_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('created_at', '>=', Carbon::now()->subHours(24))->get();
        if(count($today_transactions) == 0){
            $driver->starting_balance = $driver->wallet_balance;
        }else{
            $driver->starting_balance = $today_transactions[0]->last_balance;
        }
        $driver->save();
        

                            

        $response_array = array(
            'success' => true,
            'wallet_balance' => number_format($driver->wallet_balance,2),
            'available_balance' => number_format($driver->available_balance,2),
            'earnings' => number_format($earnings,2),
            'withdraw' => number_format($withdraw,2),
            'transactions' => $transactions,
            'starting_balance' => $driver->starting_balance,
            
        );
        $response = response()->json($response_array, 200);
        return $response;
    }

        public function add_money_sp(Request $request){

        $this->validate($request, [
                'amount' => 'required'
            ]);

        try{
             $User = Provider::find(Auth::user()->id);
             $payToken = $request->payToken;
             if($request->has('mobile')){
                $mobile = $request->mobile;
             }else if($request->has('mobile_number')){
                $mobile = $request->mobile_number;
             }else{
                $mobile = $User->mobile;
             }

            if(str_contains($request->network,"AIRTEL") == true){
                $request->network = "AIRTELTIGO_MONEY";
             }else if(str_contains($request->network,"VODAFONE") == true){
                $request->network = "VODAFONE_CASH";
             }

                $code = rand(100000, 999999);
                $name = substr($User->first_name, 0, 2);
                $req_id = $name.$code;
                $trans_id = "UWT".$code;
                $amount = number_format($request->amount,2);
            //SlydePay Send Invoice and Confirm Payment
            if($request->has('payment_mode') && $request->payment_mode == "MOBILE"){

                $client = new \GuzzleHttp\Client();
                $invoice_url = "https://posapi.usebillbox.com/webpos/payNow";
                $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];

                $res = $client->post($invoice_url, [ 
                    'headers' => $headers,
                    'json' => ["requestId"=> $req_id,
                                "appReference"=> "Eganow",
                                "secret"=> "EganowTech1#",
                                "serviceCode"=> "670",
                                "amount"=> $amount,
                                "currency"=> "GHS",
                                "customerName"=> $User->first_name ." ". $User->last_name,
                                "customerSegment"=> "",
                                "reference"=> "Wallet Topup of ".$request->amount,
                                "transactionId" => $trans_id,
                                "provider" => $request->network,
                                "walletRef" => $mobile,
                                "customerName" => $User->first_name ." ". $User->last_name,
                                "customerMobile" => $mobile]]);

                $code = $res->getStatusCode();
                $result = array();
                $result = json_decode($res->getBody(),'true');
                Log::info($result);
                if($result['success'] != 'true'){
                    return response()->json(['success' => FALSE, 'message' => 'Payment Failed, Try Again!']); 
                }

            } 


                    $rave_transactions = new RaveTransaction;
                    $rave_transactions->driver_id = Auth::user()->id;
                    $rave_transactions->reference_id = $req_id;
                    $rave_transactions->rave_ref_id = $trans_id;
                    if($request->has('flwref')){
                        $rave_transactions->flwref = $request->flwref;
                    }
                    $rave_transactions->narration = "Wallet Topup";
                    $rave_transactions->amount = number_format($request->amount,2);
                    if($request->has('app_fee')){
                        $rave_transactions->transaction_fee = $request->app_fee;
                    }
                    $rave_transactions->type = "credit";
                    $rave_transactions->status = 2;
                    $rave_transactions->save();
                    
                    $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                //sending push on adding wallet money
                $response_array =  array('success' => TRUE , 'message' => 'Please check your phone for prompt to complete payment', 'wallet_balance' => number_format($User->wallet_balance,2), $transactions);
            

        } catch(Exception $e) { 
            Log:info($e);
            $response_array = array('success' => FALSE , 'error' => 'Something Went Wrong!');
        }
        $response = response()->json($response_array, 200);
        return $response;
    }



    public function wallet_balance_sp(Request $request)
    {
        
        $user = Provider::find(Auth::user()->id);
        $code = rand(1000, 9999);
        $name = substr($user->first_name, 0, 2);
        $reference = "AWT".$code.$name;
        
        $credit_pending_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('status', 2)->where('type', 'credit')->where('credit', '!=', 1)->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                 $payToken = $credit_pending_transaction->rave_ref_id;
                
                try{
                    $client1 = new \GuzzleHttp\Client();
                    $status_url = "https://posapi.usebillbox.com/webpos/checkPaymentStatus";
                    $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];
                    
                    $status = $client1->post($status_url, [ 
                        'headers' => $headers,
                        'json' => ["requestId" => $reference,
                                    "appReference" => "Eganow",
                                    "secret" => "EganowTech1#",
                                    "transactionId" => $payToken]]);

                    $result = array();
                    $result = json_decode($status->getBody(),'true');
                    
                        Log::info("Driver Wallet balance status: ". $payToken." - ". $result['result']['status']);
                        if($result['success'] == TRUE && $result['result']['status'] == "CONFIRMED"){

                            $credit_pending_transaction->last_balance = $user->wallet_balance;
                            $user = Provider::find(Auth::user()->id);
                            $user->wallet_balance = $user->wallet_balance + $credit_pending_transaction->amount;
                            $user->save();
                            $credit_pending_transaction->flwref = $result['result']['receiptNo'];
                            $credit_pending_transaction->narration = "Wallet Topup";
                            $credit_pending_transaction->status = 1;
                            $credit_pending_transaction->save();
                        }else if($result['success'] == TRUE && $result['result']['status'] == "FAILED"){
                            $credit_pending_transaction->status = 0;
                            $credit_pending_transaction->narration = "Wallet topup failed";
                        }else if($result['success'] == TRUE && $result['result']['status'] == "PENDING"){
                            $credit_pending_transaction->status = 2;
                            $credit_pending_transaction->narration = "Wallet topup Pending";
                        }
                        $credit_pending_transaction->save();
                }catch(Exception $e){
                    
                        $credit_pending_transaction->status = 2;
                        $credit_pending_transaction->narration = "Wallet Topup";
                        $credit_pending_transaction->save();
                }
            }
        $user = Provider::find(Auth::user()->id);

        $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();


        $available_balance_duration = Setting::get('available_balance_time', '24');
        $credit = $debit = 0;
        $driver = Provider::find(Auth::user()->id);

        //Transactions for available balance calculation
        $available_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('status', 1)->where('type', 'credit')->where('credit', 0)->where('created_at', '<=', Carbon::now()->subHours($available_balance_duration))->orderBy('created_at', 'desc')->get();

         foreach ($available_transactions as $available_transaction) {
                $available_transaction->last_availbale_balance = $driver->available_balance;
                if($available_transaction->type == 'credit' && $available_transaction->credit == 0){
                    $driver->available_balance += $available_transaction->amount;
                    $driver->save();  
                }
                    // else if($available_transaction->type == 'debit'){
                //     $debit += $available_transaction->amount;
                // }
                $available_transaction->credit = 1;
                $available_transaction->save();      
        }
        // $available_balance = $credit;
        // $driver->available_balance += $available_balance;  
        // $driver->save();

        $transactions = RaveTransaction::where('driver_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
        $earnings = RaveTransaction::where('driver_id', Auth::user()->id)->where('type', 'credit')->sum('amount');
        $withdraw = RaveTransaction::where('driver_id', Auth::user()->id)->where('type', 'debit')->sum('amount');
        $today_transactions = RaveTransaction::where('driver_id', Auth::user()->id)->where('created_at', '>=', Carbon::now()->subHours(24))->get();
        if(count($today_transactions) == 0){
            $driver->starting_balance = $driver->wallet_balance;
        }else{
            $driver->starting_balance = $today_transactions[0]->last_balance;
        }
        $driver->save();
        

                            

        $response_array = array(
            'success' => true,
            'wallet_balance' => number_format($driver->wallet_balance,2),
            'available_balance' => number_format($driver->available_balance,2),
            'earnings' => number_format($earnings,2),
            'withdraw' => number_format($withdraw,2),
            'transactions' => $transactions,
            'starting_balance' => $driver->starting_balance,
            
        );
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function chat_history(Request $request)
    {
        $this->validate($request, [
                'request_id' => 'required|integer'
            ]);
        try{

$Chat = array();
    
            $Chat['data'] = Chat::where('request_id',$request->request_id)
                        //->where('provider_id', \Auth::user()->id)
                        ->get();
            return response()->json($Chat);
        }catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }

    public function change_destination(Request $request){

        $this->validate($request, [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

        if($user = UserRequests::find($request->request_id)){
                 $change_destination = ChangeDestination::where('request_id',$request->request_id)->where('user_id', $user->user_id)->where('status', 0)->first();
                
                $UserRequest = UserRequests::find($request->request_id);

                if($request->change == 0){
                    if($change_destination){
                        $change_destination->status = 2;
                        $change_destination->save();
                    }

                    if($UserRequest->service_type->is_delivery == 1){
                        $service_flow = "Delivery";
                    }else{
                        $service_flow= "Ride";
                    }
                    (new SendPushNotification)->DriverChangeDestinationReject($user->user_id, $service_flow);
                    return response()->json(['success' => FALSE, 'message' => trans('api.user.driver_change_destination_reject')]);
                }else{
                    if($change_destination){
                        $change_destination->status = 1;
                        $change_destination->save();   
                    }
                    
                    $user->d_latitude = $request->latitude;
                    $user->d_longitude = $request->longitude;
                    $user->d_address = $request->address;
                    if($request->has('title')){
                        $user->d_title = $request->title;
                    }
                    $user->reroute = 3;
                    $user->save();

                    if($UserRequest->service_type->is_delivery == 1){
                        $service_flow = "Delivery";
                    }else{
                        $service_flow= "Ride";
                    }
                    // Send push to driver for destination changed by the user
                    (new SendPushNotification)->DriverChangeDestination($user->user_id, $service_flow);

                    return response()->json(['success' => TRUE, 'message' => trans('api.user.destination_updated')]);

                }
        }else{

                return response()->json(['success' => FALSE, 'message' => trans('api.user.destination_cant_change')], 200);
            }

    }

    public function change_destination_request(Request $request){

        $this->validate($request, [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);

        if($user = UserRequests::find($request->request_id)){
            
            $UserRequest = UserRequests::find($request->request_id);

            $change_destination = ChangeDestination::where('request_id',$request->request_id)->where('status', 0)->first();
            if(count($change_destination) == 0){
                $change_destination = new ChangeDestination;
                $change_destination->latitude = $request->latitude;
                $change_destination->longitude = $request->longitude;
                $change_destination->address = $request->address;
                $change_destination->title = $request->title;
                $change_destination->fare = $request->fare;
                $change_destination->request_id = $request->request_id;
                $change_destination->driver_id = Auth::user()->id;
                $change_destination->save();

                if($UserRequest->service_type->is_delivery == 1){
                        $service_flow = "Delivery";
                    }else{
                        $service_flow= "Ride";
                    }

                (new SendPushNotification)->DriverChangeDestinationRequest($user->user_id, $request->latitude, $request->longitude, $request->request_id, $request->address, $request->fare, $request->title, $service_flow);

                return response()->json(['success' => TRUE, 'message' => trans('api.user.destination_request_sent')]);

            }else{
                return response()->json(['success' => FALSE, 'message' => trans('api.user.live_destination_cant_change')], 200);
            }

        }else{

            return response()->json(['success' => FALSE, 'message' => trans('api.user.destination_cant_change')], 200);

        }

    }

    public function change_destination_estimation(Request $request){

        $this->validate($request, [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);

        if($user = UserRequests::find($request->request_id)){


            $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$user->s_latitude.",".$user->s_longitude."&destinations=".$request->latitude.",".$request->longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

           Log::info(env("GOOGLE_MAP_KEY"));

            // $client = new Client(); //GuzzleHttp\Client
            // $result = $client->get($details);

            $json = curl($details);

            $details = json_decode($json, TRUE);
            $meter = $details['rows'][0]['elements'][0]['distance']['value'];
            $time = $details['rows'][0]['elements'][0]['duration']['text'];
            $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

            $kilometer = round($meter/1000);
            $minutes = round($seconds/60);

            $tax_percentage = Setting::get('tax_percentage');
            $commission_percentage = Setting::get('commission_percentage');
            
            
            $service_type = FleetPrice::where('service_id', $user->service_type_id)->where('fleet_id', $user->fleet_id)->first();
            if(!$service_type){
                $service_type = ServiceType::findOrFail($user->service_type_id);
            }

            $price_base = $service_type->fixed;
            $time_price = $service_type->time* $minutes;
            $distance_price = $kilometer * $service_type->price;

            if(Setting::get('surge_percentage') != 0){
                $price_base = $price_base * Setting::get('surge_percentage');
                $time_price = $time_price * Setting::get('surge_percentage');
                $distance_price = $distance_price * Setting::get('surge_percentage');
            }
            $total = $price_base + $time_price + $distance_price;

            $commission = ( $commission_percentage/100 ) * $total;

            $tax_price = ( $tax_percentage/100 ) * $total;
            $total = $total + $tax_price + $commission;

            $ActiveProviders = ProviderService::AvailableServiceProvider($user->service_type_id)->get()->pluck('provider_id');

            $distance = Setting::get('provider_search_radius', '10');
            $latitude = $user->s_latitude;
            $longitude = $user->s_longitude;

            $Providers = Provider::whereIn('id', $ActiveProviders)
                ->where('status', 'approved')
                ->where('fleet', $user->fleet_id)
                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                ->get();

            
            $Discount = 0; // Promo Code discounts should be added here.

            if($PromocodeUsage = PromocodeUsage::where('user_id',$user->user_id)->where('status','ADDED')->first()){
                if($Promocode = Promocode::find($PromocodeUsage->promocode_id)){
                    if($Promocode->discount != 0){
                        $Discount = $total * ( $Promocode->discount / 100);
                    }
                    $PromocodeUsage->status ='USED';
                    $PromocodeUsage->save();
                }
            }
            

            $total = $total - $Discount;
            if($total <= $service_type->minimum_fare){
                $total = $service_type->minimum_fare;
            }

            return response()->json([
                    'success' => TRUE,
                    'estimated_fare' => number_format($total,2), 
                    'distance' => $kilometer,
                    'distance_price' => number_format($distance_price,2),
                    'time' => $time,
                    'time_price' => number_format($time_price,2),
                    'tax_price' => number_format($tax_price,2),
                    'base_price' => number_format($service_type->fixed,2),
                    'discount' => number_format($Discount,2),
                    'destination' => $request->address,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                ],200);
            return response()->json(['success' => TRUE, 'message' => trans('api.user.destination_updated')]);

        }else{

            return response()->json(['success' => FALSE, 'message' => trans('api.user.destination_cant_change')], 200);

        }

    }

    public function set_destination(Request $request){

            $location = DriverLocation::find($request->address_id);
            $location->is_default = 1;
            $location->save();
            $make_off_loc = DriverLocation::where('id', '!=', $request->address_id)->update(['is_default' => 0]);
            $latitude = $location->latitude;
            $longitude = $location->longitude;
            $address = $location->address;
       
        if($provider = Provider::find(Auth::user()->id)){

            $provider->d_latitude = $latitude;
            $provider->d_longitude = $longitude;
            $provider->address = $address;
            $provider->save();

            return response()->json(['success' => TRUE, 'message' => trans('api.user.set_destination')]);

        }else{

            return response()->json(['success' => FALSE, 'message' => trans('api.user.destination_cant_change')], 200);

        }

    }

    public function add_location(Request $request) {

        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required|max:255',
            'title' => 'required|max:255'
            ]);


            $location = new DriverLocation;
            $location->driver_id = Auth::user()->id;
            $location->title = $request->title;
            $location->latitude = $request->latitude;
            $location->longitude = $request->longitude;
            $location->address = $request->address;

            if(!$check_location_default = DriverLocation::where('driver_id' , Auth::user()->id)->count()){
                $location->is_default = 1;
            }
            $location->save();

            $provider = Provider::find(Auth::user()->id);
            $provider->d_latitude = $location->latitude;
            $provider->d_longitude = $location->longitude;
            $provider->address = $location->address;
            $provider->save();

            $response_array = array('success' => true, 'message' => trans('api.user.location_saved'), 'location' => $location);

        

        return response()->json($response_array, 200);

    }

    public function get_locations(Request $request) {
        
        $locations = DriverLocation::where('driver_id' , Auth::user()->id)
                    ->select('driver_locations.id as location_id','driver_locations.latitude','driver_locations.longitude' ,'driver_locations.title',
                        'driver_locations.address', 'driver_locations.is_default')
                    ->get()
                    ->toArray();

        $response_array = array('success' => true , 'locations' => $locations);

        return response()->json($response_array , 200);
    }

    public function set_destination_activation(Request $request){
        try{

            $Provider = Auth::user();
            $location = DriverLocation::where('driver_id' , $Provider->id)->get();
            if(count($location) > 0){
                $Provider = Auth::user();
                $Provider->sd_activation = $request->status;
                $Provider->save();
            }else{
                return response()->json(['success' => FALSE, 'data'=> $Provider, 'message' => 'Sorry! You have not added any destination yet!'], 200);
            }
            
            return response()->json(['success' => TRUE, 'data'=> $Provider, 'message' => 'Set Destination status changed'], 200);
        }catch (Exception $e) {
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }

    }

    public function withdraw(Request $request){
        try{
            Log::info($request->all());
            $ProviderBank = ProviderProfile::where('provider_id', Auth::user()->id)->first();
            if($ProviderBank->acc_no == ""){
                return response()->json(['success' => FALSE, 'message' => 'Bank / Mobile Money account has not been configured'], 200);
            }
                $Provider = Auth::user();
                $code = rand(1000, 9999);
                $name = substr($Provider->first_name, 0, 2);
                $reference = "DWT".$code.$name;
                $mobile_money = array('MTN','VODAFONE', 'TIGO', 'AIRTEL' );;
                if (in_array($ProviderBank->bank_code, $mobile_money)) {
                    if($ProviderBank->acc_no[0] == "0"){
                        $momo_number = substr($ProviderBank->acc_no, 1);
                    }else{
                        $momo_number = $ProviderBank->acc_no;
                    }
                    $account_number = "233".$momo_number;
                }else{
                    $account_number = $ProviderBank->acc_no;
                }
            if($Provider->available_balance > 0 && $Provider->available_balance <= $request->amount){
                return response()->json(['success' => FALSE, 'message' => 'You do not have enough balance to withdraw'], 200);
            }else{



                    //Create Transfer 
                    $client = new \GuzzleHttp\Client();

                    $url = "https://api.ravepay.co/v2/gpx/transfers/create";

                    $body['account_bank'] = $ProviderBank->bank_code;
                    $body['account_number'] = $account_number;
                    $body['amount'] =$request->amount;
                    $body['seckey'] = env('RAVE_SECRET_KEY');
                    $body['narration'] = "Driver Wallet withdraw";
                    $body['currency'] = "GHS";
                    $body['reference'] = $reference;
                    $body['destination_branch_code'] = "GH030100"; // call the get branch code endpoint to get a list of bank codes.
                    $body['beneficiary_name'] = $Provider->first_name .' '. $Provider->last_name; // only pass this for non NGN 
                    

                    // $headers = ['Authorization' => 'Bearer '. $access_token, 'Content-Type' => 'application/vnd.identity-specs.v2+json', 'Accept' => 'application/vnd.identity-specs.v2+json'];
                    

                    $res = $client->post($url, ['json' => $body]);

                    // $code = $res->getStatusCode();
                    $transfer = array();
                    $transfer = json_decode($res->getBody(),'true');  

                    //Check Transfer

                    $client = new \GuzzleHttp\Client();

                    $url = "https://api.flutterwave.com/v3/transfers?id=".$transfer['data']['id'];

                    $headers = ['Authorization' => 'Bearer '. env('RAVE_SECRET_KEY'), 'Content-Type' => 'application/json'];
                    

                    $res = $client->get($url, ['headers' => $headers]);

                    // $code = $res->getStatusCode();
                    $result = array();
                    $result = json_decode($res->getBody(),'true');

                    Log::info($result);
                    
                    if($result['status'] == 'success'){
                        if($result['data'][0]['status'] == 'SUCCESSFUL'){
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->rave_ref_id = $transfer['data']['id'];
                            $rave_transactions->narration = "Withdrawal of ".currency($request->amount)." successful";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->transaction_fee = $transfer['data']['fee'];
                            $rave_transactions->status = 1;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                            $Provider->wallet_balance -= $request->amount +  $transfer['data']['fee'];
                            $Provider->available_balance -= $request->amount +  $transfer['data']['fee'];
                            $Provider->save();
                            return response()->json(['success' => TRUE, 'driver' => $Provider ,'data'=> $rave_transactions, 'message' => 'Transaction Successful, Funds will be deposited shortly'], 200);
                        }else if($result['data'][0]['status'] == 'FAILED'){
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->rave_ref_id = $transfer['data']['id'];
                            $rave_transactions->narration = currency($request->amount)." withdrawal failed";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->transaction_fee = $transfer['data']['fee'];
                            $rave_transactions->status = 0;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                            return response()->json(['success' => FALSE, 'data'=> $rave_transactions, 'message' => 'Withdrawal request failed. Please Try again'], 200);
                        }else if($result['data'][0]['status'] == 'PENDING'){
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->rave_ref_id = $transfer['data']['id'];
                            $rave_transactions->narration = currency($request->amount)." withdrawal pending";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->transaction_fee = $transfer['data']['fee'];
                            $rave_transactions->status = 2;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                            $Provider->wallet_balance -= $request->amount +  $transfer['data']['fee'];
                            $Provider->available_balance -= $request->amount +  $transfer['data']['fee'];
                            return response()->json(['success' => FALSE, 'data'=> $rave_transactions, 'message' => 'Withdrawal request pending. Wait for the confirmation'], 200);
                        }else if($result['data'][0]['status'] == 'NEW'){
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->rave_ref_id = $transfer['data']['id'];
                            $rave_transactions->narration = currency($request->amount)." withdrawal pending";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->transaction_fee = $transfer['data']['fee'];
                            $rave_transactions->status = 2;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                            $Provider->wallet_balance -= $request->amount +  $transfer['data']['fee'];
                            $Provider->available_balance -= $request->amount +  $transfer['data']['fee'];
                            return response()->json(['success' => FALSE, 'data'=> $rave_transactions, 'message' => 'Withdrawal request pending. Wait for the confirmation'], 200);
                        }
                    }else{
                            return response()->json(['success' => FALSE, 'message' => 'Withdrawal has been failed. Please Try again'], 200);
                    }
                }
            
        }catch(Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function withdraw_sp(Request $request){
        try{
            return response()->json(['success' => FALSE, 'message' => "Temporarily, withdrawal feature is disabled!"], 200);
            Log::info($request->all());

            if($request->has('acc_no')){

                $Provider = new DriverAccounts;

                if($request->has('acc_no')) {
                    $Provider->acc_no = $request->acc_no;
                }

                if($request->has('acc_name')) {
                    $Provider->acc_name = $request->acc_name;
                }

                if ($request->has('bank_name')){
                    $Provider->bank_name = $request->bank_name;
                }

                if ($request->has('bank_name_id')){
                    $Provider->bank_name_id = $request->bank_name_id-1;
                }

                if ($request->has('bank_code')){
                    $Provider->bank_code = $request->bank_code;
                }
                $Provider->driver_id = Auth::user()->id;
                
                $Provider->is_active = 1;
                
                $Provider->save();
            }
            $ProviderBanks = DriverAccounts::where('driver_id', Auth::user()->id)->get();

            if(count($ProviderBanks) > 1){
                $ProviderBank = DriverAccounts::where('driver_id', Auth::user()->id)->where('is_active',1)->first();
            }else{
                $ProviderBank = DriverAccounts::where('driver_id', Auth::user()->id)->first();
            }
            
            if(count($ProviderBank) ==0){
                $ProviderBank = ProviderProfile::where('provider_id', Auth::user()->id)->first();
            }

            if($ProviderBank->acc_no == ""){
                return response()->json(['success' => FALSE, 'message' => 'Bank / Mobile Money account has not been configured'], 200);
            }
                $Provider = Auth::user();
                $code = rand(1000, 9999);
                $name = substr($Provider->first_name, 0, 2);
                $reference = "AWD".$code.$name;
                $banks = array("SLYDEPAY", "MTN_MONEY", "AIRTEL_MONEY", "VODAFONE_CASH", "nib-account-fi-service", "prudential-account-fi-service", "gt-account-fi-service", "heritage-account-fi-service", "fnb-account-fi-service", "sovereign-account-fi-service", "umb-account-fi-service", "zenith-account-fi-service", "baroda-account-fi-service", "access-account-fi-service", "cal-account-fi-service", "energy-account-fi-service", "standardchartered-account-fi-service", "ecobank-account-fi-service", "barclays-account-fi-service", "gcb-account-fi-service", "stanbic-account-fi-service", "adb-account-fi-service", "uba-account-fi-service", "royal-account-fi-service", "fidelity-account-fi-service" );

                if(!in_array($ProviderBank->bank_code, $banks)){
                    return response()->json(['success' => FALSE, 'message' => 'Bank / Mobile Money account has not been configured'], 200);
                }
                $mobile_money = array('MTN_MONEY','VODAFONE_CASH', 'VODAFONE_CASH_PROMPT', 'AIRTEL_MONEY' );
                if (in_array($ProviderBank->bank_code, $mobile_money)) {
                    if($ProviderBank->bank_code != "MTN_MONEY" && $ProviderBank->acc_no[0] == "0"){
                        $momo_number = substr($ProviderBank->acc_no, 1);
                    }else{
                        $momo_number = $ProviderBank->acc_no;
                    }
                    $account_number = $momo_number;
                }else{
                    $account_number = $ProviderBank->acc_no;
                }
            if($Provider->wallet_balance <= 0 || $Provider->available_balance <= 0 || $Provider->available_balance < $request->amount || $Provider->wallet_balance < $Provider->available_balance || $Provider->wallet_balance < $request->amount){
                return response()->json(['success' => FALSE, 'message' => 'Withdrawal failed. Please try later'], 200);
            }else{

                $client = new \GuzzleHttp\Client();

                    $url = "https://app.slydepay.com/api/partnerconnect/sendmoney";

                    $body['email'] = $Provider->email;
                    $body['amount'] = $request->amount;
                    $body['receiverName'] = $Provider->first_name;
                    $body['receiverNumber'] = $account_number;
                    $body['refNo'] = $reference;
                    $body['message'] = "Driver Withdraw";
                    $body['accountType'] = $ProviderBank->bank_code;
                    $body['accountRef'] = $account_number;
                    $body['agencyKey'] = "1598053938201";
                    $body['agencyUsername'] = "payouts@Eganow.technology";
                    $body['agencyPassword'] = "EganowTech1#";      
                    // dd($body);          

                    // $headers = ['Authorization' => 'Bearer '. $access_token, 'Content-Type' => 'application/vnd.identity-specs.v2+json', 'Accept' => 'application/vnd.identity-specs.v2+json'];
                    
// Log::info("End Point: ". $url ."Body: ".json_encode($body));
                    $res = $client->post($url, ['json' => $body]);

                    // $code = $res->getStatusCode();
                    $transfer = array();
                    $transfer = json_decode($res->getBody(),'true');
                    Log::info("SlydePay Withdraw Response: ". json_encode($transfer));
                   
                    if($transfer['success'] == 'true'){
                            $Provider = Provider::where('id',Auth::user()->id)->first();
                            $Provider->wallet_balance = $Provider->wallet_balance - $request->amount;
                            $Provider->available_balance = $Provider->available_balance - $request->amount;
                            $Provider->save();
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->last_availbale_balance = $Provider->available_balance;
                            $rave_transactions->rave_ref_id = $transfer['transactionId'];
                            $rave_transactions->narration = "Wallet Withdrawal";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->status = 1;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                           
                            return response()->json(['success' => TRUE, 'driver' => $Provider ,'data'=> $rave_transactions, 'message' => 'Transaction Successful, Funds will be deposited shortly'], 200);
                    }else{
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = Auth::user()->id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->last_availbale_balance = $Provider->available_balance;
                            $rave_transactions->rave_ref_id = $transfer['transactionId'];
                            $rave_transactions->narration = "Wallet Withdrawal failed";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->status = 0;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                        return response()->json(['success' => FALSE, 'message' => "Withdrawal failed. Please try later!"], 200);
                    }
                
            }
        }catch(Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function redeem_referral(Request $request){

        try{
                $input_promo = strtoupper($request->promocode);
                $User = Provider::find(Auth::user()->id);
            
                if($input_promo == $User->referal){
                    if($request->ajax()){

                        return response()->json(['success' => FALSE, 'message' => "You can't use your own referral code"], 200);

                    }else{
                        return back()->with('flash_error', trans('api.promocode_expired'));
                    }
                }else if($User->referral_used ==1){
                    if($request->ajax()){

                        return response()->json(['success' => FALSE, 'message' => 'Referral code has already been used'], 200);

                    }else{
                        return back()->with('flash_error', trans('api.promocode_expired'));
                    }

                }else{
                    $bonus = 0;
                    $marketer = Marketers::where('referral_code', $input_promo)->first();
                    $user_referal = User::where('referal', $input_promo)->first();
                    $driver_referal = Provider::where('referal', $input_promo)->first();
                    if(count($user_referal) !=0 || count($driver_referal) !=0 || count($marketer) !=0 ){
                        if($user_referal){  
                            $bonus = Setting::get('user_to_driver_referral', 0);
                            $User->user_referred = $input_promo;
                            $User->wallet_balance += $bonus;
                            $User->referral_used = 1;
                            $User->save();
                        }else if($driver_referal){ 

                            if($driver_referal->ambassador == 1){
                                $bonus = Setting::get('driver_to_driver_referral', 0);
                            }else{
                                $bonus = Setting::get('ambassadors_to_driver_referral', 0);
                            } 
                            $User->driver_referred = $input_promo;
                            $User->wallet_balance += $bonus;
                            $User->referral_used = 1;
                            $User->save();
                        }else if($marketer){

                            $User->marketer = $marketer->id;
                            $marketer_referrals = new MarketerReferrals;
                            $marketer_referrals->marketer_id = $marketer->id;
                            $marketer_referrals->user_id = $User->id;
                            $marketer_referrals->referrer_code = $input_promo;
                            $marketer->total_referrals = $marketer->total_referrals + 1;
                            $marketer->user_referrals = $marketer->user_referrals + 1;
                            $marketer_referrals->save();
                            $marketer->save();
                            $bonus = Setting::get('marketer_to_driver_referral', '10');
                            $User->wallet_balance += $bonus;
                            $User->referral_used = 1;
                            $User->save(); 
                        }
                        if($bonus > 0){
                            $code = rand(1000, 9999);
                            $name = substr($User->first_name, 0, 2);
                            $reference = "RWT".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->user_id = $User->id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->narration = "Referral bonus credited for ". $request->input_promo;
                            $rave_transactions->amount = $bonus;
                            $rave_transactions->status = 1;
                            $rave_transactions->type = "credit";
                            $rave_transactions->save();
                            
                            (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($bonus));
                        }
                        
                        return response()->json(['success' => TRUE, 'message' => 'Referral code applied successfully'], 200);

                    }else{
                        return response()->json(['success' => FALSE, 'message' => 'Referral code invalid!'], 200);
                    }
                    
                }
                    }
                    catch (Exception $e) {
                         Log::info($e);
                        return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
                    }
            }

         public function add_emergency_contacts(Request $request) {

            $exist_contacts = EmergencyContact::where('driver_id', Auth::user()->id)->get();
            if(count($exist_contacts) >=5){
                return response()->json(['success' => FALSE, 'message' => 'Sorry! Only 5 Emergency Contacts can be added.'], 200);
            }
            $contacts = new EmergencyContact;
            $contacts->first_name = $request->first_name;
            $contacts->last_name = $request->last_name;
            $contacts->email = $request->email;
            if($request->hasFile('picture')) {
                $contactspicture = Helper::upload_picture($request->picture);
            }
            $contacts->mobile = $request->mobile;
            $contacts->country_code = $request->country_code;
            $contacts->driver_id = Auth::user()->id;
            $contacts->save();
            $emergency_contacts = EmergencyContact::where('driver_id', Auth::user()->id)->get();
            $response_array = array('success' => true, 'contacts' => $emergency_contacts);

            return response()->json($response_array, 200);

        }

    

    public function delete_emergency_contacts(Request $request) {
            $contact = EmergencyContact::find($request->contact_id);
            if($contact){

                EmergencyContact::find($request->contact_id)->delete();

                $response_array = array('success' => true, 'message' => 'Contact has been deleted from your Emergency Contacts!');
            }else{
                $response_array = array('success' => false, 'message' => 'Contact not found! Please add new');
            }
        return response()->json($response_array,200);

    }

    public function emergency_contacts(Request $request) {

        $exist_contacts = EmergencyContact::where('driver_id', Auth::user()->id)->get();

        $response_array = array('success' => true, 'contacts' => $exist_contacts);

        return response()->json($response_array, 200);

    }

    // public function sendSOSAlert(Request $request){
    //     try{
    //         $request_id = $request->request_id;
    //         $emergency_contacts = EmergencyContact::where('driver_id', Auth::user()->id)->get();
    //         $UserRequest = UserRequests::find($request_id);
    //         $UserRequest->sos_alert = 1;
    //         $UserRequest->alert_initiated = Carbon::now();
    //         $UserRequest->save();
    //         $from = "Eganow";
    //         $current_location = "http://www.google.com/maps/place/".$UserRequest->provider->latitude.",".$UserRequest->provider->longitude;
    //         $car_details = $UserRequest->user->first_name." / ". $UserRequest->provider_profiles->car_registration." (".$UserRequest->provider_profiles->car_make ." ". $UserRequest->provider_profiles->car_model.")";


    //         $content = "Eganow Emergency Alert:

    //                     User name may be in danger and has triggered the sos button in our app.

    //                     Trip details: ". $UserRequest->s_address ." to ". $UserRequest->d_address. "
    //                     Current location: ". $current_location ."
    //                     User /Car details: ".$car_details."

    //                     You received this text because user has saved your number as emergency contact.";

    //         foreach ($emergency_contacts as $contact) {
    //             $to = $contact->mobile;
    //             $cc = $contact->country_code;
    //             if(str_contains($cc,"23") == true){

    //             $content = urlencode($content);
    //             $clientId = env("HUBTEL_API_KEY");
    //             $clientSecret = env("HUBTEL_API_SECRET");

    //             $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);

    //             if(count($sendSms) > 1){
    //                 return response()->json(['success' => TRUE, 'company' => 'Hubtel', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200);
    //             }else if(count($sendSms) == 1 || $sendSms == FALSE){

    //                 $content = urlencode($content);
    //                 $mobile = $to;
    //                 if($mobile[0] == 0){
    //                     $receiver = $mobile;
    //                 }else{
    //                     $receiver = "0".$mobile; 
    //                 }

    //                 $client = new \GuzzleHttp\Client();

    //                 $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

    //                 $headers = ['Content-Type' => 'application/json'];
                    
    //                 $res = $client->get($url, ['headers' => $headers]);

    //                 $code = (string)$res->getBody();
    //                 $codeT = str_replace("\n","",$code);
    //                 Log::info($url);
    //                 if($codeT == "000"){
    //                     return response()->json(['success' => TRUE, 'company' => 'Rancard', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200);
    //                 }else{
    //                     $to = $cc . $to;
    //                     $sendTwilio = sendMessageTwilio($to, $content);
    //                     //Log::info($sendTwilio);
    //                     if($sendTwilio){
    //                        return response()->json(['success' => TRUE,  'company' => 'Twilio', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200); 
    //                     }else{
    //                         return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
    //                     }
    //                 }
    //             }
                
                
    //         }
    //         else{
                
    //             $sendTwilio = sendMessageTwilio($to, $content);
    //             //Log::info($sendTwilio);
    //             if($sendTwilio){
    //                return response()->json(['success' => TRUE, 'company' => 'Twilio', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200); 
    //             }else{
    //                 return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
    //             }
    //         }
    //         }
            
    //     }
    //     catch (Exception $e) {
    //         Log::info($e);
    //          return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
    //     }
    // }

    public function sendSOSAlert(Request $request){
        try{
            $request_id = $request->request_id;
            $emergency_contacts = EmergencyContact::where('driver_id', Auth::user()->id)->get();
            $UserRequest = UserRequests::find($request_id);
            $UserRequest->sos_alert = 3;
            $UserRequest->alert_initiated = Carbon::now();
            $UserRequest->save();
            $from = "Eganow";
            $current_location = "http://www.google.com/maps/place/".$UserRequest->provider->latitude.",".$UserRequest->provider->longitude;
            $car_details = $UserRequest->user->first_name." / ". $UserRequest->provider_profiles->car_registration." (".$UserRequest->provider_profiles->car_make ." ". $UserRequest->provider_profiles->car_model.")";


            $content = "Eganow Emergency Alert:

                        User name may be in danger and has triggered the sos button in our app.

                        Trip details: ". $UserRequest->s_address ." to ". $UserRequest->d_address. "
                        Current location: ". $current_location ."
                        User /Car details: ".$car_details."

                        You received this text because user has saved your number as emergency contact.";
                        $sos_number = Setting::get('Eganow_sos_number');
                        $content = urlencode($content);
                        $clientId = env("HUBTEL_API_KEY");
                        $clientSecret = env("HUBTEL_API_SECRET");

                        $sendSms = sendSMS($from, $sos_number, $content, $clientId, $clientSecret);

                        if(count($sendSms) == 1 || $sendSms == FALSE){

                            $content = urlencode($content);
                            $mobile = $sos_number;
                            if($mobile[0] == 0){
                                $receiver = $mobile;
                            }else{
                                $receiver = "0".$mobile; 
                            }

                            $client = new \GuzzleHttp\Client();

                            $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                            $headers = ['Content-Type' => 'application/json'];
                            
                            $res = $client->get($url, ['headers' => $headers]);

                            $code = (string)$res->getBody();
                            $codeT = str_replace("\n","",$code);
                            Log::info($url);
                            if($codeT != "000"){
                                $sos_number = $cc . $sos_number;
                                $sendTwilio = sendMessageTwilio($sos_number, $content);
                            }
                        }

            if(count($emergency_contacts) > 0){
                $content = "Eganow Emergency Alert:

                        User name may be in danger and has triggered the sos button in our app.

                        Trip details: ". $UserRequest->s_address ." to ". $UserRequest->d_address. "
                        Current location: ". $current_location ."
                        Driver /Car details: ".$car_details."

                        You received this text because user has saved your number as emergency contact.";
                foreach ($emergency_contacts as $contact) {
                    $to = $contact->mobile;
                    $cc = $contact->country_code;
                    if(str_contains($cc,"23") == true){

                        $content = urlencode($content);
                        $clientId = env("HUBTEL_API_KEY");
                        $clientSecret = env("HUBTEL_API_SECRET");

                        $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);

                        if(count($sendSms) > 1){
                            return response()->json(['success' => TRUE, 'company' => 'Hubtel', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200);
                        }else if(count($sendSms) == 1 || $sendSms == FALSE){

                            $content = urlencode($content);
                            $mobile = $to;
                            if($mobile[0] == 0){
                                $receiver = $mobile;
                            }else{
                                $receiver = "0".$mobile; 
                            }

                            $client = new \GuzzleHttp\Client();

                            $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                            $headers = ['Content-Type' => 'application/json'];
                            
                            $res = $client->get($url, ['headers' => $headers]);

                            $code = (string)$res->getBody();
                            $codeT = str_replace("\n","",$code);
                            Log::info($url);
                            if($codeT == "000"){
                                return response()->json(['success' => TRUE, 'company' => 'Rancard', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200);
                            }
                            // else{
                            //     if(str_contains($cc,"+") == false){
                            //         $cc = "+".$cc;
                            //     }
                            //     $to = $cc . $to;
                            //     $sendTwilio = sendMessageTwilio($to, $content);
                            //     //Log::info($sendTwilio);
                            //     if($sendTwilio){
                            //        return response()->json(['success' => TRUE,  'company' => 'Twilio', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200); 
                            //     }else{
                            //         return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                            //     }
                            // }
                        }
                    
                    }else{
                        if(str_contains($cc,"+") == false){
                            $cc = "+".$cc;
                        }
                        $to = $cc . $to;
                        $sendTwilio = sendMessageTwilio($to, $content);
                        //Log::info($sendTwilio);
                        if($sendTwilio){
                           return response()->json(['success' => TRUE, 'company' => 'Twilio', 'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200); 
                        }else{
                            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                        }
                    }
                }
            }else{
                return response()->json(['success' => TRUE,  'message' => 'Alert Activated, Eganow Team will contact you immediately.'], 200);
            }
            
        }
        catch (Exception $e) {
            Log::info($e);
             return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function update_eta(Request $request)
    {
        try {
            $eta = $request->eta;
            if(str_contains($eta,"mins") == true){
                $eta = str_replace(" mins", "", $eta);
            }
            if(str_contains($eta,"min") == true){
                $eta = str_replace(" min", "", $eta);
            }
            $UserRequest = UserRequests::where('id',$request->request_id)->first();
            if($UserRequest){
                $UserRequest->eta = $eta;
                $UserRequest->save();
            return response()->json(['success' => TRUE, 'data'=> $UserRequest], 200);
            }else{
                return response()->json(['success' => FALSE, 'data'=> $eta], 200);
            }
            

        }catch (Exception $e) {
             Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function update_car(Request $request){
        try{
            $DriverCar = DriverCars::where('id', $request->car_id)->first();

            if($DriverCar){

                if ($request->has('car_registration'))
                    $DriverCar->car_registration = $request->car_registration;

                if ($request->has('car_make'))
                    $DriverCar->car_make = $request->car_make;

                if ($request->has('car_model'))
                    $DriverCar->car_model = $request->car_model;

                if ($request->hasFile('car_picture')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/providers';                    
                        $contents = file_get_contents($request->car_picture);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->car_picture = $s3_url;
                }

                 if ($request->hasFile('insurance_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/providers';                    
                        $contents = file_get_contents($request->insurance_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->insurance_file = $s3_url;
                }

                 if ($request->hasFile('road_worthy_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/providers';                    
                        $contents = file_get_contents($request->road_worthy_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->road_worthy_file = $s3_url;
                }

                if ($request->has('mileage'))
                    $DriverCar->mileage = $request->mileage;

                if ($request->has('car_make_year'))
                    $DriverCar->car_make_year = $request->car_make_year;

                if ($request->has('road_worthy_expire'))
                    $DriverCar->road_worthy_expire = $request->road_worthy_expire;

                if ($request->has('insurance_type'))
                    $DriverCar->insurance_type = $request->insurance_type;

                if ($request->has('insurance_expire'))
                    $DriverCar->insurance_expire = $request->insurance_expire;

                $DriverCar->save();

                if($request->has('is_active')){
                    $DriverCar->is_active = $request->is_active;
                    $DriverCar->save();

                    if($DriverCar->is_active == 1){
                        $defaultCar = DriverCars::where('id', '!=', $request->car_id)->where('driver_id', Auth::user()->id)->update(['is_active' => 0]);
                        
                        $DriverProfile = ProviderProfile::where('provider_id', $DriverCar->driver_id)->first();
                        $DriverProfile->car_registration = $DriverCar->car_registration;
                        $DriverProfile->car_make = $DriverCar->car_make;
                        $DriverProfile->car_model = $DriverCar->car_model;
                        $DriverProfile->car_picture = $DriverCar->car_picture;
                        $DriverProfile->mileage = $DriverCar->mileage;
                        $DriverProfile->car_make_year = $DriverCar->car_make_year;
                        $DriverProfile->road_worthy_expire = $DriverCar->road_worthy_expire;
                        $DriverProfile->insurance_type = $DriverCar->insurance_type;
                        $DriverProfile->insurance_expire = $DriverCar->insurance_expire;
                        $DriverProfile->save();   
                    }
                }

                return response()->json(['success' => TRUE, 'data'=> $DriverCar], 200);
            }else{
                return response()->json(['success' => FALSE, 'message' => 'No Car info found'], 200);
            }
                

        }catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }

    }

    public function add_car(Request $request){
        
        try{
            $DriverCar = new DriverCars;
            $DriverCar->driver_id = Auth::user()->id;

                if ($request->has('car_registration'))
                    $DriverCar->car_registration = $request->car_registration;

                if ($request->has('car_make'))
                    $DriverCar->car_make = $request->car_make;

                if ($request->has('car_model'))
                    $DriverCar->car_model = $request->car_model;

                if ($request->hasFile('car_picture')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/providers';                    
                        $contents = file_get_contents($request->car_picture);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->car_picture = $s3_url;
                }

                 if ($request->hasFile('insurance_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/providers';                    
                        $contents = file_get_contents($request->insurance_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->insurance_file = $s3_url;
                }

                 if ($request->hasFile('road_worthy_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/providers';                    
                        $contents = file_get_contents($request->road_worthy_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->road_worthy_file = $s3_url;
                }

                if ($request->has('mileage'))
                    $DriverCar->mileage = $request->mileage;

                if ($request->has('car_make_year'))
                    $DriverCar->car_make_year = $request->car_make_year;

                if ($request->has('road_worthy_expire'))
                    $DriverCar->road_worthy_expire = $request->road_worthy_expire;

                if ($request->has('insurance_type'))
                    $DriverCar->insurance_type = $request->insurance_type;

                if ($request->has('insurance_expire'))
                    $DriverCar->insurance_expire = $request->insurance_expire;

                
                $DriverCar->status = 0;
                $DriverCar->save();

                // if($request->has('is_active')){
                //     $DriverCars = DriverCars::where('driver_id', Auth::user()->id)->count();
                //     if($DriverCars > 1){
                //         if($request->is_active == 1){
                //             $DriverCar->is_active = 1;
                //             $defaultCar = DriverCars::where('id', '!=', $DriverCar->id)->where('driver_id', Auth::user()->id)->update(['is_active' => 0]);
                //         }else{
                //             $DriverCar->is_active = 0;
                //         }
                //     }else{
                //         $DriverCar->is_active = 0;
                //     }
                        
                //         $DriverCar->save();
                        
                // }

                return response()->json(['success' => TRUE, 'data'=> $DriverCar], 200);
                

        }catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }

    }

    public function car_info(Request $request){
        try{
            $DriverCar = DriverCars::where('id', $request->car_id)->first();
            return response()->json(['success' => TRUE, 'data'=> $DriverCar], 200);
        } catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function list_cars(){
        try{
            $driver_car = DriverCars::where('driver_id', Auth::user()->id)->get();
            if(count($driver_car) ==0){
                $savedCar = ProviderProfile::where('provider_id',Auth::user()->id)->where('car_registration' , '!=', '')->first();
                $insurance_file = ProviderDocument::where('provider_id',Auth::user()->id)->where('document_id', 6)->first();
                $road_worthy_file = ProviderDocument::where('provider_id',Auth::user()->id)->where('document_id', 3)->first();

                if($savedCar){
                    $DriverCar = new DriverCars;
                    $DriverCar->car_registration = $savedCar->car_registration;
                    $DriverCar->car_make = $savedCar->car_make;
                    $DriverCar->car_model = $savedCar->car_model;
                    $DriverCar->car_picture = $savedCar->car_picture;
                    $DriverCar->mileage = $savedCar->mileage;
                    $DriverCar->car_make_year = $savedCar->car_make_year;
                    $DriverCar->road_worthy_expire = $savedCar->road_worthy_expire;
                    $DriverCar->insurance_type = $savedCar->insurance_type;
                    $DriverCar->insurance_expire = $savedCar->insurance_expire;
                    $DriverCar->driver_id = $savedCar->provider_id;

                    if($insurance_file){
                        $DriverCar->insurance_file = $insurance_file->url;
                    }
                    if($road_worthy_file){
                        $DriverCar->road_worthy_file = $road_worthy_file->url;
                    }
                    $DriverCar->status = 1;
                    $DriverCar->save();
                    $savedCar->car_saved = 1;
                    $savedCar->save();
                }
            }
            $DriverCars = DriverCars::where('driver_id', Auth::user()->id)->get();

            return response()->json(['success' => TRUE, 'cars'=> $DriverCars], 200);
        } catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function delete_car(Request $request){
        try{
            $DriverCar = DriverCars::where('id', $request->car_id)->delete();
            $DriverCars = DriverCars::where('driver_id', Auth::user()->id)->get();

            return response()->json(['success' => TRUE, 'message'=> 'Car Deleted Successfully'], 200);
        } catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function update_account(Request $request){
        try{
            $DriverAccount = DriverAccounts::where('id', $request->account_id)->first();

            if($DriverAccount){

                if($request->has('is_active')){
                    $DriverAccount->is_active = $request->is_active;
                    $DriverAccount->save();
                    $defaultCar = DriverAccounts::where('id', '!=', $request->account_id)->where('driver_id', Auth::user()->id)->update(['is_active' => 0]);
                }
               
                if($request->has('acc_no')) {
                    $DriverAccount->acc_no = $request->acc_no;
                }

                if($request->has('acc_name')) {
                    $DriverAccount->acc_name = $request->acc_name;
                }

                if ($request->has('bank_name')){
                    $DriverAccount->bank_name = $request->bank_name;
                }

                if ($request->has('bank_name_id')){
                    $DriverAccount->bank_name_id = $request->bank_name_id-1;
                }

                if ($request->has('bank_code')){
                    $DriverAccount->bank_code = $request->bank_code;
                }
                
                $DriverAccount->save();

                return response()->json(['success' => TRUE, 'data'=> $DriverAccount], 200);
            }else{
                return response()->json(['success' => FALSE, 'message' => 'No Account info found'], 200);
            }
                

        }catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }

    }

    public function add_account(Request $request){
        
        try{
            $DriverAccount = new DriverAccounts;

            $DriverAccount->driver_id = Auth::user()->id;

            if($request->has('acc_no')) {
                $DriverAccount->acc_no = $request->acc_no;
            }

            if($request->has('acc_name')) {
                $DriverAccount->acc_name = $request->acc_name;
            }

            if ($request->has('bank_name')){
                $DriverAccount->bank_name = $request->bank_name;
            }

            if ($request->has('bank_name_id')){
                $DriverAccount->bank_name_id = $request->bank_name_id-1;
            }

            if ($request->has('bank_code')){
                $DriverAccount->bank_code = $request->bank_code;
            }
            
            
           
            $DriverAccount->status = 0;
            $DriverAccount->save();

             if($request->has('is_active')){
                $DriverAccounts = DriverAccounts::where('driver_id', Auth::user()->id)->count();
                if($DriverAccounts > 1){
                    if($request->is_active == 1){
                        $DriverAccount->is_active = 1;
                        $defaultCar = DriverAccounts::where('id', '!=', $DriverAccount->id)->where('driver_id', Auth::user()->id)->update(['is_active' => 0]);
                    }else{
                        $DriverAccount->is_active = 0;
                    }
                }else{
                    $DriverAccount->is_active = 1;
                }
                    
                    $DriverAccount->save();
                    
            }

            return response()->json(['success' => TRUE, 'data'=> $DriverAccount], 200);  

        }catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }

    }

    public function account_info(Request $request){
        try{
            $DriverAccount = DriverAccounts::where('id', $request->account_id)->first();
            return response()->json(['success' => TRUE, 'data'=> $DriverAccount], 200);
        } catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function list_accounts(){
        try{

            $DriverAccount = DriverAccounts::where('driver_id', Auth::user()->id)->get();
            if(count($DriverAccount) == 0){
                $savedAccount = ProviderProfile::where('provider_id',Auth::user()->id)->where('acc_no', '!=', '')->first();
                if($savedAccount){
                    $DriverAccounts = new DriverAccounts;
                    $DriverAccounts->acc_no = $savedAccount->acc_no;
                    $DriverAccounts->acc_name = $savedAccount->acc_name;
                    $DriverAccounts->bank_name = $savedAccount->bank_name;
                    $DriverAccounts->bank_name_id = $savedAccount->bank_name_id;
                    $DriverAccounts->bank_code = $savedAccount->bank_code;
                    $DriverAccounts->driver_id = $savedAccount->provider_id;
                    $DriverAccounts->is_active = 1;
                    $DriverAccounts->status = 1;
                    $DriverAccounts->save();
                    $savedAccount->account_saved = 1;
                    $savedAccount->save();
                }
            }
            $DriverAccounts = DriverAccounts::where('driver_id', Auth::user()->id)->get();
            return response()->json(['success' => TRUE, 'accounts'=> $DriverAccounts], 200);
        } catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function delete_account(Request $request){
        try{
            $DriverAccount = DriverAccounts::where('id', $request->account_id)->delete();
            $DriverAccounts = DriverAccounts::where('driver_id', Auth::user()->id)->get();

            return response()->json(['success' => TRUE, 'message'=> 'Account Deleted Successfully'], 200);
        } catch (Exception $e){
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function changepayment(Request $request){

        $request_id = $request->request_id;
        try{
            $UserRequest = UserRequests::find($request_id);
            $UserRequest->payment_mode = "CASH";
            $UserRequest->save();
            
            if($UserRequest->service_type->is_delivery == 1){
                        $service_flow = "Delivery";
                    }else{
                        $service_flow= "Ride";
                    }
            (new SendPushNotification)->PaymentModeChangedDriver($UserRequest, $service_flow);

            $RequestPayment = UserRequestPayment::where('request_id',$request_id)->first();
            $RequestPayment->payment_mode = $request->payment_mode;
            $RequestPayment->save(); 

            return response()->json(['success' => TRUE, 'message' => 'Payment Mode Changed']); 

        }catch (Exception $e) {

            if($request->ajax()){

                return response()->json(['success' => FALSE, 'message' => 'Something Went Wrong']); 

            }else{

                return back()->with('flash_error','Try again later');
            }
        }
    }

    //Untapped APIs

    //Vehicle List API

    public function vehicle_list()
    {
        try {
            if(Auth::user()->id == '11571'){
                $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
                $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');

                $vehicles = DriveNowVehicle::whereIn('fleet_id', $fleets)->with(['drivenow' => function ($query) { $query->select('id','driver_name');}])->get();
                

                
                return response()->json(['success' => TRUE, 'vehicles'=> $vehicles], 200); 
            }else{
                return response()->json(['success' => FALSE, 'message' => 'No access to this API, Contact Eganow Admin!']); 
            }
            
        } catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function vehicle_profile($id)
    {
        try {
            if(Auth::user()->id == '11571'){
                $vehicle = DriveNowVehicle::where('id', $id)->first();

                $d = Carbon::parse(Carbon::now())->diffInDays($vehicle->drivenow->next_due);
                $driver = OfficialDriver::where('id',$vehicle->drivenow->id)->first();

                $transactions = DriveNowRaveTransaction::where('official_id',$vehicle->drivenow->id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->orderBy('created_at', 'desc')->get();

                $txn_amt = DriveNowRaveTransaction::where('driver_id', $driver->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');
                $txn_adc = DriveNowRaveTransaction::where('driver_id', $driver->driver_id)->where('status',1)->sum('add_charge');

                $vehicle_paid = $driver->pre_balance + ($txn_amt - $txn_adc);

                $repairs = DriveNowVehicleRepairHistory::where('car_id',$id)->orderBy('created_at' , 'desc')->get();

                $tro_access_token = Setting::get('tro_access_token','');
                if($tro_access_token == ''){
                    $time = Carbon::now()->timestamp;
                    $account = "Eganow@trotrotracker.com";
                    $password = "EganowTech1T#";
                    $signature = md5(md5($password).$time);

                    $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                    $token_json = curl($token_url);

                    $token_details = json_decode($token_json, TRUE);

                    $tro_access_token = $token_details['record']['access_token'];
                    Setting::set('tro_access_token', $tro_access_token);
                    Setting::save();
                    Log::info("Tro Access Token Called:".$tro_access_token
                );
                }

                if($tro_access_token !='' && $vehicle->imei !=''){
                    $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$vehicle->imei;

                    $status_json = curl($status_url);

                    $status_details = json_decode($status_json, TRUE);

                    if($status_details){
                        if($status_details['code']== '10012'){
                            $time = Carbon::now()->timestamp;
                            $account = "Eganow@trotrotracker.com";
                            $password = "EganowTech1T#";
                            $signature = md5(md5($password).$time);

                            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                            $token_json = curl($token_url);

                            $token_details = json_decode($token_json, TRUE);

                            $tro_access_token = $token_details['record']['access_token'];
                            Setting::set('tro_access_token', $tro_access_token);
                            Setting::save();
                            Log::info("Tro Access Token Called");
                            $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$vehicle->imei;

                            $status_json = curl($status_url);

                            $status_details = json_decode($status_json, TRUE);
                        }
                        if($status_details['code']== '10007'){
                            Log::info(json_encode($status_details));
                        }                    
                        $vehicle = DriveNowVehicle::where('id', $id)->first();
                        $official_driver = OfficialDriver::findOrFail($driver->id);

                        if($status_details['record'][0]['oilpowerstatus'] == 0){
                            $official_driver->engine_status = 1;
                            $official_driver->save();
                        }else{
                            $official_driver->engine_status = 0;
                            $official_driver->save();
                        }
                        
                        $vehicle->latitude = $status_details['record'][0]['latitude'];
                        $vehicle->longitude = $status_details['record'][0]['longitude'];
                        $vehicle->car_speed = $status_details['record'][0]['speed'];

                        if($status_details['record'][0]['oilpowerstatus'] == 1) {
                         $engine_status ="Active";
                        }elseif($status_details['record'][0]['datastatus'] == 2){
                         $engine_status ="Blocked"; 
                        }


                        $vehicle->engine_status = $engine_status;
                        
                        if($status_details['record'][0]['datastatus'] == 1) {
                         $data_connection ="Never Online";
                        }elseif($status_details['record'][0]['datastatus'] == 2){
                         $data_connection ="Online"; 
                        }elseif($status_details['record'][0]['datastatus'] == 3){
                         $data_connection ="Expired"; 
                        }elseif($status_details['record'][0]['datastatus'] == 4){
                         $data_connection ="Offline";  
                        }elseif($status_details['record'][0]['datastatus'] == 5){
                         $data_connection ="Blocked"; 
                        }
                        $vehicle->data_connection = $data_connection;

                        $vehicle->active_hours = round(($status_details['record'][0]['acctime'])/3600);
                        $vehicle->location_updated = Carbon::createFromTimestamp($status_details['record'][0]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                        // $vehicle->gpstime = Carbon::createFromTimestamp($status_details['record'][0]['gpstime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                        $vehicle->active_since = Carbon::createFromTimestamp($status_details['record'][0]['systemtime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                        $vehicle->battery = $status_details['record'][0]['battery'];
                    }
                }
                $driver = OfficialDriver::findOrFail($driver->id);
                $p = Provider::where('id', $driver->driver_id)->first();
                $driver->latitude = $p->latitude;
                $driver->longitude = $p->longitude;
                $defaults = DriveNowBlockedHistory::with('provider', 'official', 'engine_off')->where('engine_off_reason','Payment Due')->where('official_id',$driver->id)->orderBy('created_at', 'desc')->get();
                // dd($defaults);
                return response()->json(['success' => TRUE, 'vehicle'=> $vehicle,'transactions' => $transactions, 'driver' => $driver, 'issue_logs' => $repairs], 200);
                // return view('drivenow.vehicle_profile',compact('vehicle','d','transactions','driver','vehicle_paid','repairs','defaults'));
            }else{
                return response()->json(['success' => FALSE, 'message' => 'No access to this API, Contact Eganow Admin!']); 
            }
            
        } catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function transactions(){
        try{
            if(Auth::user()->id == '11571'){
                $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
                $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');
                $transactions = DriveNowRaveTransaction::with(['official_driver' => function ($query) { $query->select('id','driver_name');}])
                                ->whereHas('official_driver')->where('status', 1)->whereIn('official_id',$drivers)->orderBy('updated_at', 'desc')->get();
                // $total_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->sum('amount_due');
                // $total_paid = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');
                // $total_add_charge = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->sum('add_charge');
                // $total_tran_suc = DriveNowRaveTransaction::where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->whereIn('official_id',$drivers)->count();
                // $total_tran_fail = DriveNowRaveTransaction::where('status',0)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->whereIn('official_id',$drivers)->count();
                // $total_tran_pen = DriveNowRaveTransaction::where('status',2)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->whereIn('official_id',$drivers)->count();
                // $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->get()->count();
                //     if(date('D') != 'Tue'){
                //         $driver_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->whereRaw('amount_due > weekly_payment')->count();
                //     }else{
                //         $driver_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->where('amount_due', '>', 0)->count();
                //     }
                //     $driver_paid = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->distinct('driver_id')->count('driver_id');
                    
                // $total_driver = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->count();
                // $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->whereIn('supplier_id', $fleets)->where('status', '!=', 1)->count();
                // $due = DriveNowTransaction::orderBy('created_at','desc')->where('due_date','!=','')->first();
                
                // $d = Carbon::parse(Carbon::now())->diffInDays($due->due_date);
                // dd($transactions[0]);
                return response()->json(['success' => TRUE, 'transactions'=> $transactions], 200);
        }else{
                return response()->json(['success' => FALSE, 'message' => 'No access to this API, Contact Eganow Admin!']); 
            }
            
        } catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
            
    }

    public function driver_list()
    {
        try {
            if(Auth::user()->id == '11571'){
            
                $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
                $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->get();

                $online_drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->whereHas('provider', function($query)  {
                                $query->where('availability', 1);})->count();

                $offline_drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->whereHas('provider', function($query)  {
                                $query->where('availability', 0);})->count();

                if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->where('amount_due', '>', 0)->count();
                    }
                $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->whereIn('supplier_id', $fleets)->where('status', '!=', 1)->count();


                // for ($o=0; $o < count($drivers); $o++) {
                //     $drivers[$o]->txn_amt = DriveNowRaveTransaction::where('driver_id',$drivers[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');

                //     $drivers[$o]->txn_adc = DriveNowRaveTransaction::where('driver_id',$drivers[$o]->driver_id)->where('status',1)->sum('add_charge');
                //     $drivers[$o]->vehicle_paid = $drivers[$o]->pre_balance + ($drivers[$o]->txn_amt - $drivers[$o]->txn_adc);
                // }
    
                  return response()->json(['success' => TRUE, 'drivers'=> $drivers], 200);
            }else{
                return response()->json(['success' => FALSE, 'message' => 'No access to this API, Contact Eganow Admin!']); 
            }
            
        } catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }


    //DriveNow Profile page apis

    public function drivenow()
    {
        try{
            $id = Auth::user()->id;
            $Provider = Provider::where('id',$id)->first();
            
            $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status','!=',1)->first();
            
            $OldDriverOff = DriverDayOff::where('driver_id',$id)->where('official_id', $official_driver->id)->whereDate('day_off', '<', Carbon::today())->update(['status' => 1]);
            $cur_day_off = DriverDayOff::where('driver_id',$id)->where('official_id', $official_driver->id)->whereDate('day_off', Carbon::today())->where('status',0)->first();
            if(!$cur_day_off){
                $official_driver->day_off = 0;
                $official_driver->save();
            }

            $transact = DriveNowTransaction::where('driver_id', $Provider->id)->where('contract_id', $official_driver->id)->where('contract_id', $official_driver->id)->where('status', 0)->orderBy('updated_at', 'desc')->first();
            $global_engine = Setting::get('global_engine', 0);
            $missed = '';
            $code = rand(100000, 999999);
            $name = substr($Provider->first_name, 0, 2);
            $req_id = $name.$code;
            $trans_id = "DriveNow".$code;
            $dup_trans = DriveNowRaveTransaction::where('slp_ref_id', $trans_id)->first();
            if($dup_trans){
                $code = rand(100000, 999999);
                $name = substr($Provider->first_name, 0, 2);
                $req_id = $name.$code;
                $trans_id = "DriveNow".$code;
            }
            // $missed = DriveNowTransaction::where('driver_id',$Provider->id)->where('status', 3)->first();

            $code = rand(1000, 9999);
            $name = substr($Provider->first_name, 0, 2);
            $reference = "AWT".$code.$name;
            
            $credit_pending_transactions = DriveNowRaveTransaction::where('driver_id', $Provider->id)->where('official_id', $official_driver->id)->whereIn('status', [2,3])->where('created_at', '>=', Carbon::now()->subDays(2)->toDateTimeString())->orderBy('created_at', 'desc')->get();


                $transactions = DriveNowRaveTransaction::where('driver_id', $Provider->id)->where('official_id', $official_driver->id)->where('status', '!=', 3)->orderBy('created_at', 'desc')->get();

                $day_offs = DriverDayOff::where('driver_id', $Provider->id)->where('created_at', '>=', Carbon::now()->subDays(30))->orderBy('created_at', 'desc')->get();
                
                $activeHours = DriverActivity::where('driver_id', $Provider->id)->select([DB::raw("SUM(working_time) as activeHours")])->groupBy(DB::raw("DATE_FORMAT(created_at,'%d %m %Y')"))->where('created_at', '>=', Carbon::now()->subDays(30))->orderBy('created_at', 'desc')->get();

                
                for($i=0; $i < count($activeHours); $i++){
                    if($activeHours[$i]->activeHours > 0){ 

                        if($activeHours[$i]->activeHours >= 60){
                            $activeHour = number_format(($activeHours[$i]->activeHours / 60), 2) ." Hrs";
                        }else{
                            $activeHour = number_format($activeHours[$i]->activeHours, 2) . " mins";
                        }

                        $activeHours[$i]->active_hours_format = $activeHour;
                        // $week['active_hours'] = $activeHours[$i] / 60;
                    }
                }
                $activities = array();
                for($i = 30; $i >= 0; $i--)
                {
                    if($i == 0){
                        $date = date("Y-m-d");
                    }else{
                        $date = date("Y-m-d", strtotime("-$i days"));
                    }
                    // Log::info($date);

                    $activeHour = DriverActivity::where('driver_id', $Provider->id)->select([DB::raw("SUM(working_time) as activeHours")])->groupBy(DB::raw("DATE_FORMAT(created_at,'%d %m %Y')"))->whereDate('created_at',$date)->first();
                    
                    $day_off = DriverDayOff::where('driver_id', $Provider->id)->whereDate('created_at',$date)->whereIn('status', [0,1])->get();

                    if(count($activeHour) > 0){
                        $activities[$i]['date'] = $date;
                        if($activeHour->activeHours >= 60){
                            $activities[$i]['activeHour'] = number_format(($activeHour->activeHours / 60), 2) ." Hrs";
                        }else{
                            $activities[$i]['activeHour'] = number_format($activeHour->activeHours, 2) . " mins";
                        }
                    }else if(count($day_off) > 0){
                        $activities[$i]['date'] = $date;
                        $activities[$i]['activeHour'] = "Day Off";
                    }else{
                        $activities[$i]['date'] = $date;
                        $activities[$i]['activeHour'] = "No Activity";
                    }

                }
                $revoke = 0;
                $day_off = DriverDayOff::where('driver_id', $Provider->id)->where('status', 0)->first();
                if($day_off){
                    $date = Carbon::parse($day_off->created_at);
                    $now = Carbon::now();
                    
                    $diff = $date->diffInMinutes($now);
                    if($diff < 15){
                        $revoke = 1;
                    } 
                }
                $total_paid_transaction = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->where('slp_ref_id', 'not like', 'DriveNow_D%')->sum('amount');
                $total_add_transaction = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->sum('add_charge');
                $vehicle_paid = $official_driver->pre_balance + ($total_paid_transaction - $total_add_transaction);

                if((int)$vehicle_paid > (int)$official_driver->vehicle_cost){

                    $vehicle_paid = $official_driver->vehicle_cost;
                }
                $extras = $due_daily_conversion = array();
                $due_daily_conversion = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)
                    // ->where('reason', '=','Pending Due')
                    ->first();
                    $extras = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->where('reason', '!=','Pending Due')
                    ->get();
                    $daily_extra = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('daily_due');
                    $weekly_extra = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('due');
                    $total_extra = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('total');

                    $split = '';
                
                if($official_driver->vehicle->fleet->management_fee != ''){
                    $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                    $management_fee = $official_driver->vehicle->fleet->management_fee / $total_fee;

                    $weekly = $official_driver->vehicle->fleet->weekly / $total_fee;

                    $company_share = $official_driver->vehicle->fleet->company_share / $total_fee;

                    $road_worthy_fee = $official_driver->vehicle->fleet->road_worthy_fee / $total_fee;

                    $insurance_fee = $official_driver->vehicle->fleet->insurance_fee / $total_fee;

                    $maintenance_fee = $official_driver->vehicle->fleet->maintenance_fee / $total_fee;

                    $revenue = $road_worthy_fee + $company_share;

                    $total_per = $revenue + $weekly + $insurance_fee + $management_fee + $maintenance_fee; 
                    $share1 = round(number_format(($weekly * 100),2));
                    $share2 = round(number_format(($revenue * 100),2));
                    $share3 = round(number_format(($insurance_fee * 100),2));
                    $share4 = round(number_format(($maintenance_fee * 100),2));

                    $split = [
                                'currency' => 'GHS',
                                'type' => 'percentage',
                                'bearer_type' => "account",
                                'subaccounts' =>[
                                    [
                                        'subaccount' => 'ACCT_nt6l9ila89nt53e',
                                        'share' => $share1
                                    ],
                                    [
                                        'subaccount' => 'ACCT_v28qbriveNowk6xpbnrp',
                                        'share' => $share2
                                    ],
                                    [
                                        'subaccount'=> 'ACCT_6m0wlmc5zzs0lm6',
                                        'share' => $share3
                                    ],
                                    [
                                        'subaccount'=> 'ACCT_87bru0epzazpghk',
                                        'share' => $share4
                                    ]
                                    
                                ],
        
                            ];
                            
                }
                $due = 0;
                    if($official_driver->amount_due > 0){
                        if($official_driver->daily_drivenow == 1){
                            if($official_driver->extra_pay > 0){
                                if(($official_driver->daily_due + $daily_extra) > 0){ 
                                    $due = round(($official_driver->daily_due + $official_driver->daily_due_add));
                                }else{
                                    $due = 0; 
                                }
                            
                            }else{
                               $due = round(($official_driver->daily_due));
                            }
                        }
                        else{
                            if($official_driver->extra_pay > 0){
                                if(($official_driver->amount_due + $weekly_extra) > 0){
                                    $due = round(($official_driver->amount_due + $official_driver->amount_due_add));
                                }else{
                                    $due = 0; 
                                }
                            }
                            else{
                                $due = round(($official_driver->amount_due));
                            }
                        }
                    }else{
                        $due = 0; 
                    }
    

            return response()->json(['success' => TRUE,
                'official_driver' => $official_driver,
                'minimum_due' => $due,
                'transactions' => $transactions,
                'missed' => $missed,
                'trans_id' => $trans_id,
                'transact' => $transact,
                'day_offs' => $day_offs,
                'activities' => $activities,
                'revoke' => $revoke,
                'extras' => $extras,
                'due_daily_conversion' => $due_daily_conversion,
                'daily_extra' => $daily_extra,
                'weekly_extra' => $weekly_extra,
                'total_extra' => $total_extra,
                'vehicle_paid' => $vehicle_paid,
                'total_add_transaction' => $total_add_transaction,
                'split' => $split], 200);

        } catch (Exception $e){
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
            
    }
    public function confirm_drivenow_transaction(Request $request){
        try {
                $id = Auth::user()->id;
                $rave_transactions = DriveNowRaveTransaction::where('slp_ref_id', $request->reference)->first();
                $driver = Provider::where('id', $id)->first();
                $official_driver = OfficialDriver::where('driver_id',$driver->id)->with('vehicle')->where('status','!=',1)->first();
                
                if(!$rave_transactions){
                    $rave_transactions = new DriveNowRaveTransaction;
                    $rave_transactions->driver_id = $driver->id;
                    $rave_transactions->official_id = $official_driver->id;
                    $rave_transactions->bill_id = $request->bill_id;
                    $rave_transactions->reference_id = $request->orderID;
                    $rave_transactions->slp_ref_id = $request->reference;
                    $rave_transactions->amount = $request->amount;
                    $rave_transactions->status = 2;
                    $rave_transactions->save();
                }
                // $CP = Helper::ConfirmPayment($rave_transactions->id);
                return response()->json(['success' => TRUE, 'message' => 'Payment received successfully'], 200);
            
        } catch (Exception $e) {
            Log::info($e);
            return response()->json(['success' => FALSE, 'error' => trans('api.something_went_wrong')], 200);
        }
    }

    public function agreement(Request $request)
    {
       $id = Auth::user()->id;
        
        try {
            $Provider = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();

            $contract = DriverContracts::where('driver_id',$Provider->driver_id)->where('status','!=',2)->first();
            if($contract){
                 $file_name = str_replace('.blade.php','',$contract->contract->content);

                if($file_name){
                    return view('provider.'.$file_name, compact('Provider'));
                }else{
                    return view('provider.agreement', compact('Provider'));
                }
            }else{
                return view('provider.agreement', compact('Provider'));
            }
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Fleet Not Found');
        }
    }

    public function delete_profile(Request $request){
        try {
            $id = Auth::user()->id;
            $driver = Provider::find($id);
            $driver->delete_acc = 1;
            $driver->save();
        return response()->json(['success' => TRUE, 'message' => 'Driver Account Deleted!'], 200);
            
        } catch (Exception $e) {
            return response()->json(['success' => FALSE, 'message' => 'Driver Account Not Found!'], 200);  
        }

    }
    

}
