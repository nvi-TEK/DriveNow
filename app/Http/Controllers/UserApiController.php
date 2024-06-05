<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

//Hubtel SMS
use NotificationChannels\Hubtel\HubtelChannel;
use NotificationChannels\Hubtel\HubtelMessage;

use Log;
use DB;
use Auth;
use Hash;
use Setting;
use Exception;
// use Notification;
use Storage;
use Carbon\Carbon;
use App\Http\Controllers\SendPushNotification;
use App\Notifications\ResetPasswordOTP;
use App\Http\Controllers\ProviderResources\TripController;
use App\FleetSubaccount;
use App\DriverSubaccount;
use App\User;
use App\Enquiry;
use App\Fleet;
use App\Chat;
use App\UserLocation;
use App\ProviderService;
use App\UserRequests;
use App\Promocode;
use App\RequestFilter;
use App\ServiceType;
use App\Provider;
use App\FleetPrice;
use App\Settings;
use App\UserRequestRating;
use App\UserRequestPayment;
use App\Card;
use App\PromocodeUsage;
use App\Helpers\Helper;
use App\DriverRequestReceived;
use App\Marketers;
use App\MarketerReferrals;
use Twilio\Rest\Client;
use App\Notification;
use App\RaveTransaction;
use App\FailedRequest;
use App\ChangeDestination;
use App\EmergencyContact;
use App\OfflineRequestFilter;
use App\UploadImages;
use App\MLMUserNetwork;
use App\MLMDriverNetwork;
use App\MLMUserCommission;
use App\MLMDriverCommission;


class UserApiController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function signup(Request $request)
    {
        $this->validate($request, [
                'social_unique_id' => ['required_if:login_by,facebook,google','unique:users'],
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'device_id' => 'required',
                'login_by' => 'required|in:manual,facebook,google',
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|email|max:255',
                // 'mobile' => 'required|between:6,13',
                'password' => 'required|min:6',
                'picture' => 'mimes:jpeg,bmp,png',
            ]);



        try{
            $Users = User::where('email',$request->email)
                        ->first();
            if($Users)
            {
                return response()->json(['success' => FALSE, 'message'=> 'Already Registered with us, Please login'], 200);
            }
            else{
                if($request->mobile[0] == "0"){
                    $request->mobile = ltrim($request->mobile, 0);
                }
                $User = $request->all();
                $FROM = 0; $TO = 'zzzz';
                $code = base_convert(rand( $FROM ,base_convert( $TO , 36,10)),10,36);
                
                $otp = rand(1000, 9999);
                if($request->country_code != ""){
                    $User['country_code'] = $request->country_code;
                }
                if($request->emergency_no != ""){
                    $User['emergency_no'] = $request->emergency_no;
                }
                
                
                if($request->login_by == 'manual'){
                if ($request->hasFile('picture')) {
                // Storage::delete($user->picture);
                $User['picture'] = Helper::upload_picture($request->picture);
                }
                $User['password'] = bcrypt($request->password);
                }
                else{
                    $Users = User::where('social_unique_id',$request->social_unique_id)
                            // ->where('user_id', Auth::user()->id)
                            ->first();
                    if(!$Users)
                    {
                        $User['picture'] = $request->image;
                        $User['social_unique_id'] = $request->social_unique_id;
                        $User['password'] = bcrypt('t@mi2h'); 
                    }
                    else{
                        return response()->json(['success' => FALSE, 'message'=> 'Already Registered with us, Please login'], 200);
                    }
                   
                }

                
                //Log::info($User);

                $User['otp'] = $otp;
                $User['fleet'] = '1';
                $rand = rand(100, 999);
                $name =  substr($request->first_name, 0, 3);
                $referral_code = strtoupper($name.$rand);
                $User['referal'] = $referral_code;
                $User['otp_activation'] = 0;

                

                $User['payment_mode'] = 'CASH';
                $User['password'] = bcrypt($request->password);
                
                $User = User::create($User);
                if($request->referral != "")
                {
                    $marketer = Marketers::where('referral_code', $request->referral)->first();
                
                    $user_referal = User::where('referal', $request->referral)->first();
                    $driver_referal = Provider::where('referal', $request->referral)->first();
                    if($user_referal)
                    {  
                        $User->user_referred = $request->referral;
                        $User->wallet_balance = Setting::get('user_to_user_referral', 0);
                        $User->referral_used = 1;
                        $User->save();
                        $user_referal->wallet_balance += Setting::get('user_to_user_referral');
                        $user_referal->save();

                    }else if($driver_referal)
                    {  
                        if($driver_referal->ambassador == 1){
                            $bonus = Setting::get('driver_to_user_referral', 0);
                        }else{
                            $bonus = Setting::get('ambassadors_to_user_referral', 0);
                        }
                        $User->driver_referred = $request->referral;
                        $User->wallet_balance = $bonus;
                        $User->referral_used = 1;
                        $User->save();
                        $driver_referal->wallet_balance += Setting::get('driver_to_user_referral');
                        $driver_referal->save();
                        if($driver_referal->official_drivers == 1){
                            $driver_referal->work_pay_balance += Setting::get('work_pay_to_user_referral');
                            $driver_referal->save();
                        }
                    }else if($marketer){
                        $User->marketer = $marketer->id;
                        $marketer_referrals = new MarketerReferrals;
                        $marketer_referrals->marketer_id = $marketer->id;
                        $marketer_referrals->user_id = $User->id;
                        $marketer_referrals->referrer_code = $request->referral;
                        $marketer->total_referrals = $marketer->total_referrals + 1;
                        $marketer->user_referrals = $marketer->user_referrals + 1;
                        $marketer_referrals->save();
                        $marketer->save();
                        $User->referral_used = 1;
                        $User->save(); 

                    }else{
                        return response()->json(['success' => FALSE, 'message' => 'Referal code not exist']);
                    }

                    $l1 = $l2 = $l3 = $l4 = $l5 = array();
                        $ul1 = $ul2 = $ul3 = $ul4 = $ul5 = "";

                        if($User->user_referred != ''){
                            $l1 = User::where('referal',$User->user_referred)->first();
                            if($l1){
                                $ul1 = "u_".$l1->id;
                            }
                            
                        }else{
                            $l1 = Provider::where('referal',$User->driver_referred)->first();
                            if($l1){
                                $ul1 = "d_".$l1->id;
                            }
                            
                        }

                        if($l1){
                            //Level 2
                            if($l1->user_referred !=''){
                                $l2 = User::where('referal',$l1->user_referred)->first();
                                if($l2){
                                    $ul2 = "u_".$l2->id;
                                }
                            }else if($l1->driver_referred !=''){
                                $l2 = Provider::where('referal',$l1->driver_referred)->first();
                                if($l2){
                                    $ul2 = "d_".$l2->id;
                                }
                            }
                            if($l2){
                                 //Level 3
                                if($l2->user_referred !=''){
                                    $l3 = User::where('referal',$l2->user_referred)->first();
                                    if($l3){
                                        $ul3 = "u_".$l3->id;
                                    }
                                }else if($l2->driver_referred !=''){
                                    $l3 = Provider::where('referal',$l2->driver_referred)->first();
                                    if($l3){
                                        $ul3 = "d_".$l3->id;
                                    }
                                }
                                if($l3){
                                     //Level 4
                                    if($l3->user_referred !=''){
                                        $l4 = User::where('referal',$l3->user_referred)->first();
                                        if($l4){
                                            $ul4 = "u_".$l4->id;
                                        }
                                    }else if($l3->driver_referred !=''){
                                        $l4 = Provider::where('referal',$l3->driver_referred)->first();
                                        if($l4){
                                            $ul4 = "d_".$l4->id;
                                        }
                                    }
                                    if($l4){
                                        //Level 5
                                        if($l4->user_referred !=''){
                                            $l5 = User::where('referal',$l4->user_referred)->first();
                                            if($l5){
                                                $ul5 = "u_".$l5->id;
                                            }
                                        }else if($l4->driver_referred !=''){
                                            $l5 = Provider::where('referal',$l4->driver_referred)->first();
                                            if($l5){
                                                $ul5 = "d_".$l5->id;
                                            }
                                        }
                                    }
                                    
                                }
                               
                            }
                        }
                        if($ul1 != ''){
                            $network = MLMUserNetwork::where('user_id',$User->id)->first();
                            if(!$network){
                                $network = new MLMUserNetwork;
                            }
                        
                            $network->user_id = $User->id;
                            $network->l1 = $ul1;
                            $network->l2 = $ul2;
                            $network->l3 = $ul3;
                            $network->l4 = $ul4;
                            $network->l5 = $ul5;
                            $network->save();
                        }

                }
                $User->access_token = $User->token()?:$User->createToken('socialLogin');
                return response()->json(['success' => TRUE, 'data'=> $User], 200);
            }

        } catch (Exception $e) {
            
             return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function duplicate(){
        $mobile = array('0509139522', '05410319152', '0542429835', '0542811190', '0543156290', '0543552849', '0544588439', '0545029018', '0546489442', '0546695783', '0549212887', '0552019041', '0552109035', '0552988570', '0553501274', '0554334806', '0554375626', '0554919818', '0555253085', '0556559504', '0556569728', '0557603950', '0557640179', '0558183366', '056296671', '0571177975', '0593986459', '099727421', '200117070', '201986668', '202009056', '202541419', '202772777', '207343318', '207474766', '208200786', '208642037', '209397324', '235238536', '235525663', '240101689', '240758810', '240899542', '241566714', '241874863', '241907103', '242177374', '242205397', '242335554', '242358622', '242434828', '242553692', '242674189', '242763807', '243160652', '243213298', '243368560', '243444543', '243445911', '243552201', '243589666', '243637398', '243758835', '243907226', '243919462', '244022032', '244297482', '244467541', '244829545', '244868818', '244878905', '244903435', '244998006', '245505854', '245682902', '245784745', '246061411', '246069783', '246367312', '246786495', '246830590', '246859919', '247129618', '247505644', '247704240', '247953816', '248213207', '248296902', '248522484', '248769108', '248794395', '249127095', '249153608', '249379726', '249412771', '249831462', '249967220', '261482883', '261793127', '262445807', '269862242', '276730117', '500222729', '500239414', '501227730', '501651463', '502306892', '503866967', '504166419', '505085544', '505200975', '505483483', '505717199', '506136155', '506718345', '507209547', '509870700', '540328284', '540370403', '540592522', '541456967', '541549290', '541848855', '541922010', '542638685', '543492211', '543909874', '544140140', '544282186', '544427331', '544546881', '544597984', '544858275', '544999944', '545107776', '545251940', '545410154', '546002225', '546157461', '546273004', '546425737', '547014585', '547196738', '548980923', '549057626', '549111250', '549125498', '549529842', '550208441', '550512346', '550933584', '551335284', '551654818', '551797098', '551916109', '553665029', '554601144', '554759402', '554805293', '554842329', '555080443', '555653838', '555929169', '557305215', '557373266', '557479389', '557700291', '558441244', '558487788', '558682610', '559004467', '559566811', '559909729', '570172806', '571556353', '574339801', '576137351', '591559118', '592692809', '593303499', '593305037', '594597646', '595551015', '596573101', '6381305467', '886322963', '8870687168', '9025861307', '9360525835');
        $user_requests = $user_id = array();
        for ($i=0; $i < count($mobile) ; $i++) { 
            
                $user_id = User::where('mobile', $mobile[$i])->pluck('id');

                for ($j=0; $j < count($user_id); $j++) { 
                    $request = UserRequests::where('user_id', $user_id[$j])->count();
                    if($request == 0){
                        $user_requests[] = User::where('mobile', $mobile[$i])->orderBy('created_at', 'desc')->first();
                    }
                }
        }

        $request = array();
        for ($i=0; $i < count($user_requests) ; $i++) { 
            $request[] = $user_requests[$i]['mobile'];

        }
        $sdf = array_unique($request);
        dd($sdf);
    }

        public function login(Request $request)
    {
        $this->validate($request, [
                'social_unique_id' => ['required_if:login_by,facebook,google','unique:users'],
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'device_id' => 'required',
                'login_by' => 'required|in:manual,facebook,google',
                'mobile' => 'required|between:6,13',
                'picture' => 'mimes:jpeg,bmp,png',
            ]);



        try{
            if($request->mobile[0] == "0"){
                $mobile = ltrim($request->mobile, 0);
            }else{
                $mobile = $request->mobile;
            }
            Log::info("User Login Mobile: ". $mobile ." - ". $request->mobile);
            if($request->has('email')){
                if($request->email != ''){
                    $Users = User::where('mobile',$mobile)->where('email', $request->email)
                        ->first();  
                }else{
                    $Users = User::where('mobile',$mobile)->get();
                }
                
            }else{
                $Users = User::where('mobile',$mobile)->get();    
            }

            if(count($Users)== 1)
            {   
                $User = User::where('mobile', $mobile)->first();
                if($User->delete_acc == 1){
                    return response()->json(['success' => FALSE,'error' => 'Account was deleted!'], 200);
                }
                if($request->device_token != "" || $request->device_token != null){
                    $User->device_token = $request->device_token;
                }

                if($request->device_type != "" || $request->device_type != null){
                    $User->device_type = $request->device_type;
                }

                if($request->device_id != "" || $request->device_id != null){
                    $User->device_id = $request->device_id;
                }
                 if($request->country_code != ""){
                        $User->country_code = $request->country_code;
                    } 
                $User->save();

                $access = DB::table('oauth_access_tokens')->where('user_id',$User->id)->delete();
                $token = $User->createToken($User->first_name)->accessToken;
                $User->access_token = $token;
                if($User->first_name != ""){
                    $User->new = 0;
                    $User->new_user = 0;
                }else{
                    $User->new = 1;
                    $User->new_user = 1;
                }
                $User->delete_menu = 1;
                
                return response()->json(['success' => TRUE, 'data'=> $User], 200);
            }else if(count($Users) > 1){
                // if($User->delete_acc == 1){
                //     return response()->json(['success' => FALSE,'error' => 'Account was deleted!'], 200);
                // }
                return response()->json(['success' => FALSE, 'message'=> "There are multiple accounts associated with ".$mobile.". Please enter Email for help us to find your account"], 200);
            }
            else if(count($Users)== 0){

                if($request->mobile[0] == "0"){
                    $mobile = ltrim($request->mobile, 0);
                }else{
                    $mobile = $request->mobile;
                }
                Log::info("User Login Mobile: ". $mobile ." - ". $request->mobile);
                $Users = User::where('mobile',$mobile)
                        ->get();
                if(count($Users) > 0){
                    $User = User::where('mobile', $mobile)->first();
                    if($request->device_token != "" || $request->device_token != null){
                    $User->device_token = $request->device_token;
                }

                if($request->device_type != "" || $request->device_type != null){
                    $User->device_type = $request->device_type;
                }

                if($request->device_id != "" || $request->device_id != null){
                    $User->device_id = $request->device_id;
                }
                    $User->save();

                    $access = DB::table('oauth_access_tokens')->where('user_id',$User->id)->delete();
                    $token = $User->createToken($User->first_name)->accessToken;
                    $User->access_token = $token;
                        if($User->first_name != ""){
                            $User->new = 0;
                            $User->new_user = 0;
                        }else{
                            $User->new = 1;
                            $User->new_user = 1;
                        }
                
                    return response()->json(['success' => TRUE, 'data'=> $User], 200);
                }else{
                    if($request->mobile[0] == "0"){
                        $mobile = ltrim($request->mobile, 0);
                    }else{
                        $mobile = $request->mobile;
                    }
                    $User = $request->all();
                    $User['mobile'] = $mobile;
                    $FROM = 0; $TO = 'zzzz';
                    $code = base_convert(rand( $FROM ,base_convert( $TO , 36,10)),10,36);
                    
                    $otp = rand(1000, 9999);
                    if($request->country_code != ""){
                        $User['country_code'] = $request->country_code;
                    }                
                    
                    if($request->login_by == 'manual'){
                        if ($request->hasFile('picture')) {
                            $User['picture'] = Helper::upload_picture($request->picture);
                        }
                            $User['password'] = bcrypt("Eganow".$mobile);
                    }else{
                        $Users = User::where('social_unique_id',$request->social_unique_id)
                                // ->where('user_id', Auth::user()->id)
                                ->first();
                        if(!$Users)
                        {
                            $User['picture'] = $request->image;
                            $User['social_unique_id'] = $request->social_unique_id;
                            $User['password'] = bcrypt("Eganow".$mobile); 
                        }
                        else{
                            return response()->json(['success' => FALSE, 'message'=> 'Already Registered with us, Please login'], 200);
                        }
                       
                    }
                    //Log::info($User);

                    $User['otp'] = $otp;
                    $User['fleet'] = '1';
                    $rand = rand(100, 999);
                    $referral_code = strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 6));
                    $User['referal'] = $referral_code;
                    $User['otp_activation'] = 0;
                    $User['payment_mode'] = 'CASH';
                    $User['password'] = bcrypt("Eganow".$mobile);
                    
                    $User = User::create($User);
                    if($request->referral != "")
                    {
                        $marketer = Marketers::where('referral_code', $request->referral)->first();
                        $user_referal = User::where('referal', $request->referral)->first();
                        $driver_referal = Provider::where('referal', $request->referral)->first();

                        if(count($user_referal) !=0 || count($driver_referal) !=0 || count($marketer) !=0 ){
                            if($user_referal){  
                                $bonus = Setting::get('user_to_user_referral', 0);
                                $User->user_referred = $request->referral;
                                $User->wallet_balance += $bonus;
                                $User->referral_used = 1;
                                $User->save();
                            }else if($driver_referal){ 

                                if($driver_referal->ambassador == 1){
                                    $bonus = Setting::get('driver_to_user_referral', 0);
                                }else{
                                    $bonus = Setting::get('ambassadors_to_user_referral', 0);
                                } 
                                $User->driver_referred = $request->referral;
                                $User->wallet_balance += $bonus;
                                $User->referral_used = 1;
                                $User->save();
                            }else if($marketer){

                                $User->marketer = $marketer->id;
                                $marketer_referrals = new MarketerReferrals;
                                $marketer_referrals->marketer_id = $marketer->id;
                                $marketer_referrals->user_id = $User->id;
                                $marketer_referrals->referrer_code = $request->referral;
                                $marketer->total_referrals = $marketer->total_referrals + 1;
                                $marketer->user_referrals = $marketer->user_referrals + 1;
                                $marketer_referrals->save();
                                $marketer->save();
                                $bonus = Setting::get('marketer_to_user_referral', '10');
                                $User->wallet_balance += $bonus;
                                $User->referral_used = 1;
                                $User->save(); 
                            }
                            $code = rand(1000, 9999);
                            $name = substr($User->first_name, 0, 2);
                            $reference = "PWT".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->user_id = $User->id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->narration = "Referral bonus credited for ". $request->referral;
                            $rave_transactions->amount = $bonus;
                            $rave_transactions->status = 1;
                            $rave_transactions->type = "credit";
                            $rave_transactions->save();
                        
                        (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($bonus));
                        return response()->json(['success' => TRUE, 'message' => 'Referral code applied successfully'], 200);

                    }else{
                            return response()->json(['success' => FALSE, 'message' => 'Referal code not exist']);
                        }
                    }
                    $access = DB::table('oauth_access_tokens')->where('user_id',$User->id)->delete();
                    $token = $User->createToken($User->first_name)->accessToken;
                    $User->access_token = $token;
                    $User->new = 1;
                    $User->new_user = 1;
                    $User->delete_menu = 1;

                    return response()->json(['success' => TRUE, 'data'=> $User], 200);
                }
            }

        } catch (Exception $e) {
            Log::info($e);
             return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }





     public function notifications(){
        try{
            $notifications = Notification::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

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
            
            $notifications = Notification::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

            return response()->json(['success' => TRUE, 'notifications'=> $notifications, 'message' => 'Notification has been removed'], 200);
        }
        catch (Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function via(Request $request)
    {
        return [HubtelChannel::class];
    }

    public function sendOTP(Request $request){
        try{
            $otp = rand(1000, 9999);
            $to = $request->to;
            $to = str_replace(" ", "", $to);
            $cc = (substr($to, 0, 3));
            $from = "Eganow";
                if(str_contains($cc,"23") == true){
                $content = urlencode("[#] Eganow: Your mobile verification code is ".$otp.". If your app has 6 spaces, leave the last 2 spaces blank q5TQY2nFEfO");
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
                    // $content = urlencode("[#] Eganow: Your mobile verification code is ".$otp.". If your app has 6 spaces, leave the last 2 spaces blank q5TQY2nFEfO");
                    $content = "[#] Eganow: Your mobile verification code is ".$otp.". If your app has 6 spaces, leave the last 2 spaces blank q5TQY2nFEfO";
                    if(str_contains($to,"+233") == true){
                        $mobile = substr($to, 1);
                    }else{
                        $mobile = $to;
                    }
                    
                    // if($mobile[0] == 0){
                    //     $receiver = $mobile;
                    // }else{
                    //     $receiver = "0".$mobile; 
                    // }

                    $sendMessage = sendMessageRancard($mobile, $content);
                    
                    if($sendMessage['code'] == "200"){
                        return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Rancard'], 200);
                    }else{
                        return response()->json(['success' => FALSE,'otp' => $otp, 'message' => 'Sending SMS to '.$to.' failed. Please try again later. '], 200);
                    }
                    

                    // $client1 = new \GuzzleHttp\Client();

                    // $url1 = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&getBalance=true";


                    // $headers1 = ['Content-Type' => 'application/json'];
                    
                    // $res1 = $client1->get($url1, ['headers' => $headers1]);

                    // $data = json_decode($res1->getBody());

                    // $balance = round(str_replace("Messaging balance for API User: f3En@x is","", $data));

                    // $client = new \GuzzleHttp\Client();

                    // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                    // $headers = ['Content-Type' => 'application/json'];
                    
                    // $res = $client->get($url, ['headers' => $headers]);

                    // $code = (string)$res->getBody();
                    // $codeT = str_replace("\n","",$code);
                    // Log::info($url);
                    // if($codeT == "000"){
                    //     return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Rancard'], 200);
                    // }
                    // else{
                    
                    //     $content = "[#] Eganow: Your mobile verification code is ".$otp.". If your app has 6 spaces, leave the last 2 spaces blank q5TQY2nFEfO";
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
                $content = "[#] Eganow: Your mobile verification code is ".$otp.". If your app has 6 spaces, leave the last 2 spaces blank q5TQY2nFEfO";
                $sendTwilio = sendMessageTwilio($to, $content);
                Log::info($sendTwilio);
                if($sendTwilio){
                   return response()->json(['success' => TRUE, 'otp' => $otp, 'data' => $sendTwilio], 200); 
                }else{
                    return response()->json(['success' => FALSE,'otp' => $otp, 'message' => trans('api.something_went_wrong')], 200);
                }
            }
        }
        catch (Exception $e) {
            Log::info($e);
             return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function sendMessageTwilio(Request $request)
    {
        try{
            $account_sid = env('TWILIO_SID');
            $auth_token = env('TWILIO_AUTH_TOKEN');
            $twilio_number = env('TWILIO_NUMBER');
            $client = new Client($account_sid, $auth_token);
            $message = $client->messages->create($request->to, ['from' => $twilio_number, 'body' => $request->message]);
            Log::info($message);
        }catch (Exception $e) {
            Log::info($e);
        }
        // return response()->json(['success' => TRUE, 'data' => $client], 200); 
    }

    public function changepayment(Request $request){

        $request_id = $request->request_id;
        try{
            $UserRequest = UserRequests::find($request_id);
            $UserRequest->payment_mode = $request->payment_mode;
            $UserRequest->save();
            
            (new SendPushNotification)->PaymentModeChanged($UserRequest);

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


             /**
     * Forgot Password.
     *
     * @return \Illuminate\Http\Response
     */


    public function forgot_password(Request $request){

        // $this->validate($request, [
        //         'email' => 'required|email|exists:users,email',
        //     ]);

        try{  
            
            $user = User::where('email' , $request->email)->first();
            if(!$user){
                return response()->json(['success' => FALSE, 'message' => 'Account not found, please try again with different account!',],200);
            }

            $otp = rand(100000, 999999);

            $user->password = bcrypt($otp);

            $user->save();

            $to = $user->country_code.$user->mobile;

            $from = "Eganow";
            // $content = "Use this code ".$otp." to verify your mobile number";
            $content = urlencode("Eganow: Use this temporary code as your password to login and change password: ".$otp.". Book your next ride with Eganow.");
            $clientId = env("HUBTEL_API_KEY");
            $clientSecret = env("HUBTEL_API_SECRET");

            // $sendSms =  (new HubtelMessage)
            // ->from($from)
            // ->to($to)
            // ->content($content);
            
            $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
            // Notification::send($user, new ResetPasswordOTP($otp));
            if(count($sendSms) > 1){
                return response()->json(['success' => TRUE, 'otp' => $otp, 'data' => $sendSms], 200);
            }
            else if($sendSms == FALSE){
                $content = "Eganow: Use this temporary code as your password to login and change password: ".$otp.". Book your next ride with Eganow.";
                $sendTwilio = sendMessageTwilio($to, $content);
                 //Log::info($sendTwilio);
                if($sendTwilio){
                   return response()->json(['success' => TRUE, 'otp' => $otp, 'data' => $sendTwilio], 200); 
                }else{
                    return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                }
            }else{
                $content = "Eganow: Use this temporary code as your password to login and change password: ".$otp.". Book your next ride with Eganow.";
                $sendTwilio = sendMessageTwilio($to, $content);
                //Log::info($sendTwilio);
                if($sendTwilio){
                   return response()->json(['success' => TRUE, 'otp' => $otp, 'data' => $sendTwilio], 200); 
                }else{
                    return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                }
            }

            return response()->json([
                'success' => TRUE,
                'message' => 'OTP sent to your email!',
                'user' => $user
            ]);

        }catch(Exception $e){
            
                return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function saveToken(Request $request)
    {
     $this->validate($request, [
                'device_id' => 'required',
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
            ]);

            $User = Auth::user();
            if($request->device_token != "" || $request->device_token != null){
                    $User->device_token = $request->device_token;
                }

                if($request->device_type != "" || $request->device_type != null){
                    $User->device_type = $request->device_type;
                }

                if($request->device_id != "" || $request->device_id != null){
                    $User->device_id = $request->device_id;
                }
            $User->save();

        return response()->json(['success' => TRUE, 'data'=> $User], 200);
    }

    /**
     * Reset Password.
     *
     * @return \Illuminate\Http\Response
     */

    public function reset_password(Request $request){

        $this->validate($request, [
                'password' => 'required|confirmed|min:6',
                'id' => 'required|numeric|exists:users,id'
            ]);

        try{

            $User = User::findOrFail($request->id);
            $User->password = bcrypt($request->password);
            $User->save();

            if($request->ajax()) {
                return response()->json(['success' => FALSE, 'message' => 'Password Updated']);
            }

        }catch (Exception $e) {
            
            if($request->ajax()) {
                return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')]);
            }
        }
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function change_password(Request $request){

        // $this->validate($request, [
        //         'password' => 'required|confirmed|min:6',
        //         // 'old_password' => 'required',
        //     ]);

        $User = Auth::user();
        if($request->password != $request->password_confirmation){
            return response()->json(['error' => TRUE, 'message' => trans('api.user.password_mismatch')], 200);
        }
        // if(Hash::check($request->old_password, $User->password))
        // {
            $User->password = bcrypt($request->password);
            $User->save();

            if($request->ajax()) {
                return response()->json(['success' => TRUE, 'message' => trans('api.user.password_updated')], 200);
            }else{
                return back()->with('flash_success', 'Password Updated');
            }

        // } else {
        //      if($request->ajax()) {
        //         return response()->json(['success' => FALSE, 'message' => trans('api.user.incorrect_password')]);
        //     }else{
        //         return back()->with('flash_error', 'InCorrect Password');
        //     }
        // }

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function update_location(Request $request){

        $this->validate($request, [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

        if($user = User::find(Auth::user()->id)){

            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            $user->save();

            return response()->json(['success' => TRUE, 'message' => trans('api.user.location_updated')]);

        }else{

            return response()->json(['success' => FALSE, 'message' => trans('api.user.user_not_found')], 200);

        }

    }

    public function change_destination(Request $request){

        $this->validate($request, [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);


            if($user = UserRequests::find($request->request_id)){
                $change_destination = ChangeDestination::where('request_id',$request->request_id)->where('driver_id', $user->provider_id)->where('status', 0)->first();
                if($request->change == 0){
                    (new SendPushNotification)->UserChangeDestinationReject($user->current_provider_id);
                    if($change_destination){
                        $change_destination->status = 2;
                        $change_destination->save();
                    }

                    return response()->json(['success' => FALSE, 'message' => trans('api.user.user_change_destination_reject')]);
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
                    // Send push to driver for destination changed by the user
                    (new SendPushNotification)->UserChangeDestination($user->current_provider_id);

                    return response()->json(['success' => TRUE, 'message' => trans('api.user.destination_updated')]);
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

           //Log::info(env("GOOGLE_MAP_KEY"));

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
            
            
            $service_type = FleetPrice::where('service_id', $user->service_type_id)->where('fleet_id', Auth::user()->fleet)->first();
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

            if($PromocodeUsage = PromocodeUsage::where('user_id',Auth::user()->id)->where('status','ADDED')->first()){
                if($Promocode = Promocode::find($PromocodeUsage->promocode_id)){
                    if($Promocode->discount != 0){
                        $Discount = $total * ( $Promocode->discount / 100);
                    }
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
                    'wallet_balance' => number_format(Auth::user()->wallet_balance,2),
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

    public function change_destination_request(Request $request){

        $this->validate($request, [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if($user = UserRequests::find($request->request_id)){
            $change_destination = ChangeDestination::where('request_id',$request->request_id)->where('status', 0)->first();
            if(count($change_destination) == 0){
                $change_destination = new ChangeDestination;
                $change_destination->latitude = $request->latitude;
                $change_destination->longitude = $request->longitude;
                $change_destination->address = $request->address;
                $change_destination->title = $request->title;
                $change_destination->fare = $request->fare;
                $change_destination->request_id = $request->request_id;
                $change_destination->user_id = Auth::user()->id;
                $change_destination->save();

                // Send push to driver for destination changed by the user

                (new SendPushNotification)->UserChangeDestinationRequest($user->provider_id, $request->latitude, $request->longitude, $request->request_id, $request->address, $request->fare, $request->title);

                return response()->json(['success' => TRUE, 'message' => trans('api.user.destination_request_sent')]);
            }else{
                return response()->json(['success' => FALSE, 'message' => trans('api.user.live_destination_cant_change')], 200);
            }
            
        }else{

            return response()->json(['success' => FALSE, 'message' => trans('api.user.destination_cant_change')], 200);

        }

    }

    //Nearby Stores
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function details(Request $request){

        // $this->validate($request, [
        //     'device_type' => 'in:android,ios',
        // ]);

        try{
            $user = User::where('id',Auth::user()->id)->first();
            if($user){

                if($request->device_token != "" || $request->device_token != null){
                    $user->device_token = $request->device_token;
                }

                if($request->device_type != "" || $request->device_type != null){
                    $user->device_type = $request->device_type;
                }

                if($request->device_id != "" || $request->device_id != null){
                    $user->device_id = $request->device_id;
                }
                if($request->has('android_app_version')){
                    $user->android_app_version = $request->android_app_version;
                    
                }  

                if($request->has('ios_app_version')){
                    $user->ios_app_version = $request->ios_app_version;
                    
                }   

                $user->save();
                $user->user_referral = User::where('user_referred', $user->referal)->count();
                $user->driver_referral = Provider::where('user_referred', $user->referal)->count();
                $user->currency = Setting::get('currency');
                $user->referral_bonus = Setting::get('referral_bonus');
                $user->time_out = Setting::get('trip_search_time', 180);
                $user->welcome_image = Setting::get('welcome_image');

                $user->sos_number = Setting::get('sos_number');

                $user->eganow_sos_number = Setting::get('eganow_sos_number');
                $user->android_user_version = Setting::get('android_user_version');
                $user->ios_user_version = Setting::get('ios_user_version');
                $user->surge = Setting::get('surge_percentage');

                $user->android_user_mapkey = Setting::get('android_user_mapkey');
                $user->ios_user_mapkey = Setting::get('ios_user_mapkey');

                $total_tot = $total_com = $total_can = 0;
                $total = UserRequests::where('user_id',$user->id)
                    ->with('payment')
                    ->get();

                for($i=0; $i < count($total); $i++) {
                        if($total[$i]['status'] == 'COMPLETED'){
                            $total_com +=1;
                        }
                        if($total[$i]['status'] == 'CANCELLED'){
                            $total_can +=1;
                        }
                    }
                    $total_tot = count($total);

                //Today Earnings

                $user->total_request = $total_tot;
                $user->completed_request = $total_com;
                $user->cancelled_request = $total_can;

                $last_trip_date = $last_trip_status = '';

                $last_trip = UserRequests::where('user_id',$user->id)->orderBy('created_at', 'desc')->first();
                if($last_trip){
                    $last_trip_date = $last_trip->created_at;
                    $last_trip_status = $last_trip->status;
                }
                $user->last_booking_date = $last_trip_date;
                $user->last_trip_status = $last_trip_status;


                $UserRequest = UserRequests::where('user_id', $user->id)->whereNotIn('user_requests.status' , ['CANCELLED', 'COMPLETED','SCHEDULED','SEARCHING'])->first();
                if($UserRequest){

                    $user->active_request_id = $UserRequest->id;
                        if($UserRequest->service_type->is_delivery == 1){
                            $service_flow = "Delivery";
                        }else{
                            $service_flow= "Ride";
                        }
                    $user->active_request_flow = $service_flow;
                }else{
                    $user->active_request_id = $user->active_request_flow = "";
                }
                $user->delete_menu = 1;
                

                
                return response()->json(['success' => TRUE, 'data' => $user ]);

            }else{
                return response()->json(['success' => FALSE, 'message' => trans('api.user.user_not_found')], 200);
            }
        }
        catch (Exception $e) {
            Log::info($e);
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function update_profile(Request $request)
    {

        $this->validate($request, [
                'first_name' => 'max:255',
                'last_name' => 'max:255',
                // 'email' => 'email|unique:users,email,'.Auth::user()->id,
                'mobile' => 'between:6,13',
                'picture' => 'mimes:jpeg,bmp,png',
            ]);
        Log::info($request->all());

         try {
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }

            $user = User::findOrFail(Auth::user()->id);

            if($request->has('first_name')){ 
                $user->first_name = $request->first_name;
            }
            
            if($request->has('last_name')){
                $user->last_name = $request->last_name;
            }

            if($request->has('mobile')){
                $user->mobile = $request->mobile;
            }

            if($request->has('country_code')){
                $user->country_code = $request->country_code;
            }
            
            if($request->has('email')){
                $user->email = $request->email;
            }

            if($request->has('fleet')){
                $user->fleet = $request->fleet;
            }

            if($request->has('sos_number')){
                $user->sos_number = $request->sos_number;
            }

            if($request->has('android_app_version')){
                $Provider->android_app_version = $request->android_app_version;
            }  

            if($request->has('ios_app_version')){
                $Provider->ios_app_version = $request->ios_app_version;
            } 


            if($request->device_token != "" || $request->device_token != null){
                    $user->device_token = $request->device_token;
                }

                if($request->device_type != "" || $request->device_type != null){
                    $user->device_type = $request->device_type;
                }

                if($request->device_id != "" || $request->device_id != null){
                    $user->device_id = $request->device_id;
                }


            if ($request->picture != "") {

                    $name = $user->id."-usr-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with asset url';                    
                    $contents = file_get_contents($request->picture);
                    $path = Storage::disk('s3')->put('user_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    
                    $user->picture = $s3_url;
            }

            $user->save();

            if($request->ajax()) {
                return response()->json(['success' => TRUE, 'data'=> $user], 200);
            }else{
                return back()->with('flash_success', trans('api.user.profile_updated'));
            }
        }

        catch (ModelNotFoundException $e) {
            
             return response()->json(['success' => FALSE, 'message' => trans('api.user.user_not_found')], 200);
        }

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function services() {

        if($serviceList = ServiceType::all()) {
            return response()->json(['success' => TRUE, 'services'=> $serviceList], 200);
        } else {
            return response()->json(['success' => FALSE, 'message' => trans('api.services_not_found')], 200);
        }

    }

    public function serviceTypes() {
        $user = User::findOrFail(Auth::user()->id);
        $serviceList = FleetPrice::where('fleet_id',$user->fleet)->where('status',1)->with('service')->get();
        if(count($serviceList)>0) {
            return response()->json(['success' => TRUE, 'services'=> $serviceList], 200);
        } else {
            return response()->json(['success' => FALSE, 'message' => trans('api.services_not_found')], 200);
        }

    }

    public function service_estimate(Request $request){
            Log::info(env("GOOGLE_MAP_KEY"));

            $distance = Setting::get('provider_search_radius', '10');    

            $minimum_balance = Setting::get('minimum_balance', '0');
           
        
            $latitude = number_format($request->s_latitude, 7);
            $longitude = number_format($request->s_longitude,7);

            $location_updated = Setting::get('location_update_interval');

            $Providers = Provider::whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                        ->where('status', 'approved')
                        ->where('availability', '1')
                        ->where('archive', '0')
                        ->where('wallet_balance', '>=', $minimum_balance)
                        ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                        ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                        ->orderBy('distance', 'asc')->first();
            $ETA = "NA";

            if($Providers){
                $d_latitude = $Providers['latitude'];
                $d_longitude = $Providers['longitude'];

                $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$d_latitude.",".$d_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

                // $client = new Client(); //GuzzleHttp\Client
                // $result = $client->get($details);

                $json = curl($details);

                $details = json_decode($json, TRUE);
                $meter = $details['rows'][0]['elements'][0]['distance']['value'];
                $time = $details['rows'][0]['elements'][0]['duration']['text'];
                $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

                $kilometer = round($meter/1000);
                $ETA = round($seconds/60);
            }
            Log::info("ETA Driver: ". $ETA);
            $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$request->d_latitude.",".$request->d_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

            // $client = new Client(); //GuzzleHttp\Client
            // $result = $client->get($details);

            $json = curl($details);

            $details = json_decode($json, TRUE);
            $meter = $details['rows'][0]['elements'][0]['distance']['value'];
            $time = $details['rows'][0]['elements'][0]['duration']['text'];
            $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

            $kilometer = round($meter/1000);
            $minutes = round($seconds/60);

            if(count($Providers) == 0){
                $ETA = $minutes;
            }
            Log::info("ETA Trip: ". $ETA);

            $tax_percentage = Setting::get('tax_percentage');
            $commission_percentage = Setting::get('commission_percentage');
            
            $serviceList = ServiceType::where('type', 0)->get();

            $services = array();

            $j=0;
            for($i=0; $i < count($serviceList); $i++) {
                $j = $j+1;
               if(Auth::user()->fleet ==1){
                    $service_type = ServiceType::findOrFail($serviceList[$i]->id);
               }else{
                $service_type = FleetPrice::where('service_id', $serviceList[$i]->id)->where('fleet_id', Auth::user()->fleet)->first();
                if(!$service_type){
                    $service_type = ServiceType::findOrFail($serviceList[$i]->id);
                }
               }
                

                $price_base = $service_type->fixed;
                $kilometer = $kilometer - $service_type->base_radius;
                $time_price = $service_type->time * $minutes;
                $distance_price = $kilometer * $service_type->price;
               
                // if($service_type->base_radius != 0 && $kilometer  <= $service_type->base_radius){
                //     $total = $price_base;
                // }else{
                //     $kilometer = $kilometer - $service_type->base_radius;
                //     $price = ($kilometer * $service_type->price) + ($service_type->time * $minutes);
                //     $total = $price + $price_base;
                // }

                if(Setting::get('surge_percentage') != 0){
                    $price_base = $price_base * Setting::get('surge_percentage');
                    $time_price = $time_price * Setting::get('surge_percentage');
                    $distance_price = $distance_price * Setting::get('surge_percentage');
                }

                $total = $price_base + $time_price + $distance_price;
                $tax_price = ( $tax_percentage/100 ) * $total;
                $total = $total + $tax_price;

                if($total <= $service_type->minimum_fare){
                    $total = $service_type->minimum_fare;
                }

                // $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');

                // $distance = Setting::get('provider_search_radius', '10');
                // $latitude = $request->s_latitude;
                // $longitude = $request->s_longitude;

                // $Providers = Provider::whereIn('id', $ActiveProviders)
                //     ->where('status', 'approved')
                //     ->where('fleet', $request->fleet)
                //     ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                //     ->get();

                // if($Providers->count() <= Setting::get('surge_trigger') && $Providers->count() > 0){
                //     $surge_price = (Setting::get('surge_percentage')/100) * $total;
                //     $total += $surge_price;
                // }

                
                $serviceList[$i]->fixed = $service_type->fixed;
                $serviceList[$i]->base_radius = $service_type->base_radius; 
                $serviceList[$i]->price = $service_type->price;
                $serviceList[$i]->time = $ETA;
                $serviceList[$i]->drivercommission = $service_type->drivercommission;
                $serviceList[$i]->commission = $service_type->commission;
                $serviceList[$i]->calculator = $service_type->calculator;
                $serviceList[$i]->description = $service_type->description;
                $serviceList[$i]->status = $service_type->status; 
                $serviceList[$i]->base_price = number_format($service_type->fixed,2);
                $serviceList[$i]->eta = $ETA;
                $serviceList[$i]->time_price = number_format($time_price,2);
                $serviceList[$i]->distance_price = number_format($distance_price,2);
                $serviceList[$i]->distance = $kilometer;
                $serviceList[$i]->estimated_fare = round($total);

            }
            $new_delivery = 0;
                $delivery_history = UserRequests::where('user_id', Auth::user()->id)->where('receiver_mobile', '!=', '')->count();
                if($delivery_history > 0){
                    $new_delivery = 0;
                }else{
                    $new_delivery = 1;
                }
            return response()->json(['success' => TRUE, 'services'=> $serviceList, 'new_delivery' => $new_delivery], 200); 
    }

    public function service_estimate_coda(Request $request){


            $distance = Setting::get('provider_search_radius', '10');    

            $minimum_balance = Setting::get('minimum_balance', '0');
           
        
            $latitude = number_format($request->s_latitude, 7);
            $longitude = number_format($request->s_longitude,7);

            $location_updated = Setting::get('location_update_interval');

            $Providers = Provider::whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                        ->where('status', 'approved')
                        ->where('availability', '1')
                        ->where('archive', '0')
                        ->where('wallet_balance', '>=', $minimum_balance)
                        ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                        ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                        ->orderBy('distance', 'asc')->first();
            $ETA = "NA";

            if($Providers){
                $d_latitude = $Providers['latitude'];
                $d_longitude = $Providers['longitude'];

                $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$d_latitude.",".$d_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

                // $client = new Client(); //GuzzleHttp\Client
                // $result = $client->get($details);

                $json = curl($details);

                $details = json_decode($json, TRUE);
                $meter = $details['rows'][0]['elements'][0]['distance']['value'];
                $time = $details['rows'][0]['elements'][0]['duration']['text'];
                $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

                $kilometer = round($meter/1000);
                $ETA = round($seconds/60);
            }
            Log::info("ETA Driver: ". $ETA);
            $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$request->d_latitude.",".$request->d_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

            // $client = new Client(); //GuzzleHttp\Client
            // $result = $client->get($details);

            $json = curl($details);

            $details = json_decode($json, TRUE);
            $meter = $details['rows'][0]['elements'][0]['distance']['value'];
            $time = $details['rows'][0]['elements'][0]['duration']['text'];
            $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

            $kilometer = round($meter/1000);
            $minutes = round($seconds/60);

            if(count($Providers) == 0){
                $ETA = $minutes;
            }
            Log::info("ETA Trip: ". $ETA);

            $tax_percentage = Setting::get('tax_percentage');
            $commission_percentage = Setting::get('commission_percentage');
            
            $serviceList = ServiceType::where('type',1)->get();
            $services = array();

            $j=0;
            for($i=0; $i < count($serviceList); $i++) {
                $j = $j+1;
               if(Auth::user()->fleet ==1){
                    $service_type = ServiceType::findOrFail($serviceList[$i]->id);
               }else{
                $service_type = FleetPrice::where('service_id', $serviceList[$i]->id)->where('fleet_id', Auth::user()->fleet)->first();
                if(!$service_type){
                    $service_type = ServiceType::findOrFail($serviceList[$i]->id);
                }
               }
                

                $price_base = $service_type->fixed;
                $kilometer = $kilometer - $service_type->base_radius;
                $time_price = $service_type->time * $minutes;
                $distance_price = $kilometer * $service_type->price;
               
                // if($service_type->base_radius != 0 && $kilometer  <= $service_type->base_radius){
                //     $total = $price_base;
                // }else{
                //     $kilometer = $kilometer - $service_type->base_radius;
                //     $price = ($kilometer * $service_type->price) + ($service_type->time * $minutes);
                //     $total = $price + $price_base;
                // }

                if(Setting::get('surge_percentage') != 0){
                    $price_base = $price_base * Setting::get('surge_percentage');
                    $time_price = $time_price * Setting::get('surge_percentage');
                    $distance_price = $distance_price * Setting::get('surge_percentage');
                }

                $total = $price_base + $time_price + $distance_price;
                $tax_price = ( $tax_percentage/100 ) * $total;
                $total = $total + $tax_price;

                if($total <= $service_type->minimum_fare){
                    $total = $service_type->minimum_fare;
                }

                // $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');

                // $distance = Setting::get('provider_search_radius', '10');
                // $latitude = $request->s_latitude;
                // $longitude = $request->s_longitude;

                // $Providers = Provider::whereIn('id', $ActiveProviders)
                //     ->where('status', 'approved')
                //     ->where('fleet', $request->fleet)
                //     ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                //     ->get();

                // if($Providers->count() <= Setting::get('surge_trigger') && $Providers->count() > 0){
                //     $surge_price = (Setting::get('surge_percentage')/100) * $total;
                //     $total += $surge_price;
                // }

                
                $serviceList[$i]->fixed = $service_type->fixed;
                $serviceList[$i]->base_radius = $service_type->base_radius; 
                $serviceList[$i]->price = $service_type->price;
                $serviceList[$i]->time = $ETA;
                $serviceList[$i]->drivercommission = $service_type->drivercommission;
                $serviceList[$i]->commission = $service_type->commission;
                $serviceList[$i]->calculator = $service_type->calculator;
                $serviceList[$i]->description = $service_type->description;
                $serviceList[$i]->status = $service_type->status; 
                $serviceList[$i]->base_price = number_format($service_type->fixed,2);
                $serviceList[$i]->eta = $ETA;
                $serviceList[$i]->time_price = number_format($time_price,2);
                $serviceList[$i]->distance_price = number_format($distance_price,2);
                $serviceList[$i]->distance = $kilometer;
                $serviceList[$i]->estimated_fare = round($total);

                
            }
            $new_delivery = 0;
                $delivery_history = UserRequests::where('user_id', Auth::user()->id)->where('receiver_mobile', '!=', '')->count();
                if($delivery_history > 0){
                    $new_delivery = 0;
                }else{
                    $new_delivery = 1;
                }
            return response()->json(['success' => TRUE, 'services'=> $serviceList, 'new_delivery' => $new_delivery], 200); 
    }


    //Send Request New Flow

    public function send_request(Request $request){
// dd(Carbon::now());
            $user = User::findOrFail(Auth::user()->id);
            $user->latitude = $request->s_latitude;
            $user->longitude = $request->s_longitude;
            $user->save();
            
            $TripDistance = calc_distance($request->s_latitude, $request->s_longitude, $request->d_latitude, $request->d_longitude, 'K');

            $ActiveRequests = UserRequests::PendingRequest(Auth::user()->id)->count(); 

            if($ActiveRequests > 0) {
                if($request->ajax()) {
                    return response()->json(['error'=>TRUE,'message' => trans('api.ride.request_inprogress')], 200);
                }else{
                    return redirect('dashboard')->with('flash_error', 'Already request is in progress. Try again later');
                }
            }

            if($request->has('schedule_date') && $request->has('schedule_time')){

                if(time() > strtotime($request->schedule_date.$request->schedule_time)){

                    if($request->ajax()) {
                        return response()->json(['error'=>TRUE,'message' => trans('api.ride.request_inprogress')], 200);
                    }else{
                        return redirect('dashboard')->with('flash_error', 'Unable to Create Request! Schedule time minimum 1 hour in advance');
                    }
                }

                $beforeschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->subHour(1);
                $afterschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->addHour(1);
                
                $CheckScheduling = UserRequests::where('status','SCHEDULED')
                                ->where('user_id', Auth::user()->id)
                                ->whereBetween('schedule_at',[$beforeschedule_time,$afterschedule_time])
                                ->get();

                if($CheckScheduling->count() > 0){
                    if($request->ajax()) {
                        return response()->json(['error'=>TRUE, 'message' => trans('api.ride.no_providers_found')], 200);
                    }else{
                        return redirect('dashboard')->with('flash_error', 'Already request is Scheduled on this time.');
                    }
                }

            }

            $service_type = ServiceType::findOrFail($request->service_type);

            $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');   

            $distance = Setting::get('provider_search_radius', '10');    

            if($request->payment_mode == 'CASH' || $service_type->drivercommission > 0){
                $minimum_balance = Setting::get('minimum_balance', '0');
            }else{
                $minimum_balance = 0;
            }
        
            $latitude = number_format($request->s_latitude, 7);
            $longitude = number_format($request->s_longitude,7);

            $location_updated = Setting::get('location_update_interval');
            if($service_type->is_delivery == 0){
                // if($request->has('fleet')){
                //     $fleet = $request->fleet;
                // }else{
                //     $fleet = $user->fleet;
                // }

                $fleet = 1;
                
                $Providers = Provider::whereIn('id', $ActiveProviders)
                            // ->whereDoesntHave('active_requests')
                            ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                            ->where('status', 'approved')
                            ->where('availability', '1')
                            ->where('archive', '0')
                            ->where('wallet_balance', '>=', $minimum_balance)
                            ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                            ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                            ->orderBy('distance', 'asc')->get();

                $offline_providers = Provider::whereIn('id', $ActiveProviders)
                                    ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                    ->where('status', 'approved')
                                    ->where('availability', '0')
                                    ->where('archive', '0')
                                    ->where('wallet_balance', '>=', $minimum_balance)
                                    ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                    ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                    ->orderBy('distance', 'asc')->take(3)->get();


                 if(count($Providers) == 0) {
                    $FailedRequest = new FailedRequest;
                    $FailedRequest->booking_id = Helper::generate_booking_id();
                    $FailedRequest->user_id = Auth::user()->id;
                    $FailedRequest->service_type_id = $request->service_type;
                    $FailedRequest->fleet_id = 1;
                    $FailedRequest->payment_mode = $request->payment_mode;

                    $FailedRequest->s_address = $request->s_address ? : "";
                    $FailedRequest->d_address = $request->d_address ? : "";

                    $FailedRequest->s_title = $request->s_title ? : "";
                    $FailedRequest->d_title = $request->d_title ? : "";

                    $FailedRequest->s_latitude = $request->s_latitude;
                    $FailedRequest->s_longitude = $request->s_longitude;

                    $FailedRequest->d_latitude = $request->d_latitude;
                    $FailedRequest->d_longitude = $request->d_longitude;
                    $FailedRequest->distance = number_format($TripDistance, 2);

                    $FailedRequest->total = $request->total ? : "";

                    $FailedRequest->use_wallet = $request->use_wallet ? : 0;
                    
                    $FailedRequest->assigned_at = Carbon::now();


                    if($request->has('schedule_date') && $request->has('schedule_time')){
                        $FailedRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
                    }

                    $FailedRequest->save();
                    // Push Notification to User
                    if($service_type->is_delivery == 1){
                        $service_flow = "Delivery";
                    }else{
                        $service_flow= "Ride";
                    }
                    (new SendPushNotification)->ProviderNotAvailable($user->id, $service_type->name, $service_flow);
                    return response()->json(['error'=>TRUE, 'message' => 'Sorry, no drivers available at this moment on '.$service_type->name.' Service. Please try our other service types'], 200); 
                 }else{
                     $driver = $Providers[0];
                 }
                
            }
            if($service_type->is_delivery != 0){
                if($request->has('fleet')){
                    $fleet = $request->fleet;
                }else{
                    $fleet = $user->fleet;
                }
                    $fleet_info = Fleet::where('id', $fleet)->first();
                    if($fleet_info->dispatch_method == 1){
                        $driver = Provider::where('fleet',$fleet)->where('fleet_driver',1)->first();


                        if(!$driver){
                                $distance = Setting::get('fleet_search_radius', '100');

                                $fleets = Fleet::where('fleets.id','!=',$fleet)
                                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) <= $distance")
                                                ->leftJoin('providers', 'fleets.id', 'providers.fleet')
                                                ->where('providers.status', 'approved')
                                                ->where('providers.availability', '1')
                                                ->where('providers.archive', '0')
                                                ->where('providers.wallet_balance', '>=', $minimum_balance)
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) AS distance"),'fleets.*')
                                                ->distinct('fleets.id')
                                                ->orderBy('distance')
                                                ->get();
                                    if($fleets){
                                        foreach ($fleets as $key => $fleet) {
                                            $request->request->add(['s_latitude' => $request->s_latitude,
                                                            's_longitude' => $request->s_longitude,
                                                            'd_latitude' => $request->d_latitude,
                                                            'd_longitude' => $request->d_longitude,
                                                            'service_type' => $request->service_type,
                                                            'fleet' => $fleet->id]);
                                            $fleets[$key]->estimated_fare = $this->estimated_fare($request);
                                        } 
                                        return response()->json(['success'=>FALSE, 'fleet' => $fleets, 'message' => 'No drivers available from your preferred service provider. Choose from the alternatives below to proceed.'], 200); 
                                    }else{
                                        return response()->json(['success'=>FALSE,  'message' => 'No drivers available from your preferred service provider.'], 200); 
                                    }
                        }
                        $offline_providers = Provider::where('fleet',$fleet)
                                            ->where('status', 'approved')
                                            ->where('availability', '0')
                                            ->where('archive', '0')
                                            // ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                            ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                            ->orderBy('distance', 'asc')
                                            ->take(15)->get();

                        $Providers = Provider::where('fleet',$fleet)
                                    ->whereIn('id', $ActiveProviders)
                                    // ->whereBetween('updated_at', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                    ->where('status', 'approved')
                                    ->where('availability', '1')
                                    ->where('archive', '0')
                                    ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                    ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                    ->orderBy('distance', 'asc')->take(3)->get();

                    }
                    else{
                            $distance = Setting::get('provider_search_radius', '10');  

                            $Providers = Provider::whereIn('id', $ActiveProviders)
                                        ->where('fleet',$fleet)
                                        ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                        ->where('status', 'approved')
                                        ->where('availability', '1')
                                        ->where('archive', '0')
                                        ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                        ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                        ->orderBy('distance', 'asc')->get();
                            if(count($Providers) == 0) {

                                $fleets = Fleet::where('fleets.id','!=',$fleet)
                                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) <= $distance")
                                                ->leftJoin('providers', 'fleets.id', 'providers.fleet')
                                                ->where('providers.status', 'approved')
                                                ->where('providers.availability', '1')
                                                ->where('providers.archive', '0')
                                                ->where('providers.wallet_balance', '>=', $minimum_balance)
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) AS distance"),'fleets.*')
                                                ->distinct('fleets.id')
                                                ->orderBy('distance')
                                                ->get();
                                if($fleets){
                                    foreach ($fleets as $key => $fleet) {
                                        $request->request->add(['s_latitude' => $request->s_latitude,
                                                        's_longitude' => $request->s_longitude,
                                                        'd_latitude' => $request->d_latitude,
                                                        'd_longitude' => $request->d_longitude,
                                                        'service_type' => $request->service_type,
                                                        'fleet' => $fleet->id]);
                                        $fleets[$key]->estimated_fare = $this->estimated_fare($request);
                                    } 
                                    return response()->json(['success'=>FALSE, 'fleet' => $fleets, 'message' => 'No drivers available from your preferred service provider. Choose from the alternatives below to proceed.'], 200); 
                                }else{
                                    return response()->json(['success'=>FALSE,  'message' => 'No drivers available from your preferred service provider.'], 200); 
                                }
                            }else{
                                    $driver = $Providers[0];
                            }

                            $offline_providers = Provider::where('fleet',$fleet)
                                                ->whereIn('id', $ActiveProviders)
                                                ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                                ->where('status', 'approved')
                                                ->where('availability', '0')
                                                ->where('archive', '0')
                                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                                ->orderBy('distance', 'asc')
                                                ->take(15)->get();

                    }
                    
                    
            }
        
                // List Providers who are currently busy and add them to the filter list.

                try{

                    $UserRequest = new UserRequests;
                    $UserRequest->booking_id = Helper::generate_booking_id();
                    $UserRequest->user_id = Auth::user()->id;
                    $UserRequest->current_provider_id = $driver->id;
                    $UserRequest->service_type_id = $request->service_type;
                    $UserRequest->fleet_id = $fleet;
                    $UserRequest->payment_mode = $request->payment_mode;

                    
                    $UserRequest->status = 'SEARCHING';

                    $UserRequest->s_address = $request->s_address ? : "";
                    $UserRequest->d_address = $request->d_address ? : "";

                    $UserRequest->s_title = $request->s_title ? : "";
                    $UserRequest->d_title = $request->d_title ? : "";

                    $UserRequest->s_latitude = $request->s_latitude;
                    $UserRequest->s_longitude = $request->s_longitude;

                    $UserRequest->d_latitude = $request->d_latitude;
                    $UserRequest->d_longitude = $request->d_longitude;
                    
                    $UserRequest->estimated_fare = $request->estimated_fare ? : "";
                    $UserRequest->distance = $request->distance ? : "";
                    $UserRequest->distance_price = $request->distance_price ? : "";
                    $UserRequest->time = $request->time ? : "";
                    $UserRequest->time_price = $request->time_price ? : "";
                    $UserRequest->tax_price = $request->tax_price ? : "";
                    $UserRequest->base_price = $request->base_price ? : "";
                    $UserRequest->wallet_balance = $request->wallet_balance ? : "";
                    $UserRequest->discount = $request->discount ? : "";
                    $UserRequest->total = $request->total ? : "";
                    $UserRequest->pickup_note = $request->pickup_note ? : "";

                    if($service_type->is_delivery == 1){
                        $UserRequest->receiver_name = $request->receiver_name;
                        $UserRequest->receiver_mobile = $request->receiver_mobile;
                        $UserRequest->pickup_instruction = $request->pickup_instruction;
                        $UserRequest->delivery_instruction = $request->delivery_instruction;
                        $UserRequest->package_type = $request->package_type;
                        $UserRequest->package_details = $request->package_details;
                    }

                    if($request->has('tempId')){
                        $upload = UploadImages::where('tempId', $request->tempId)->first();
                        $UserRequest->delivery_image = $upload->url;
                        $upload->delete();
                    }

                    if($request->has('pay_resp')){
                        $UserRequest->pay_resp = $request->pay_resp;
                    }

                    if($request->has('donation')){
                        $UserRequest->donation = $request->donation;
                    }

                    $UserRequest->use_wallet = $request->use_wallet ? : 0;
                    
                    $UserRequest->assigned_at = Carbon::now();


                    if($request->has('schedule_date') && $request->has('schedule_time')){
                        $UserRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
                    }

                    // update payment mode 

                    User::where('id',Auth::user()->id)->update(['payment_mode' => $request->payment_mode]);

                    // if($request->has('card_id')){

                    //     Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
                    //     Card::where('card_id',$request->card_id)->update(['is_default' => 1]);
                        
                    // }
                        // Send push notifications to the first provider
                    $fleet_details = Fleet::where('id', $fleet)->first();
                    if(!$driver){
                        $d_latitude = $fleet_details->latitude;
                        $d_longitude = $fleet_details->longitude;
                    }else{
                        $d_latitude = $driver->latitude;
                        $d_longitude = $driver->longitude;
                    }
                    $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$d_latitude.",".$d_longitude."&destinations=".$UserRequest->s_latitude.",".$UserRequest->s_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

                    $json = curl($details);

                    $details = json_decode($json, TRUE);
                    $meter = $details['rows'][0]['elements'][0]['distance']['value'];
                    $time = $details['rows'][0]['elements'][0]['duration']['text'];
                    $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

                    $kilometer = round(($meter/1000));
                    $minutes = round($seconds/60);
                            
                    $UserRequest->eta = $minutes;
                    $UserRequest->save();
                    $DriverReceived = new DriverRequestReceived;
                    $DriverReceived->user_id = $UserRequest->user_id;
                    $DriverReceived->provider_id = $driver->id;
                    $DriverReceived->latitude = $driver->latitude;
                    $DriverReceived->longitude = $driver->longitude;
                    $DriverReceived->request_id = $UserRequest->id;
                    $DriverReceived->service_id = $UserRequest->service_type_id;
                    $DriverReceived->distance = $kilometer;
                    $DriverReceived->duration = $minutes;
                    $DriverReceived->save();

                    if($offline_providers){
                       foreach($offline_providers as $offline_driver){
                            
                            $kilometer = $offline_driver->distance;
                            $minutes = '';

                            $OfflineRequestFilter = new OfflineRequestFilter;
                            $OfflineRequestFilter->user_id = $UserRequest->user_id;
                            $OfflineRequestFilter->provider_id = $offline_driver->id;
                            $OfflineRequestFilter->latitude = $offline_driver->latitude;
                            $OfflineRequestFilter->longitude = $offline_driver->longitude;
                            $OfflineRequestFilter->request_id = $UserRequest->id;
                            $OfflineRequestFilter->distance = $kilometer;
                            $OfflineRequestFilter->duration = $minutes;
                            $OfflineRequestFilter->save();
                        } 
                    }
                    
                    
                    session(['request_id' => $UserRequest->id]);

                    (new SendPushNotification)->IncomingRequest($driver->id);

                    foreach ($Providers as $key => $Provider) {

                        $Filter = new RequestFilter;
                        $Filter->request_id = $UserRequest->id;
                        $Filter->provider_id = $Provider->id; 
                        $Filter->save();
                    }

                    if($request->ajax()) {
                        return response()->json([
                                'success' => TRUE,
                                'message' => 'New request Created!',
                                'request_id' => $UserRequest->id,
                                'current_provider' => $UserRequest->current_provider_id,
                            ]);
                    }else{
                        return redirect('dashboard');
                    }

                } catch (Exception $e) {
                    Log::info($e);
                    if($request->ajax()) {
                        return response()->json(['success'=>FALSE, 'message' => trans('api.something_went_wrong')], 200);
                    }else{
                        return back()->with('flash_error', 'Something went wrong while sending request. Please try again.');
                    }
                }
    }


    public function send_request_delivery(Request $request){
// dd(Carbon::now());
            $user = User::findOrFail(Auth::user()->id);
            $user->latitude = $request->s_latitude;
            $user->longitude = $request->s_longitude;
            $user->save();
            
            $TripDistance = calc_distance($request->s_latitude, $request->s_longitude, $request->d_latitude, $request->d_longitude, 'K');

            $ActiveRequests = UserRequests::PendingRequest(Auth::user()->id)->count(); 

            if($ActiveRequests > 0) {
                if($request->ajax()) {
                    return response()->json(['error'=>TRUE,'message' => trans('api.ride.request_inprogress')], 200);
                }else{
                    return redirect('dashboard')->with('flash_error', 'Already request is in progress. Try again later');
                }
            }

            if($request->has('schedule_date') && $request->has('schedule_time')){

                if(time() > strtotime($request->schedule_date.$request->schedule_time)){

                    if($request->ajax()) {
                        return response()->json(['error'=>TRUE,'message' => trans('api.ride.request_inprogress')], 200);
                    }else{
                        return redirect('dashboard')->with('flash_error', 'Unable to Create Request! Schedule time minimum 1 hour in advance');
                    }
                }

                $beforeschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->subHour(1);
                $afterschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->addHour(1);
                
                $CheckScheduling = UserRequests::where('status','SCHEDULED')
                                ->where('user_id', Auth::user()->id)
                                ->whereBetween('schedule_at',[$beforeschedule_time,$afterschedule_time])
                                ->get();

                if($CheckScheduling->count() > 0){
                    if($request->ajax()) {
                        return response()->json(['error'=>TRUE, 'message' => trans('api.ride.no_providers_found')], 200);
                    }else{
                        return redirect('dashboard')->with('flash_error', 'Already request is Scheduled on this time.');
                    }
                }

            }

            $service_type = ServiceType::findOrFail($request->service_type);

            $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');   

            $distance = Setting::get('provider_search_radius', '10');    

            if($request->payment_mode == 'CASH' || $service_type->drivercommission > 0){
                $minimum_balance = Setting::get('minimum_balance', '0');
            }else{
                $minimum_balance = 0;
            }
        
            $latitude = number_format($request->s_latitude, 7);
            $longitude = number_format($request->s_longitude,7);

            $location_updated = Setting::get('location_update_interval');
            if($request->has('fleet')){
                $fleet = $request->fleet;
            }else{
                $fleet = $user->fleet;
            }
                    $fleet_info = Fleet::where('id', $fleet)->first();
                    if($fleet_info->dispatch_method == 1){

                        $driver = Provider::where('fleet',$fleet)->where('fleet_driver',1)->first();

                        if(!$driver){
                                $distance = Setting::get('fleet_search_radius', '100');

                                $fleets = Fleet::where('fleets.id','!=',$fleet)
                                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) <= $distance")
                                                ->leftJoin('providers', 'fleets.id', 'providers.fleet')
                                                ->where('providers.status', 'approved')
                                                ->where('providers.availability', '1')
                                                ->where('providers.archive', '0')
                                                ->where('providers.wallet_balance', '>=', $minimum_balance)
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) AS distance"),'fleets.*')
                                                ->distinct('fleets.id')
                                                ->orderBy('distance')
                                                ->get();
                                if($fleets){
                                    foreach ($fleets as $key => $fleet) {
                                        $request->request->add(['s_latitude' => $request->s_latitude,
                                                        's_longitude' => $request->s_longitude,
                                                        'd_latitude' => $request->d_latitude,
                                                        'd_longitude' => $request->d_longitude,
                                                        'service_type' => $request->service_type,
                                                        'fleet' => $fleet->id]);
                                        $fleets[$key]->estimated_fare = $this->estimated_fare($request);
                                    } 
                                    return response()->json(['success'=>FALSE, 'fleet' => $fleets, 'message' => 'No drivers available from your preferred service provider. Choose from the alternatives below to proceed.'], 200); 
                                }else{
                                    return response()->json(['success'=>FALSE,  'message' => 'No drivers available from your preferred service provider.'], 200); 
                                }      
                        }
                            $offline_providers = Provider::where('fleet',$fleet)
                                                ->where('status', 'approved')
                                                ->where('availability', '0')
                                                ->where('archive', '0')
                                                // ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                                ->orderBy('distance', 'asc')
                                                ->take(15)->get();
                            $Providers = Provider::where('fleet',$fleet)
                                        ->whereIn('id', $ActiveProviders)
                                        // ->whereBetween('updated_at', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                        ->where('status', 'approved')
                                        ->where('availability', '1')
                                        ->where('archive', '0')
                                        ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                        ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                        ->orderBy('distance', 'asc')->take(3)->get();

                    }else{
                            $distance = Setting::get('provider_search_radius', '10');  

                            $Providers = Provider::whereIn('id', $ActiveProviders)
                                        ->where('fleet',$fleet)
                                        ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                        ->where('status', 'approved')
                                        ->where('availability', '1')
                                        ->where('archive', '0')
                                        ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                        ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                        ->orderBy('distance', 'asc')->get();
                            if(count($Providers) == 0) {

                                $fleets = Fleet::where('fleets.id','!=',$fleet)
                                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) <= $distance")
                                                ->leftJoin('providers', 'fleets.id', 'providers.fleet')
                                                ->where('providers.status', 'approved')
                                                ->where('providers.availability', '1')
                                                ->where('providers.archive', '0')
                                                ->where('providers.wallet_balance', '>=', $minimum_balance)
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) AS distance"),'fleets.*')
                                                ->distinct('fleets.id')
                                                ->orderBy('distance')
                                                ->get();
                                    if($fleets){
                                        foreach ($fleets as $key => $fleet) {
                                            $request->request->add(['s_latitude' => $request->s_latitude,
                                                            's_longitude' => $request->s_longitude,
                                                            'd_latitude' => $request->d_latitude,
                                                            'd_longitude' => $request->d_longitude,
                                                            'service_type' => $request->service_type,
                                                            'fleet' => $fleet->id]);
                                            $fleets[$key]->estimated_fare = $this->estimated_fare($request);
                                        } 
                                        return response()->json(['success'=>FALSE, 'fleet' => $fleets, 'message' => 'No drivers available from your preferred service provider. Choose from the alternatives below to proceed.'], 200); 
                                    }else{
                                        return response()->json(['success'=>FALSE,  'message' => 'No drivers available from your preferred service provider.'], 200); 
                                    }
                            }else{
                                    $driver = $Providers[0];
                            }

                            $offline_providers = Provider::where('fleet',$fleet)
                                                ->whereIn('id', $ActiveProviders)
                                                ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                                ->where('status', 'approved')
                                                ->where('availability', '0')
                                                ->where('archive', '0')
                                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                                ->orderBy('distance', 'asc')
                                                ->take(15)->get();

                    }
                // List Providers who are currently busy and add them to the filter list.

                try{

                    $UserRequest = new UserRequests;
                    $UserRequest->booking_id = Helper::generate_booking_id();
                    $UserRequest->user_id = Auth::user()->id;
                    $UserRequest->current_provider_id = $driver->id;
                    $UserRequest->service_type_id = $request->service_type;
                    $UserRequest->fleet_id = $fleet;
                    $UserRequest->payment_mode = $request->payment_mode;

                    
                    $UserRequest->status = 'SEARCHING';

                    $UserRequest->s_address = $request->s_address ? : "";
                    $UserRequest->d_address = $request->d_address ? : "";

                    $UserRequest->s_title = $request->s_title ? : "";
                    $UserRequest->d_title = $request->d_title ? : "";

                    $UserRequest->s_latitude = $request->s_latitude;
                    $UserRequest->s_longitude = $request->s_longitude;

                    $UserRequest->d_latitude = $request->d_latitude;
                    $UserRequest->d_longitude = $request->d_longitude;
                    
                    $UserRequest->estimated_fare = $request->estimated_fare ? : "";
                    $UserRequest->distance = $request->distance ? : "";
                    $UserRequest->distance_price = $request->distance_price ? : "";
                    $UserRequest->time = $request->time ? : "";
                    $UserRequest->time_price = $request->time_price ? : "";
                    $UserRequest->tax_price = $request->tax_price ? : "";
                    $UserRequest->base_price = $request->base_price ? : "";
                    $UserRequest->wallet_balance = $request->wallet_balance ? : "";
                    $UserRequest->discount = $request->discount ? : "";
                    $UserRequest->total = $request->total ? : "";
                    $UserRequest->pickup_note = $request->pickup_note ? : "";

                    $UserRequest->receiver_name = $request->receiver_name;
                    $UserRequest->receiver_mobile = $request->receiver_mobile;
                    $UserRequest->pickup_instruction = $request->pickup_instruction;
                    $UserRequest->delivery_instruction = $request->delivery_instruction;
                    $UserRequest->package_type = $request->package_type;
                    $UserRequest->package_details = $request->package_details;

                    $UserRequest->pickup_add_flat = $request->pickup_add_flat ? : "";
                    $UserRequest->pickup_add_area = $request->pickup_add_area ? : "";
                    $UserRequest->pickup_add_landmark = $request->pickup_add_landmark ? : "";

                    $UserRequest->delivery_add_flat = $request->delivery_add_flat ? : "";
                    $UserRequest->delivery_add_area = $request->delivery_add_area ? : "";
                    $UserRequest->delivery_add_landmark = $request->delivery_add_landmark ? : "";

                    if($request->has('tempId')){
                        $upload = UploadImages::where('tempId', $request->tempId)->first();
                        $UserRequest->delivery_image = $upload->url;
                        $upload->delete();
                    }

                    $UserRequest->pickup_name = $request->pickup_name ? : "";
                    $UserRequest->pickup_mobile = $request->pickup_mobile ? : "";

                    if($request->has('pay_resp')){
                        $UserRequest->pay_resp = $request->pay_resp;
                    }

                    if($request->has('donation')){
                        $UserRequest->donation = $request->donation;
                    }

                    $UserRequest->use_wallet = $request->use_wallet ? : 0;
                    
                    $UserRequest->assigned_at = Carbon::now();


                    if($request->has('schedule_date') && $request->has('schedule_time')){
                        $UserRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
                    }

                    // update payment mode 

                    User::where('id',Auth::user()->id)->update(['payment_mode' => $request->payment_mode]);

                    // if($request->has('card_id')){

                    //     Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
                    //     Card::where('card_id',$request->card_id)->update(['is_default' => 1]);
                        
                    // }
                        // Send push notifications to the first provider
                    $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$driver->latitude.",".$driver->longitude."&destinations=".$UserRequest->s_latitude.",".$UserRequest->s_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

                    $json = curl($details);

                    $details = json_decode($json, TRUE);
                    $meter = $details['rows'][0]['elements'][0]['distance']['value'];
                    $time = $details['rows'][0]['elements'][0]['duration']['text'];
                    $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

                    $kilometer = round(($meter/1000));
                    $minutes = round($seconds/60);
                            
                    $UserRequest->eta = $minutes;
                    $UserRequest->save();
                    $DriverReceived = new DriverRequestReceived;
                    $DriverReceived->user_id = $UserRequest->user_id;
                    $DriverReceived->provider_id = $driver->id;
                    $DriverReceived->latitude = $driver->latitude;
                    $DriverReceived->longitude = $driver->longitude;
                    $DriverReceived->request_id = $UserRequest->id;
                    $DriverReceived->service_id = $UserRequest->service_type_id;
                    $DriverReceived->distance = $kilometer;
                    $DriverReceived->duration = $minutes;
                    $DriverReceived->save();

                    if($offline_providers){
                       foreach($offline_providers as $offline_driver){
                            
                            $kilometer = $offline_driver->distance;
                            $minutes = '';

                            $OfflineRequestFilter = new OfflineRequestFilter;
                            $OfflineRequestFilter->user_id = $UserRequest->user_id;
                            $OfflineRequestFilter->provider_id = $offline_driver->id;
                            $OfflineRequestFilter->latitude = $offline_driver->latitude;
                            $OfflineRequestFilter->longitude = $offline_driver->longitude;
                            $OfflineRequestFilter->request_id = $UserRequest->id;
                            $OfflineRequestFilter->distance = $kilometer;
                            $OfflineRequestFilter->duration = $minutes;
                            $OfflineRequestFilter->save();
                        } 
                    }
                    
                    
                    session(['request_id' => $UserRequest->id]);

                    (new SendPushNotification)->IncomingRequest($driver->id);

                    foreach ($Providers as $key => $Provider) {

                        $Filter = new RequestFilter;
                        $Filter->request_id = $UserRequest->id;
                        $Filter->provider_id = $Provider->id; 
                        $Filter->save();
                    }

                    if($request->ajax()) {
                        return response()->json([
                                'success' => TRUE,
                                'message' => 'New request Created!',
                                'request_id' => $UserRequest->id,
                                'current_provider' => $UserRequest->current_provider_id,
                            ]);
                    }else{
                        return redirect('dashboard');
                    }

                } catch (Exception $e) {
                    Log::info($e);
                    if($request->ajax()) {
                        return response()->json(['success'=>FALSE, 'message' => trans('api.something_went_wrong')], 200);
                    }else{
                        return back()->with('flash_error', 'Something went wrong while sending request. Please try again.');
                    }
                }
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function send_request_new(Request $request) {

        $user = User::findOrFail(Auth::user()->id);
        $user->latitude = $request->s_latitude;
        $user->longitude = $request->s_longitude;
        $user->save();
        
        //Log::info('New Request from user id :'. Auth::user()->id .' params are :');
        
        $TripDistance = calc_distance($request->s_latitude, $request->s_longitude, $request->d_latitude, $request->d_longitude, 'K');

        $ActiveRequests = UserRequests::PendingRequest(Auth::user()->id)->count();  
        if($ActiveRequests > 0) {
            if($request->ajax()) {
                return response()->json(['error'=>TRUE,'message' => trans('api.ride.request_inprogress')], 200);
            }else{
                return redirect('dashboard')->with('flash_error', 'Already request is in progress. Try again later');
            }
        }
        //Log::info(time().' - '.strtotime($request->schedule_date.$request->schedule_time));

        if($request->has('schedule_date') && $request->has('schedule_time')){

            if(time() > strtotime($request->schedule_date.$request->schedule_time)){
                if($request->ajax()) {
                    return response()->json(['error'=>TRUE,'message' => trans('api.ride.request_inprogress')], 200);
                }else{
                    return redirect('dashboard')->with('flash_error', 'Unable to Create Request! Schedule time minimum 1 hour in advance');
                }
            }

            $beforeschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->subHour(1);
            $afterschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->addHour(1);
            
            $CheckScheduling = UserRequests::where('status','SCHEDULED')
                            ->where('user_id', Auth::user()->id)
                            ->whereBetween('schedule_at',[$beforeschedule_time,$afterschedule_time])
                            ->get();

            if($CheckScheduling->count() > 0){
                if($request->ajax()) {
                    return response()->json(['error'=>TRUE, 'message' => trans('api.ride.no_providers_found')], 200);
                }else{
                    return redirect('dashboard')->with('flash_error', 'Already request is Scheduled on this time.');
                }
            }

        }
        $service_type = ServiceType::findOrFail($request->service_type);
        $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');        
        $distance = Setting::get('provider_search_radius', '10');    
        // $distance = 1000;
        if($request->payment_mode == 'CASH' || $service_type->drivercommission > 0){
            $minimum_balance = Setting::get('minimum_balance', '0');
        }else{
            $minimum_balance = 0;
        }
        
        $latitude = number_format($request->s_latitude, 7);
        $longitude = number_format($request->s_longitude,7);

        if($request->has('fleet')){
            if($service_type->is_delivery == 0){
                $fleet = 1;
            }else{
                $fleet = $request->fleet;
                $user->fleet = $request->fleet;
                $user->save();
            }
        }
        else{
            if($service_type->is_delivery == 0){
                $fleet = 1;
            }else{
                $fleet = $user->fleet;
            }
        }
            $location_updated = Setting::get('location_update_interval');

            $Providers = Provider::whereIn('id', $ActiveProviders)
                                ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                ->where('status', 'approved')
                                ->where('availability', '1')
                                ->where('archive', '0')
                                ->where('wallet_balance', '>=', $minimum_balance)
                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                ->orderBy('distance', 'asc');

            $offline_providers = Provider::whereIn('id', $ActiveProviders)
                                ->whereBetween('location_updated', [Carbon::now()->subMinutes($location_updated), Carbon::now()])
                                ->where('status', 'approved')
                                ->where('availability', '0')
                                ->where('archive', '0')
                                ->where('wallet_balance', '>=', $minimum_balance)
                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                ->orderBy('distance', 'asc')->take(3)->get();

        if($service_type->is_delivery == 0){
            $Providers = $Providers->get();
        }else{
            $Providers = $Providers->where('fleet', $fleet)->get();
            
        }
             
            

            if(count($Providers) == 0) {

                if($service_type->is_delivery != 0){
                    $distance = Setting::get('fleet_search_radius', '100');
                    $fleets = Fleet::where('fleets.id','!=',$fleet)
                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) <= $distance")
                                ->leftJoin('providers', 'fleets.id', 'providers.fleet')
                                ->where('providers.status', 'approved')
                                ->where('providers.availability', '1')
                                ->where('providers.archive', '0')
                                ->where('providers.wallet_balance', '>=', $minimum_balance)
                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) AS distance"),'fleets.*')
                        ->distinct('fleets.id')
                        ->orderBy('distance')
                        ->get();
                        foreach ($fleets as $key => $fleet) {
                           
                            $request->request->add(['s_latitude' => $request->s_latitude,
                                            's_longitude' => $request->s_longitude,
                                            'd_latitude' => $request->d_latitude,
                                            'd_longitude' => $request->d_longitude,
                                            'service_type' => $request->service_type,
                                            'fleet' => $fleet->id]);
                            $fleets[$key]->estimated_fare = $this->estimated_fare($request);
                        } 

                        if(count($fleets) >= 1){

                            $response = response()->json(['success'=>FALSE, 'fleet' => $fleets, 'message' => 'No drivers available from your preferred service provider. Choose from the alternatives below to proceed.'], 200);   
                        }
                        else{

                            $FailedRequest = new FailedRequest;
                            $FailedRequest->booking_id = Helper::generate_booking_id();
                            $FailedRequest->user_id = Auth::user()->id;
                            $FailedRequest->service_type_id = $request->service_type;
                            $FailedRequest->fleet_id = $fleet;
                            $FailedRequest->payment_mode = $request->payment_mode;

                            $FailedRequest->s_address = $request->s_address ? : "";
                            $FailedRequest->d_address = $request->d_address ? : "";

                            $FailedRequest->s_title = $request->s_title ? : "";
                            $FailedRequest->d_title = $request->d_title ? : "";

                            $FailedRequest->s_latitude = $request->s_latitude;
                            $FailedRequest->s_longitude = $request->s_longitude;

                            $FailedRequest->d_latitude = $request->d_latitude;
                            $FailedRequest->d_longitude = $request->d_longitude;
                            $FailedRequest->distance = number_format($TripDistance, 2);

                            $FailedRequest->total = $request->total ? : "";

                            if($service_type->is_delivery == 1){
                            $FailedRequest->receiver_name = $request->receiver_name;
                            $FailedRequest->receiver_mobile = $request->receiver_mobile;
                            $FailedRequest->pickup_instruction = $request->pickup_instruction;
                            $FailedRequest->delivery_instruction = $request->delivery_instruction;
                            $FailedRequest->package_type = $request->package_type;
                            $FailedRequest->package_details = $request->package_details;
                            }

                            $FailedRequest->use_wallet = $request->use_wallet ? : 0;
                            
                            $FailedRequest->assigned_at = Carbon::now();


                            if($request->has('schedule_date') && $request->has('schedule_time')){
                                $FailedRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
                            }

                            $FailedRequest->save();
                            if($service_type->is_delivery == 1){
                                $service_flow = "Delivery";
                            }else{
                                $service_flow= "Ride";
                            }
                            // Push Notification to User
                            (new SendPushNotification)->ProviderNotAvailable($user->id, $service_type->name, $service_flow);
                            $response = response()->json(['error'=>TRUE, 'message' => 'Sorry, no drivers available at this moment on '.$service_type->name.' Service. Please try our other service types'], 200); 
                        }
       
                    }else{

                        $FailedRequest = new FailedRequest;
                        $FailedRequest->booking_id = Helper::generate_booking_id();
                        $FailedRequest->user_id = Auth::user()->id;
                        $FailedRequest->service_type_id = $request->service_type;
                        $FailedRequest->fleet_id = $fleet;
                        $FailedRequest->payment_mode = $request->payment_mode;

                        $FailedRequest->s_address = $request->s_address ? : "";
                        $FailedRequest->d_address = $request->d_address ? : "";

                        $FailedRequest->s_title = $request->s_title ? : "";
                        $FailedRequest->d_title = $request->d_title ? : "";

                        $FailedRequest->s_latitude = $request->s_latitude;
                        $FailedRequest->s_longitude = $request->s_longitude;

                        $FailedRequest->d_latitude = $request->d_latitude;
                        $FailedRequest->d_longitude = $request->d_longitude;
                        $FailedRequest->distance = number_format($TripDistance, 2);

                        $FailedRequest->total = $request->total ? : "";

                        if($service_type->is_delivery == 1){
                        $FailedRequest->receiver_name = $request->receiver_name;
                        $FailedRequest->receiver_mobile = $request->receiver_mobile;
                        $FailedRequest->pickup_instruction = $request->pickup_instruction;
                        $FailedRequest->delivery_instruction = $request->delivery_instruction;
                        $FailedRequest->package_type = $request->package_type;
                        $FailedRequest->package_details = $request->package_details;
                        }

                        $FailedRequest->use_wallet = $request->use_wallet ? : 0;
                        
                        $FailedRequest->assigned_at = Carbon::now();


                        if($request->has('schedule_date') && $request->has('schedule_time')){
                            $FailedRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
                        }

                        $FailedRequest->save();
                        // Push Notification to User
                        if($service_type->is_delivery == 1){
                            $service_flow = "Delivery";
                        }else{
                            $service_flow= "Ride";
                        }
                        (new SendPushNotification)->ProviderNotAvailable($user->id, $service_type->name, $service_flow);
                        $response = response()->json(['error'=>TRUE, 'message' => 'Sorry, no drivers available at this moment on '.$service_type->name.' Service. Please try our other service types'], 200); 
                }
                
                if($request->ajax()) {
                        return $response;
                    
                }else{
                    return back()->with('flash_success', 'No Providers Found! Please try again.');
                }
        }
        
        // }
        // else{
        //     $distance = Setting::get('provider_search_radius', '10'); 
        //     if($service_type->is_delivery == 0){
        //         $fleet = 1;
        //     }else{
        //         $fleet = $user->fleet;
        //     }
        //     $Providers = Provider::whereIn('id', $ActiveProviders)
        //                     ->where('status', 'approved')
        //                     ->where('availability', '1')
        //                     ->where('archive', '0')
        //                     ->where('fleet', $fleet)
        //                     ->where('wallet_balance', '>=', $minimum_balance)
        //                     ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
        //                     ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
        //                     ->orderBy('distance', 'asc')
        //                     ->get(); 
        //     if(count($Providers) == 0) {

        //         $distance = Setting::get('fleet_search_radius', '100');
        //         $fleets = Fleet::where('fleets.id','!=',$fleet)
        //                     ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) <= $distance")
        //                     ->leftJoin('providers', 'fleets.id', 'providers.fleet')
        //                     ->where('providers.status', 'approved')
        //                     ->where('providers.availability', '1')
        //                     ->where('providers.archive', '0')
        //                     ->where('providers.wallet_balance', '>=', $minimum_balance)
        //                     ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(fleets.latitude) ) * cos( radians(fleets.longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(fleets.latitude) ) ) ) AS distance"),'fleets.*')
        //             ->distinct('fleets.id')
        //             ->orderBy('distance')
        //             ->get();
        //             foreach ($fleets as $key => $fleet) {
                       
        //                 $request->request->add(['s_latitude' => $request->s_latitude,
        //                                 's_longitude' => $request->s_longitude,
        //                                 'd_latitude' => $request->d_latitude,
        //                                 'd_longitude' => $request->d_longitude,
        //                                 'service_type' => $request->service_type,
        //                                 'fleet' => $fleet->id]);
        //                 $fleets[$key]->estimated_fare = $this->estimated_fare($request);
        //             }
                
        //     if($request->ajax()) {

        //         // Push Notification to User
        //         if(count($fleets) >= 1){

        //             return response()->json(['success'=>FALSE, 'fleet' => $fleets, 'message' => 'No drivers available from your preferred service provider. Choose from the alternatives below to proceed.'], 200);   
        //         }
        //         else{

        //             $FailedRequest = new FailedRequest;
        //             $FailedRequest->booking_id = Helper::generate_booking_id();
        //             $FailedRequest->user_id = Auth::user()->id;
        //             $FailedRequest->service_type_id = $request->service_type;
        //             $FailedRequest->fleet_id = $fleet;
        //             $FailedRequest->payment_mode = $request->payment_mode;

        //             $FailedRequest->s_address = $request->s_address ? : "";
        //             $FailedRequest->d_address = $request->d_address ? : "";

        //             $FailedRequest->s_title = $request->s_title ? : "";
        //             $FailedRequest->d_title = $request->d_title ? : "";

        //             $FailedRequest->s_latitude = $request->s_latitude;
        //             $FailedRequest->s_longitude = $request->s_longitude;

        //             $FailedRequest->d_latitude = $request->d_latitude;
        //             $FailedRequest->d_longitude = $request->d_longitude;
        //             $FailedRequest->distance = number_format($TripDistance, 2);
        //             $FailedRequest->total = $request->total ? : "";

        //             if($service_type->is_delivery == 1){
        //             $FailedRequest->receiver_name = $request->receiver_name;
        //             $FailedRequest->receiver_mobile = $request->receiver_mobile;
        //             $FailedRequest->pickup_instruction = $request->pickup_instruction;
        //             $FailedRequest->delivery_instruction = $request->delivery_instruction;
        //             $FailedRequest->package_type = $request->package_type;
        //             $FailedRequest->package_details = $request->package_details;
        //             }

        //             $FailedRequest->use_wallet = $request->use_wallet ? : 0;
                    
        //             $FailedRequest->assigned_at = Carbon::now();


        //             if($request->has('schedule_date') && $request->has('schedule_time')){
        //                 $FailedRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
        //             }

        //             $FailedRequest->save();

        //             (new SendPushNotification)->ProviderNotAvailable($user->id, $service_type->name);
        //             return response()->json(['error'=>TRUE, 'message' => 'Sorry, no drivers available at this moment on '.$service_type->name.' Service. Please try our other service types'], 200);
        //         }
        //     }else{
        //         return back()->with('flash_success', 'No Providers Found! Please try again.');
        //     }
        // }
        
        
        // }
        
        // List Providers who are currently busy and add them to the filter list.

        try{

            $UserRequest = new UserRequests;
            $UserRequest->booking_id = Helper::generate_booking_id();
            $UserRequest->user_id = Auth::user()->id;
            $UserRequest->current_provider_id = $Providers[0]->id;
            $UserRequest->service_type_id = $request->service_type;
            $UserRequest->fleet_id = $fleet;
            $UserRequest->payment_mode = $request->payment_mode;

            
            $UserRequest->status = 'SEARCHING';

            $UserRequest->s_address = $request->s_address ? : "";
            $UserRequest->d_address = $request->d_address ? : "";

            $UserRequest->s_title = $request->s_title ? : "";
            $UserRequest->d_title = $request->d_title ? : "";

            $UserRequest->s_latitude = $request->s_latitude;
            $UserRequest->s_longitude = $request->s_longitude;

            $UserRequest->d_latitude = $request->d_latitude;
            $UserRequest->d_longitude = $request->d_longitude;
            
            $UserRequest->estimated_fare = $request->estimated_fare ? : "";
            $UserRequest->distance = $request->distance ? : "";
            $UserRequest->distance_price = $request->distance_price ? : "";
            $UserRequest->time = $request->time ? : "";
            $UserRequest->time_price = $request->time_price ? : "";
            $UserRequest->tax_price = $request->tax_price ? : "";
            $UserRequest->base_price = $request->base_price ? : "";
            $UserRequest->wallet_balance = $request->wallet_balance ? : "";
            $UserRequest->discount = $request->discount ? : "";
            $UserRequest->total = $request->total ? : "";
            $UserRequest->pickup_note = $request->pickup_note ? : "";

            if($service_type->is_delivery == 1){
            $UserRequest->receiver_name = $request->receiver_name;
            $UserRequest->receiver_mobile = $request->receiver_mobile;
            $UserRequest->pickup_instruction = $request->pickup_instruction;
            $UserRequest->delivery_instruction = $request->delivery_instruction;
            $UserRequest->package_type = $request->package_type;
            $UserRequest->package_details = $request->package_details;
            }

            $UserRequest->use_wallet = $request->use_wallet ? : 0;
            
            $UserRequest->assigned_at = Carbon::now();


            if($request->has('schedule_date') && $request->has('schedule_time')){
                $UserRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
            }

           
            //Log::info('New Request id : '. $UserRequest->id .' Assigned to Driver : '. $UserRequest->current_provider_id);

            // incoming request push to provider
            // (new SendPushNotification)->IncomingRequest($UserRequest->current_provider_id);

            // update payment mode 

            User::where('id',Auth::user()->id)->update(['payment_mode' => $request->payment_mode]);

            // if($request->has('card_id')){

            //     Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
            //     Card::where('card_id',$request->card_id)->update(['is_default' => 1]);
                
            // }
                // Send push notifications to the first provider
            $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$Providers[0]->latitude.",".$Providers[0]->longitude."&destinations=".$UserRequest->s_latitude.",".$UserRequest->s_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

                    // $client = new Client(); //GuzzleHttp\Client
                    // $result = $client->get($details);

                    $json = curl($details);

                    $details = json_decode($json, TRUE);
                    $meter = $details['rows'][0]['elements'][0]['distance']['value'];
                    $time = $details['rows'][0]['elements'][0]['duration']['text'];
                    $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

                    $kilometer = round(($meter/1000));
                    $minutes = round($seconds/60);
            $UserRequest->eta = $minutes;
            $UserRequest->save();
            $DriverReceived = new DriverRequestReceived;
            $DriverReceived->user_id = $UserRequest->user_id;
            $DriverReceived->provider_id = $Providers[0]->id;
            $DriverReceived->latitude = $Providers[0]->latitude;
            $DriverReceived->longitude = $Providers[0]->longitude;
            $DriverReceived->request_id = $UserRequest->id;
            $DriverReceived->service_id = $UserRequest->service_type_id;
            $DriverReceived->distance = $kilometer;
            $DriverReceived->duration = $minutes;
            $DriverReceived->save();
            if($offline_providers){
               foreach($offline_providers as $offline_driver){

                //Calculate Duration and Distance
                $kilometer = $offline_driver->distance;
                $minutes = '';

                $OfflineRequestFilter = new OfflineRequestFilter;
                $OfflineRequestFilter->user_id = $UserRequest->user_id;
                $OfflineRequestFilter->provider_id = $offline_driver->id;
                $OfflineRequestFilter->latitude = $offline_driver->latitude;
                $OfflineRequestFilter->longitude = $offline_driver->longitude;
                $OfflineRequestFilter->request_id = $UserRequest->id;
                $OfflineRequestFilter->distance = $kilometer;
                $OfflineRequestFilter->duration = $minutes;
                $OfflineRequestFilter->save();
            } 
            }
            
            
            session(['request_id' => $UserRequest->id]);

            (new SendPushNotification)->IncomingRequest($Providers[0]->id);

            foreach ($Providers as $key => $Provider) {

                $Filter = new RequestFilter;
                $Filter->request_id = $UserRequest->id;
                $Filter->provider_id = $Provider->id; 
                $Filter->save();
            }

            if($request->ajax()) {
                return response()->json([
                        'success' => TRUE,
                        'message' => 'New request Created!',
                        'request_id' => $UserRequest->id,
                        'current_provider' => $UserRequest->current_provider_id,
                    ]);
            }else{
                return redirect('dashboard');
            }

        } catch (Exception $e) {
            Log::info($e);
            if($request->ajax()) {
                return response()->json(['success'=>FALSE, 'message' => trans('api.something_went_wrong')], 200);
            }else{
                return back()->with('flash_error', 'Something went wrong while sending request. Please try again.');
            }
        }
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function cancel_request(Request $request) {

        // $this->validate($request, [
        //         'request_id' => 'required|numeric|exists:user_requests,id,user_id,'.Auth::user()->id,
        //     ]);

        try{

            $UserRequest = UserRequests::findOrFail($request->request_id);

            if($UserRequest->status == 'CANCELLED')
            {
                if($request->ajax()) {
                    return response()->json(['success'=>TRUE, 'message' => trans('api.ride.already_cancelled')], 200); 
                }else{
                    return back()->with('flash_error', 'Request is Already Cancelled!');
                }
            }

            if(in_array($UserRequest->status, ['SEARCHING', 'ACCEPTED', 'ARRIVED', 'STARTED', 'CREATED','SCHEDULED'])) {

                $UserRequest->status = 'CANCELLED';
                $UserRequest->cancelled_reason = $request->reason;
                $UserRequest->cancelled_by = 'USER';
                $UserRequest->save();
                
                (new SendPushNotification)->UserCancellRide($UserRequest);

                RequestFilter::where('request_id', $UserRequest->id)->delete();

                if($UserRequest->status != 'SCHEDULED'){

                    if($UserRequest->provider_id != 0){

                        ProviderService::where('provider_id',$UserRequest->provider_id)->update(['status' => 'active']);

                    }
                }

                 // Send Push Notification to User
                

                if($request->ajax()) {
                    return response()->json(['success' => TRUE,'message' => 'Request has been Cancelled'], 200); 
                }else{
                    return redirect('dashboard')->with('flash_success','Request Cancelled Successfully');
                }

            } else {
                if($request->ajax()) {
                    return response()->json(['success'=>FALSE, 'message' => trans('api.ride.already_onride')], 200); 
                }else{
                    return back()->with('flash_error', 'Service Already Started!');
                }
            }
        }

        catch (ModelNotFoundException $e) {
            
            if($request->ajax()) {
                return response()->json(['success'=>FALSE, 'message' => trans('api.something_went_wrong')], 200);
            }else{
                return back()->with('flash_error', 'No Request Found!');
            }
        }

    }

    public function otp_activation(Request $request){

        // $this->validate($request, [
        //         'otp' => 'required',
        //     ]);
        
        $User = Auth::user();
        // if($request->otp)
        // {
            $User->otp_activation = 1;
            $User->save();
            //Log::info('Otp Activated User');
            return response()->json(['success' => TRUE, 'User'=> $User], 200);
        // }

        // else
        // {
        //     return response()->json(['success'=>FALSE,'message' => 'Wrong OTP']);
        // }

    }

    /**
     * Show the request status check.
     *
     * @return \Illuminate\Http\Response
     */

    public function request_status_check(Request $request) {

        try{

            $check_status = ['CANCELLED','SCHEDULED'];

            $UserRequests = UserRequests::UserRequestStatusCheck(Auth::user()->id,$check_status)
                                        ->get()
                                        ->toArray();


            $user_timeout = Setting::get('trip_search_time', 60);

            foreach ($UserRequests as $key => $value) { 
                if($request->has('seen')){
                    if($request->seen != 0){
                        UserRequests::where('id', $value['id'])->update(['reroute' => 0]);
                    }
                }
                $service_type = ServiceType::findOrFail($UserRequests[$key]['service_type_id']);
                        if($UserRequests[$key]['provider_profiles']['car_picture'] == ""){
                            $UserRequests[$key]['provider_profiles']['car_picture'] = $service_type->image;
                        }
                if($UserRequests[$key]['payment_mode'] == 'MOBILE'){
                    $UserRequests[$key]['payment_image'] = asset('asset/img/mobile.png');
                }
                if($UserRequests[$key]['payment_mode'] == 'CARD'){
                    $UserRequests[$key]['payment_image'] = asset('asset/img/card.png');
                }
                if($UserRequests[$key]['payment_mode'] == 'CASH'){
                    $UserRequests[$key]['payment_image'] = asset('asset/img/cash.png');
                }
                $change_destination = ChangeDestination::where('request_id',$value['id'])->where('driver_id', $UserRequests[$key]['current_provider_id'])->where('status', 0)->first();
                        if(count($change_destination) > 0){
                            $UserRequests[$key]['change'] = $change_destination;
                        }else{
                            $UserRequests[$key]['change'] = array();
                        }
                if($value['status'] == 'SEARCHING'){
                    $ExpiredTime = $user_timeout - (time() - strtotime($value['assigned_at']));                    
                    if($value['status'] == 'SEARCHING' && $ExpiredTime < 0) {
                        UserRequests::where('id', $value['id'])->update(['status' => 'CANCELLED']);

                        // No longer need request specific rows from RequestMeta
                        RequestFilter::where('request_id', $value['id'])->delete();
                        //Log::info('No Drivers found on time out period');
                        $service_type = ServiceType::findOrFail($value['service_type_id']);
                        //  request push to user provider not available
                        if($service_type->is_delivery == 1){
                            $service_flow = "Delivery";
                        }else{
                            $service_flow= "Ride";
                        }
                        (new SendPushNotification)->ProviderNotAvailable($value['user_id'], $service_type->name, $service_flow);
                    }
                }
                
                $provider = Provider::find($value['current_provider_id']);
                $subaccounts = array();
                if(!empty($provider)){
                    if($provider->fleet != '' && $provider->fleet != 0){
                        $fleet = Fleet::find($provider->fleet);
                        if($fleet->auto_payout == 1){
                            $fleetsubaccount = FleetSubaccount::where('fleet_id',$provider->fleet)->first();
                            if($fleetsubaccount){
                                $subaccounts[0]['subAccountId'] = $fleetsubaccount->subaccount_id;
                                $subaccounts[0]['commission']  = $value['payment']['commision'];
                                $subaccounts[0]['transactionType'] =   'flat';
                            }                        

                            if($fleet->driver_payout == 1){
                                $driver = DriverSubaccount::where('driver_id', $provider->id)->first();
                                if($driver){
                                    $subaccounts[1]['subAccountId'] = $driver->subaccount_id;
                                    $subaccounts[1]['commission']  = $value['payment']['drivercommision'];
                                    $subaccounts[1]['transactionType'] =   'flat';
                                }
                            } 
                            $UserRequests[$key]['subaccounts'] = $subaccounts;                          
                        }                                                
                    }
                }                            
            }

            $search_status = ['SEARCHING','SCHEDULED'];
            $UserRequestsFilter = UserRequests::UserRequestAssignProvider(Auth::user()->id,$search_status)->get(); 

            $Timeout = Setting::get('provider_select_timeout', 180);

            if(!empty($UserRequestsFilter)){
                for ($i=0; $i < sizeof($UserRequestsFilter); $i++) {
                    $ExpiredTime = $Timeout - (time() - strtotime($UserRequestsFilter[$i]->assigned_at));                    
                    if($UserRequestsFilter[$i]->status == 'SEARCHING' && $ExpiredTime < 0) {
                        $Providertrip = new TripController();
                        $Providertrip->assign_next_provider($UserRequestsFilter[$i]->id);
                    }else if($UserRequestsFilter[$i]->status == 'SEARCHING' && $ExpiredTime > 0){
                        break;
                    }
                }
            }

                $User = User::find(Auth::user()->id);
                if($request->device_token != "" || $request->device_token != null){
                    $User->device_token = $request->device_token;
                }

                if($request->device_type != "" || $request->device_type != null){
                    $User->device_type = $request->device_type;
                }

                if($request->device_id != "" || $request->device_id != null){
                    $User->device_id = $request->device_id;
                }
                $User->save();

            
            return response()->json(['success' => TRUE, 'data' => $UserRequests]);

        }

        catch (Exception $e) { 
            Log::info($e);
            return response()->json(['success'=>FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }

    } 

    public function driver_details(Request $request)
    {
// Log::info(json_encode($request->all()));
        $UserRequest = UserRequests::find($request->request_id);
        if($UserRequest){
            $Provider = Provider::find($UserRequest->provider_id);
            return response()->json(['success' => TRUE, 'eta' => $UserRequest->eta, 'driver' => $Provider]);
        }else{
            $UserRequest = UserRequests::where('user_id', Auth::user()->id)->orderBy('updated_at', 'desc')->first();
            if($UserRequest){
                $Provider = Provider::find($UserRequest->provider_id);
                // Log::info("Driver Details ETA: ".$UserRequest->eta);
                return response()->json(['success' => TRUE, 'eta' => $UserRequest->eta, 'driver' => $Provider]);
            }else{
                // Log::info("Driver Details ETA No Request ID: ");
                return response()->json(['success' => TRUE, 'eta' => 0, 'driver' => ""]);
            }
            
        }
        


        
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */


    public function rate_provider(Request $request) {

        $this->validate($request, [
                'request_id' => 'required|integer|exists:user_requests,id,user_id,'.Auth::user()->id,
                'rating' => 'required|integer|in:1,2,3,4,5',
                'comment' => 'max:255',
            ]);
    
        $UserRequests = UserRequests::where('id' ,$request->request_id)
                ->where('status' ,'COMPLETED')
                ->where('paid', 0)
                ->first();

        if ($UserRequests) {
            if($request->ajax()){
                return response()->json(['success'=>FALSE,'message' => trans('api.user.not_paid')], 200);
            } else {
                return back()->with('flash_error', 'Service Already Started!');
            }
        }

        try{

            $UserRequest = UserRequests::findOrFail($request->request_id);
            
            if($UserRequest->rating == null) {
                UserRequestRating::create([
                        'provider_id' => $UserRequest->provider_id,
                        'user_id' => $UserRequest->user_id,
                        'request_id' => $UserRequest->id,
                        'user_rating' => $request->rating,
                        'user_comment' => $request->comment,
                    ]);
            } else {
                $UserRequest->rating->update([
                        'user_rating' => $request->rating,
                        'user_comment' => $request->comment,
                    ]);
            }

            $UserRequest->user_rated = 1;
            $UserRequest->save();

            $base = UserRequestRating::where('provider_id', $UserRequest->provider_id);
            $average = $base->avg('user_rating');
            $average_count = $base->count();

            $UserRequest->provider->update(['rating' => $average, 'rating_count' => $average_count]);

            // Send Push Notification to Provider 

            (new SendPushNotification)->UserRated($UserRequest);
            if($request->ajax()){
                return response()->json(['success' => TRUE, 'message' => trans('api.ride.provider_rated')]); 
            }else{
                return redirect('dashboard')->with('flash_success', 'Driver Rated Successfully!');
            }
        } catch (Exception $e) {
            
            if($request->ajax()){
                return response()->json(['success'=>FALSE,'message' => trans('api.something_went_wrong')], 200);
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

    public function trips() {
    
        try{
            $UserRequests = UserRequests::UserTrips(Auth::user()->id)->get();
            if(!empty($UserRequests)){
                $map_icon = asset('asset/marker.png');
                foreach ($UserRequests as $key => $value) {
                    $UserRequests[$key]->static_map = "";
                    
                        
                        if($value->payment_mode == 'MOBILE'){
                            $UserRequests[$key]->payment_image = asset('asset/img/mobile.png');
                        }
                        if($value->payment_mode == 'CARD'){
                            $UserRequests[$key]->payment_image = asset('asset/img/card.png');
                        }
                        if($value->payment_mode == 'CASH'){
                            $UserRequests[$key]->payment_image = asset('asset/img/cash.png');
                        }
                }
            }
            return response()->json(['success' => TRUE, 'data'=> $UserRequests], 200);
        }

        catch (Exception $e) {
            Log::info($e);
            return response()->json(['success'=>FALSE,'message' => trans('api.something_went_wrong')]);
        }
    }

    public function Lasttrips() {
    
        try{
            $UserRequests = UserRequests::UserLastTrips(Auth::user()->id)->get();
            if(!empty($UserRequests)){
                $map_icon = asset('asset/marker.png');
                foreach ($UserRequests as $key => $value) {
                    $UserRequests[$key]->static_map = "";
                    $service_type = ServiceType::findOrFail($UserRequests[$key]->service_type_id);
                        if($UserRequests[$key]->provider_profiles->car_picture == ""){
                            $UserRequests[$key]->provider_profiles->car_picture = $service_type->image;
                        }
                        if($value->payment_mode == 'MOBILE'){
                            $UserRequests[$key]->payment_image = asset('asset/img/mobile.png');
                        }
                        if($value->payment_mode == 'CARD'){
                            $UserRequests[$key]->payment_image = asset('asset/img/card.png');
                        }
                        if($value->payment_mode == 'CASH'){
                            $UserRequests[$key]->payment_image = asset('asset/img/cash.png');
                        }
                }
            }
            return response()->json(['success' => TRUE, 'data'=> $UserRequests], 200);
        }

        catch (Exception $e) {
            
            return response()->json(['success'=>FALSE,'message' => trans('api.something_went_wrong')]);
        }
    }



    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function trip_details(Request $request) {

         $this->validate($request, [
                'request_id' => 'required|integer|exists:user_requests,id',
            ]);
    
        try{
            $UserRequests = UserRequests::UserTripDetails(Auth::user()->id,$request->request_id)->get();
            if(!empty($UserRequests)){
                $map_icon = asset('asset/marker.png');
                foreach ($UserRequests as $key => $value) {
                    $service_type = ServiceType::findOrFail($UserRequests[$key]->service_type_id);
                    $UserRequests[$key]->static_map = "";
                        if($UserRequests[$key]->provider_profiles->car_picture == ""){
                            $UserRequests[$key]->provider_profiles->car_picture = $service_type->image;
                        }
                        if($value->payment_mode == 'MOBILE'){
                            $UserRequests[$key]->payment_image = asset('asset/img/mobile.png');
                        }
                        if($value->payment_mode == 'CARD'){
                            $UserRequests[$key]->payment_image = asset('asset/img/card.png');
                        }
                        if($value->payment_mode == 'CASH'){
                            $UserRequests[$key]->payment_image = asset('asset/img/cash.png');
                        }
                }
            }
            return response()->json(['success' => TRUE, 'data'=> $UserRequests], 200);

        }

        catch (Exception $e) {
            
            return response()->json(['success'=>FALSE,'message' => trans('api.something_went_wrong')]);
        }
    }

    /**
     * get all promo code.
     *
     * @return \Illuminate\Http\Response
     */

    public function promocodes() {

        try{

            $this->check_expiry();

            $active_promocodes = Promocode::where('status', '!=', 'EXPIRED')->orderBy('created_at' , 'desc')->get();

            $used_promocodes = PromocodeUsage::Active()->where('user_id',Auth::user()->id)
                                ->with('promocode')
                                ->get()->toArray();

            return response()->json(['success' => TRUE, 'available_promo'=> $active_promocodes, 'used_promo' => $used_promocodes], 200);

        }

        catch (Exception $e) {
            
            return response()->json(['success'=>FALSE,'message' => trans('api.something_went_wrong')], 200);
        }

    } 



    public function check_expiry(){

        try{

            $Promocode = Promocode::all();

            foreach ($Promocode as $index => $promo) {
                $total_used = promo_used_count($promo->id);
                if(date("Y-m-d") >= $promo->expiration || $total_used >= $promo->count_max){
                    $promo->status = 'EXPIRED';
                    $promo->save();
                    PromocodeUsage::where('promocode_id',$promo->id)->update(['status' => 'EXPIRED']);
                }

            }

        }    
        catch (Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }  
    }


    /**
     * add promo code.
     *
     * @return \Illuminate\Http\Response
     */

    public function add_promocode(Request $request) {


        try{
            $input_promo = strtoupper($request->promocode);
            $find_promo = Promocode::where('promo_code',$input_promo)->first();
            if($find_promo){
                $usage = PromocodeUsage::where('promocode_id',$find_promo->id)->where('user_id', Auth::user()->id)->whereIn('status', ['ADDED','USED'])->count();
                if($find_promo->status == 'EXPIRED' || (date("Y-m-d") > $find_promo->expiration) || $usage >= $find_promo->count_max ){

                    if($request->ajax()){

                        return response()->json([
                            'success' => FALSE,
                            'message' => trans('api.promocode_expired'), 
                            'code' => 'promocode_expired'
                        ],200);

                    }else{
                        return back()->with('flash_error', trans('api.promocode_expired'));
                    }

                }else if(PromocodeUsage::where('promocode_id',$find_promo->id)->where('user_id', Auth::user()->id)->where('status', 'USED')->count() > 0){

                    if($request->ajax()){

                        return response()->json(['success' => FALSE, 'message' => 'Promocode already used'], 200);

                    }else{
                        return back()->with('flash_error', 'Promocode Already in use');
                    }

                }else if(PromocodeUsage::where('promocode_id',$find_promo->id)->where('user_id', Auth::user()->id)->where('status', 'ADDED')->count() > 0){

                    if($request->ajax()){

                        return response()->json(['success' => FALSE, 'message' => 'You have an active promocode not used'], 200);

                    }else{
                        return back()->with('flash_error', 'Promocode Already in use');
                    }
                }else{

                    $promo = new PromocodeUsage;
                    $promo->promocode_id = $find_promo->id;
                    $promo->user_id = Auth::user()->id;
                    $promo->status = "ADDED";
                    $promo->save();

                    if($request->ajax()){
                        return response()->json(['success' => TRUE, 'message' => 'Promocode applied successfully', 'discount' => $find_promo->discount], 200);

                    }else{
                        return back()->with('flash_success', trans('api.promocode_applied'));
                    }

                }

            }else
            {       
                $User = User::find(Auth::user()->id);
            
                if($input_promo == $User->referal  || $User->referral_used ==1){
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
                            $bonus = Setting::get('user_to_user_referral', 0);
                            $User->user_referred = $input_promo;
                            $User->wallet_balance += $bonus;
                            $User->referral_used = 1;
                            $User->save();
                        }else if($driver_referal){ 

                            if($driver_referal->ambassador == 1){
                                $bonus = Setting::get('driver_to_user_referral', 0);
                            }else{
                                $bonus = Setting::get('ambassadors_to_user_referral', 0);
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
                            $bonus = Setting::get('marketer_to_user_referral', '10');
                            $User->wallet_balance += $bonus;
                            $User->referral_used = 1;
                            $User->save(); 
                        }
                        if($bonus > 0){
                            $code = rand(1000, 9999);
                            $name = substr($User->first_name, 0, 2);
                            $reference = "PWT".$code.$name;

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
        }
        catch (Exception $e) {
            Log::info($e);
            if($request->ajax()){
                return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
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

    public function upcoming_trips() {
    
        try{
            $UserRequests = UserRequests::UserUpcomingTrips(Auth::user()->id)->get();
            
            return response()->json(['success' => TRUE, 'data'=> $UserRequests], 200);
        }

        catch (Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')]);
        }
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function upcoming_trip_details(Request $request) {

         $this->validate($request, [
                'request_id' => 'required|integer|exists:user_requests,id',
            ]);
    
        try{
            $UserRequests = UserRequests::UserUpcomingTripDetails(Auth::user()->id,$request->request_id)->get();
            
            return response()->json(['success' => TRUE, 'data'=> $UserRequests], 200);        }

        catch (Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')]);
        }
    }


        /**
     * Show the nearby providers.
     *
     * @return \Illuminate\Http\Response
     */

    public function show_providers(Request $request) {

        $this->validate($request, [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
                // 'service' => 'required|numeric|exists:service_types,id',
            ]);
        //Log::info($request->all());
        try{

            // $ActiveProviders = ProviderService::AvailableServiceProvider($request->service)->get()->pluck('provider_id');

            $distance = Setting::get('provider_search_radius', '10');
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $minimum_balance = Setting::get('minimum_balance', '0');

            $Providers = Provider::where('status', 'approved')
                                ->where('availability', '1')
                                ->where('archive', '0')
                                ->where('wallet_balance', '>=', $minimum_balance)
                        // ->whereIn('id', $ActiveProviders)
                         // ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                                ->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"), 'providers.*')
                                ->orderBy('distance', 'asc')
                                ->get()->take(6);

            if(count($Providers) == 0) {
                if($request->ajax()) {
                    return response()->json(['success' => FALSE, 'message' => "No Drivers Found"]); 
                }else{
                    return back()->with('flash_success', 'No Drivers Found! Please try again.');
                }
            }
        //Log::info($Providers);
            return response()->json(['success' => TRUE, 'data'=> $Providers], 200);
        } catch (Exception $e) {
            
            if($request->ajax()) {
                return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
            }else{
                return back()->with('flash_error', 'Something went wrong while sending request. Please try again.');
            }
        }
    }



    /**
     * Show the provider.
     *
     * @return \Illuminate\Http\Response
     */

    public function provider(Request $request) {

        $this->validate($request, [
                'provider_id' => 'required|numeric|exists:providers,id',
            ]);

        if($Provider = Provider::find($request->provider_id)) {

            if($Services = ServiceType::all()) {
                foreach ($Services as $key => $value) {
                    $price = ProviderService::where('provider_id',$request->provider_id)
                            ->where('service_type_id',$value->id)
                            ->first();
                    if($price){
                        $Services[$key]->available = true;
                    }else{
                        $Services[$key]->available = false;
                    }
                }
            } 


            return response()->json([
                    'provider' => $Provider, 
                    'services' => $Services,
                ]);

        } else {
            return response()->json(['success' => FALSE, 'message' => 'No Driver Found!'], 200);
        }

    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function logout(Request $request)
    {
        try {
            User::where('id', $request->id)->update(['device_id'=> '', 'device_token' => '']);
            return response()->json(['success' => FALSE, 'message' => trans('api.logout_success')]);
        } catch (Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    
    /**
     * help Details.
     *
     * @return \Illuminate\Http\Response
     */

    public function help_details(Request $request){

        try{

            if($request->ajax()) {
                return response()->json([
                        'contact_number' => Setting::get('contact_number',''), 
                        'contact_email' => Setting::get('contact_email',''),
                        'contact_text' => Setting::get('contact_text',''),
                        'contact_title' => Setting::get('site_title',''),
                     ]);
            }

        }catch (Exception $e) {
            
            if($request->ajax()) {
                return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')]);
            }
        }
    }

        public function estimated_fare_new(Request $request){
        $this->validate($request,[
                's_latitude' => 'required|numeric',
                's_longitude' => 'required|numeric',
                'd_latitude' => 'required|numeric',
                'd_longitude' => 'required|numeric',
                'service_type' => 'required|numeric|exists:service_types,id',
            ]);

        try{ 

           $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$request->d_latitude.",".$request->d_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

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
            
                $service_type = ServiceType::findOrFail($request->service_type);

            $price_base = $service_type->fixed;

            $price = ($kilometer * $service_type->price) + ($service_type->time * $minutes);

            $time_price = $service_type->time* $minutes;
            $distance_price = $kilometer * $service_type->price;
            

            $price += ( $commission_percentage/100 ) * $price;
            $tax_price = ( $tax_percentage/100 ) * $price;
            if($kilometer  <= $service_type->base_radius){
                $total = $price_base;
            }else{
                $total = $price + $tax_price + $price_base;
            }
            if(Setting::get('surge_percentage') != 0){
                    $total = $total * Setting::get('surge_percentage');
                }
            

            $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');

            $distance = Setting::get('provider_search_radius', '10');
            $latitude = $request->s_latitude;
            $longitude = $request->s_longitude;

            $Providers = Provider::whereIn('id', $ActiveProviders)
                ->where('status', 'approved')
                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                ->get();

            // if($Providers->count() <= Setting::get('surge_trigger') && $Providers->count() > 0){
            //     $surge_price = (Setting::get('surge_percentage')/100) * $total;
            //     $total += $surge_price;
            // }
                


            return response()->json([
                    'success' => TRUE,
                    'estimated_fare' => number_format($total,2), 
                    'distance' => $kilometer,
                    'distance_price' => number_format($distance_price,2),
                    'time' => $time,
                    'time_price' => number_format($time_price,2),
                    'tax_price' => number_format($tax_price,2),
                    'base_price' => number_format($service_type->fixed,2),
                    'wallet_balance' => number_format(Auth::user()->wallet_balance,2)
                ]);

        } catch(Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function estimated_fare(Request $request){

        $this->validate($request,[
                's_latitude' => 'required|numeric',
                's_longitude' => 'required|numeric',
                'd_latitude' => 'required|numeric',
                'd_longitude' => 'required|numeric',
                'service_type' => 'required|numeric|exists:service_types,id',
            ]);

        try{


           $details = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$request->d_latitude.",".$request->d_longitude."&mode=driving&sensor=false&key=".env("GOOGLE_MAP_KEY");

           //Log::info(env("GOOGLE_MAP_KEY"));

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
            $minimum_balance = Setting::get('minimum_balance', '0');
            
            if(Auth::user()->fleet ==1){
                    $service_type = ServiceType::findOrFail($request->service_type);
            }else{
                $service_type = FleetPrice::where('service_id', $request->service_type)->where('fleet_id', Auth::user()->fleet)->first();
                if(!$service_type){
                    $service_type = ServiceType::findOrFail($request->service_type);
                }
            }

            $price_base = $service_type->fixed;
            $kilometer = $kilometer - $service_type->base_radius;
            $time_price = $service_type->time * $minutes;
            $distance_price = $kilometer * $service_type->price;
           
            // if($service_type->base_radius != 0 && $kilometer  <= $service_type->base_radius){
            //     $total = $price_base;
            // }else{
            //     $kilometer = $kilometer - $service_type->base_radius;
            //     $price = ($kilometer * $service_type->price) + ($service_type->time * $minutes);
            //     $total = $price + $price_base;
            // }

            if(Setting::get('surge_percentage') != 0){
                $price_base = $price_base * Setting::get('surge_percentage');
                $time_price = $time_price * Setting::get('surge_percentage');
                $distance_price = $distance_price * Setting::get('surge_percentage');
            }
            
            
            $total = $price_base + $time_price + $distance_price;
            $tax_price = ( $tax_percentage/100 ) * $total;
            $total = $total + $tax_price;
            
            if($total <= $service_type->minimum_fare){
                $total = $service_type->minimum_fare;
            }

            $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');

            $distance = Setting::get('provider_search_radius', '10');
            $latitude = $request->s_latitude;
            $longitude = $request->s_longitude;

            $Providers = Provider::whereIn('id', $ActiveProviders)
                ->where('status', 'approved')
                ->where('availability', '1')
                ->where('archive', '0')
                ->where('fleet', $request->fleet)
                ->where('wallet_balance', '>=', $minimum_balance)
                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                ->get();

            // if($Providers->count() <= Setting::get('surge_trigger') && $Providers->count() > 0){
            //     $surge_price = (Setting::get('surge_percentage')/100) * $total;
            //     $total += $surge_price;
            // }
            $Discount = $promo_discount = 0; // Promo Code discounts should be added here.
            if($total > $service_type->minimum_fare){
                if($PromocodeUsage = PromocodeUsage::where('user_id',Auth::user()->id)->where('status','ADDED')->first()){
                    if($Promocode = Promocode::find($PromocodeUsage->promocode_id)){
                        if($Promocode->type == "percent"){
                            $promo_discount = $total * ( $Promocode->discount / 100);
                        }else if($Promocode->type == "flat"){
                            $promo_discount = $Promocode->discount;
                        }
                    }
                }
            }
            
            $Discount += $promo_discount;
            if($total > $Discount && $total > 0){
                $total = $total - $Discount;
            }else{
                $total = 0;
            }
            
            $total = round($total);

            if($request->has('s_address')){
                    $estimate['estimated_fare'] = number_format($total,2); 
                    $estimate['distance'] = $kilometer;
                    // $estimate['distance_price'] = number_format($distance_price,2);
                    $estimate['time'] = $time;
                    // $estimate['time_price'] = number_format($time_price,2);
                    // $estimate['tax_price'] = number_format($tax_price,2);
                    // $estimate['base_price'] = number_format($service_type->fixed,2);
                    // $estimate['wallet_balance'] = number_format(Auth::user()->wallet_balance,2);
                    // $estimate['discount'] = number_format($Discount,2);
                    // $estimate = number_format($total,2);
                    return $estimate;
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
                    'wallet_balance' => number_format(Auth::user()->wallet_balance,2),
                    'discount' => number_format($Discount,2)
                ],200);

        } catch(Exception $e) {
            Log::info($e);
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
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

                            $update_user = User::find(Auth::user()->id);
                            $update_user->wallet_balance += $request->amount;
                            $update_user->save();

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->user_id = Auth::user()->id;
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
                            $rave_transactions->status = 1;
                            $rave_transactions->save();
                            
                            $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            //sending push on adding wallet money
                            $response_array =  array('success' => TRUE , 'message' => 'Topup successful', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }else{

                            $update_user = User::find(Auth::user()->id);

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->user_id = Auth::user()->id;
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

                            $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            $response_array =  array('success' => FALSE , 'message' => 'Topup failed. Please try later', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }
                    }else{
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

                            $update_user = User::find(Auth::user()->id);
                            $update_user->wallet_balance += $request->amount;
                            $update_user->save();

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->user_id = Auth::user()->id;
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
                            $rave_transactions->status = 1;
                            $rave_transactions->save();
                            
                            $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            //sending push on adding wallet money
                            $response_array =  array('success' => TRUE , 'message' => 'Topup Successful', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }else{

                            $update_user = User::find(Auth::user()->id);

                            $code = rand(1000, 9999);
                            $name = substr($update_user->first_name, 0, 2);
                            $reference = "AWD".$code.$name;

                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->user_id = Auth::user()->id;
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
                            $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                            $response_array =  array('success' => FALSE , 'message' => 'Topup failed. Please try later', 'wallet_balance' => number_format($update_user->wallet_balance,2), $transactions);
                        }
                    }


            

        } catch(Exception $e) { 
            Log:info($e);
            $response_array = array('success' => 'false' , 'error' => 'Something Went Wrong!');
        }
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function add_money_sp(Request $request){

        $this->validate($request, [
                'amount' => 'required'
            ]);

        try{
             $User = User::find(Auth::user()->id);
                    
                    $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

                //sending push on adding wallet money
                // $response_array =  array('success' => TRUE , 'message' => 'Please check your phone for prompt to complete payment', 'wallet_balance' => number_format($User->wallet_balance,2), $transactions);
            
            $response_array =  array('success' => TRUE , 'message' => 'Topup wallet temporarily not available', 'wallet_balance' => number_format($User->wallet_balance,2), $transactions);
            

        } catch(Exception $e) { 
            Log::info($e);
            $response_array = array('success' => 'false' , 'error' => 'Something Went Wrong!');
        }
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function wallet_balance(Request $request){
        $user = User::find(Auth::user()->id);

        $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

        $response_array = array(
            'success' => true,
            'wallet_balance' => number_format($user->wallet_balance,2),
            'transactions' => $transactions,
        );
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function wallet_balance_sp(Request $request)
    {
        
        $user = User::find(Auth::user()->id);

        $code = rand(1000, 9999);
        $name = substr($user->first_name, 0, 2);
        $reference = "AWT".$code.$name;
        
        $credit_pending_transactions = RaveTransaction::where('user_id', Auth::user()->id)->where('status', 2)->where('type', 'credit')->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                $payToken = $credit_pending_transaction->rave_ref_id;
                
                try{
                    $client1 = new \GuzzleHttp\Client();
                    $status_url = "https://posapi.usebillbox.com/webpos/checkPaymentStatus";
                    $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];
                    
                    $status = $client1->post($status_url, [ 
                        'headers' => $headers,
                        'json' => ["requestId" => $reference,
                                    "appReference" => "replace with actual reference",
                                    "secret" => "replace with actual password",
                                    "transactionId" => $payToken]]);

                    $result = array();
                    $result = json_decode($status->getBody(),'true');
                    
                        Log::info("Driver Wallet balance status: ". $payToken." - ". $result['result']['status']);
                        if($result['success'] == TRUE && $result['result']['status'] == "CONFIRMED"){

                            $credit_pending_transaction->last_balance = $user->wallet_balance;
                            $user = User::find(Auth::user()->id);
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
        $user = User::find(Auth::user()->id);

        $transactions = RaveTransaction::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

        $response_array = array(
            'success' => true,
            'wallet_balance' => number_format($user->wallet_balance,2),
            'transactions' => $transactions,
        );
        $response = response()->json($response_array, 200);
        return $response;
    }

    public function referals()
    {
        $user = User::find(Auth::user()->id);
        $user_referral = User::where('user_referred', $user->referal)->count();
        $driver_referral = Provider::where('user_referred', $user->referal)->count();

        $response_array = array(
            'success' => true,
            'user_referals'=> $user_referral,
            'driver_referals' => $driver_referral,
        );
        $response = response()->json($response_array, 200);
        return $response;
    }


        public function locations(Request $request) {
        
        $locations = UserLocation::where('user_id' , Auth::user()->id)
                    ->select('user_locations.id as location_id','user_locations.latitude','user_locations.longitude' ,'user_locations.title',
                        'user_locations.address', 'user_locations.is_default', 'user_locations.type')
                    ->get()
                    ->toArray();

        $recent_source = UserRequests::where('user_id', Auth::user()->id)->select('user_requests.s_latitude','user_requests.s_longitude' ,'user_requests.s_title', 'user_requests.s_address')->orderBy('user_requests.created_at', 'desc')->get()->take(10)->toArray();

        $recent_destination = UserRequests::where('user_id', Auth::user()->id)->select('user_requests.d_latitude','user_requests.d_longitude' ,'user_requests.d_title', 'user_requests.d_address')->orderBy('user_requests.created_at', 'desc')->get()->take(10)->toArray();

        $response_array = array('success' => true , 'locations' => $locations, 'recent_source' => $recent_source, 'recent_destination' => $recent_destination);

        return response()->json($response_array , 200);
    }

    public function add_location(Request $request) {

        $this->validate($request, [
            'latitude' => 'required',
            'longitude' => 'required',
            'address' => 'required|max:255',
            'title' => 'required|max:255'
            ]);


            $location = new UserLocation;
            $location->user_id = Auth::user()->id;
            $location->title = $request->title;
            $location->type = $request->type;
            $location->latitude = $request->latitude;
            $location->longitude = $request->longitude;
            $location->address = $request->address;

            if(!$check_location_default = UserLocation::where('user_id' , Auth::user()->id)->count()){
                $location->is_default = 1;
            }
            $location->save();

            $response_array = array('success' => true);

        

        return response()->json($response_array, 200);

    }

    public function edit_location(Request $request) {

       // $this->validate($request, [
       //      'location_id' => 'required|integer|exists:user_locations,id,user_id,'.Auth::user()->id,
       //      'latitude' => '',
       //      'longitude' => '',
       //      'address' => 'max:255',
       //      ]);


            $location = UserLocation::find($request->location_id);

            $location->title = $request->has('title') ? $request->title : $location->title;

            $location->latitude = $request->has('latitude') ? $request->latitude : $location->latitude;

            $location->longitude = $request->has('longitude') ? $request->longitude : $location->longitude;

            $location->address = $request->has('address') ? $request->address : $location->address;

            $location->save();

            $response_array = array('success' => true);

    

        return response()->json($response_array,200);
    
    }

    public function delete_location(Request $request) {

        $this->validate($request, [
            'location_id' => 'required|integer|exists:user_locations,id,user_id,'.Auth::user()->id,
            ]);

        

            UserLocation::find($request->location_id)->delete();

            $response_array = array('success' => true);


        return response()->json($response_array,200);

    }

    public function default_location(Request $request) {

        $this->validate($request, [
            'location_id' => 'required|integer|exists:user_locations,id,user_id,'.Auth::user()->id,
            ]);

        

            $location = UserLocation::find($request->location_id);


            if($check_location_default = UserLocation::where('user_id' , Auth::user()->id)->where('is_default' , 1)->first()) {
                $check_location_default->is_default = 0;
                $check_location_default->save();
            }

            $location->is_default = 1;
            $location->save();

            $response_array = array('success' => true);


        return response()->json($response_array,200);

    }

    public function social_login(Request $request){
        $this->validate($request, [
                'social_unique_id' => 'required',
            ]);
        try{
            $User = User::where('social_unique_id',$request->social_unique_id)
                        // ->where('user_id', Auth::user()->id)
                        ->first();
            if($User){
                $exist = $User->email;
                $country_code = $User->country_code;
                $success = 'true';
                return response()->json(['success'=>$success, 'username' => $exist, 'password' => 't@mi2h' ,'country_code' => $country_code]);
            }
            else{
                $exist = 0;
                $success = 'false';
                $country_code = 0;
                return response()->json(['success' => FALSE, 'message'=> 'You\'re not registered with us. Please do Register'], 200);
            }
            
        }catch (Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function chat_histroy(Request $request)
    {
        $this->validate($request, [
                'request_id' => 'required|integer'
            ]);
        try{
        $Chat = array();
            $Chat['data'] = Chat::where('request_id',$request->request_id)
                        // ->where('user_id', Auth::user()->id)
                        ->get();
            return response()->json($Chat);
        }catch (Exception $e) {
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    public function contact_submission(Request $request)
    {

        $this->validate($request, [
                'name' => 'max:255',
                'business_name' => 'max:255',
                'email' => 'email',
                'phone' => 'between:6,13',
                'message' => 'max:255',
            ]);

         try {

            $enquiry = new Enquiry;

            if($request->has('name')){ 
                $enquiry->name = $request->name;
            }
            
            if($request->has('business_name')){
                $enquiry->business_name = $request->business_name;
            }

            if($request->has('phone')){
                $enquiry->phone = $request->phone;
            }

            if($request->has('message')){
                $enquiry->message = $request->message;
            }
            
            if($request->has('email')){
                $enquiry->email = $request->email;
            }



            $enquiry->save();

            if($request->ajax()) {
                return response()->json(['success' => TRUE, 'data'=> $enquiry], 200);
            }else{
                return redirect('/#get-in-touch')->with('flash_success', 'Thanks for contacting us, We will be in touch within 24 hours. The Eganow Team.');
            }
        }

        catch (ModelNotFoundException $e) {
            
             return response()->json(['success' => FALSE, 'message' => trans('api.user.user_not_found')], 200);
        }

    }

    public function add_emergency_contacts(Request $request) {

        $exist_contacts = EmergencyContact::where('user_id', Auth::user()->id)->get();
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
        $contacts->user_id = Auth::user()->id;
        $contacts->save();
        $emergency_contacts = EmergencyContact::where('user_id', Auth::user()->id)->get();
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

        $exist_contacts = EmergencyContact::where('user_id', Auth::user()->id)->get();

        $response_array = array('success' => true, 'contacts' => $exist_contacts);

        return response()->json($response_array, 200);

    }

    public function sendSOSAlert(Request $request){
        try{
            $request_id = $request->request_id;
            $emergency_contacts = EmergencyContact::where('user_id', Auth::user()->id)->get();
            $UserRequest = UserRequests::find($request_id);
            $UserRequest->sos_alert = 1;
            $UserRequest->alert_initiated = Carbon::now();
            $UserRequest->save();
            $from = "Eganow";
            $current_location = "http://www.google.com/maps/place/".$UserRequest->provider->latitude.",".$UserRequest->provider->longitude;
            $car_details = $UserRequest->provider->first_name." / ". $UserRequest->provider_profiles->car_registration." (".$UserRequest->provider_profiles->car_make ." ". $UserRequest->provider_profiles->car_model.")";


            $content = "Eganow Emergency Alert:

                        User name may be in danger and has triggered the sos button in our app.

                        Trip details: ". $UserRequest->s_address ." to ". $UserRequest->d_address. "
                        Current location: ". $current_location ."
                        Driver /Car details: ".$car_details."

                        You received this text because user has saved your number as emergency contact.";
                        $sos_number = Setting::get('eganow_sos_number');
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

    public function upload_image(Request $request)
    {

        try{
            $User = Auth::user();
            $rand = rand(100,999);
            $n = substr($User->first_name, 0, 2);
            $tempId = $rand.$n;

            $upload = new UploadImages;
            $upload->tempId = $tempId;
            $upload->user_id = $User->id;

            if($request->hasFile('image')){

                    $name = $User->id."-req-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual asset url';                    
                    $contents = file_get_contents($request->image);
                    $path = Storage::disk('s3')->put('requests/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    
                    $upload->url = $s3_url;
            }

            $upload->save();

            return response()->json(['success' => TRUE, 'data'=> $upload], 200);
        }
        catch (ModelNotFoundException $e) {
            
            return response()->json(['success' => FALSE, 'message' => 'Unable to upload, Please try again later']);
        } 
    }

    public function active_request(){
        try{
            $User = Auth::user();

            $UserRequest = UserRequests::where('user_id', $User->id)->whereNotIn('user_requests.status' , ['CANCELLED', 'COMPLETED','SCHEDULED','SEARCHING'])->get();

            return response()->json(['success' => TRUE, 'data'=> $UserRequest], 200);

        }catch(Exception $e){
            return response()->json(['success' => FALSE, 'message' => 'No Ongoing Request'], 200);
        }
    }

    public function trotrotracker(){
        $time = Carbon::now()->timestamp;
        $account = "replace with account name";
        $password = "replace with account password";
        $signature = md5(md5($password).$time);

        $url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

        $json = curl($url);

        $details = json_decode($json, TRUE);

        Log::info($details['record']);
        dd($details['record']['access_token']);
        return response()->json(['success' => TRUE,'url' => $url, 'data'=> $details], 200);

    }

    public function mmn(){

            $user = User::where('id',Auth::user()->id)->first();
            $user_cashback = $driver_cashback = 0;
            $mlm = array();
            $mlm_id = "u_".$user->id;

            $user_networks = MLMUserNetwork::with('user')->where('l1',$mlm_id)->orwhere('l2',$mlm_id)->orwhere('l3',$mlm_id)->orwhere('l4',$mlm_id)->orwhere('l5',$mlm_id)->get();

            $driver_networks = MLMDriverNetwork::with('driver')->where('l1',$mlm_id)->orwhere('l2',$mlm_id)->orwhere('l3',$mlm_id)->orwhere('l4',$mlm_id)->orwhere('l5',$mlm_id)->get();

            $c_user_network = count($user_networks);
            $c_driver_network = count($driver_networks);
            $total_network = $c_user_network + $c_driver_network;

            $user_trips = MLMUserCommission::where('l1_id',$mlm_id)->orwhere('l2_id',$mlm_id)->orwhere('l3_id',$mlm_id)->orwhere('l4_id',$mlm_id)->orwhere('l5_id',$mlm_id)->get();
            $driver_trips = MLMDriverCommission::where('l1_id',$mlm_id)->orwhere('l2_id',$mlm_id)->orwhere('l3_id',$mlm_id)->orwhere('l4_id',$mlm_id)->orwhere('l5_id',$mlm_id)->get();

                $amount =0;
                for ($j=0; $j < count($user_trips) ; $j++) { 
                    if($user_trips[$j]->l1_id == $mlm_id){
                        $amount = $user_trips[$j]->l1_com;
                    }else if($user_trips[$j]->l2_id == $mlm_id){
                        $amount = $user_trips[$j]->l2_com;
                    }else if($user_trips[$j]->l3_id == $mlm_id){
                        $amount = $user_trips[$j]->l3_com;
                    }else if($user_trips[$j]->l4_id == $mlm_id){
                        $amount = $user_trips[$j]->l4_com;
                    }else if($user_trips[$j]->l5_id == $mlm_id){
                        $amount = $user_trips[$j]->l5_com;
                    }
                    $user_cashback +=  $amount; 
                }
                $amount = 0;
                for ($k=0; $k < count($driver_trips) ; $k++) { 
                    if($driver_trips[$k]->l1_id == $mlm_id){
                        $amount = $driver_trips[$k]->l1_com;
                    }else if($driver_trips[$k]->l2_id == $mlm_id){
                        $amount = $driver_trips[$k]->l2_com;
                    }else if($driver_trips[$k]->l3_id == $mlm_id){
                        $amount = $driver_trips[$k]->l3_com;
                    }else if($driver_trips[$k]->l4_id == $mlm_id){
                        $amount = $driver_trips[$k]->l4_com;
                    }else if($driver_trips[$k]->l5_id == $mlm_id){
                        $amount = $driver_trips[$k]->l5_com;
                    }
                    $driver_cashback += $amount; 
                }
                $total_mlm_trips = count($user_trips) + count($driver_trips);
                $total_mlm_cashback = $user_cashback + $driver_cashback;

                $mlm['total_network'] = $total_network;
                $mlm['total_trips'] = $total_mlm_trips;
                $mlm['total_cashback'] = $total_mlm_cashback;

                $mlm['user_network'] = $c_user_network;
                $mlm['driver_network'] = $c_driver_network;

                $mlm['user_trips'] = count($user_trips);
                $mlm['driver_trips'] = count($driver_trips);

                $mlm['user_cashback'] = $user_cashback;
                $mlm['driver_cashback'] = $driver_cashback;

                $response_array = array('success' => true, 'mlm' => $mlm);

                return response()->json($response_array, 200);
    }

    public function delete_account(Request $request){
        try {
            $id = Auth::user()->id;
            $user = User::find($id);
            $user->delete_acc = 1;
            $user->save();
        return response()->json(['success' => TRUE, 'message' => 'Your Account Deleted!'], 200);
            
        } catch (Exception $e) {
            return response()->json(['success' => FALSE, 'message' => 'Account Not Found!'], 200);  
        }

    }
}
