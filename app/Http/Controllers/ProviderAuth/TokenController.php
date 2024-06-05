<?php

namespace App\Http\Controllers\ProviderAuth;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;

use Tymon\JWTAuth\Exceptions\JWTException;
use App\Notifications\ResetPasswordOTP;

use Log;
use Validator;
use Auth;
use Config;
use Setting;
use JWTAuth;
use Exception;
use Notification;
use Socialite;
use App\Helpers\Helper;

use App\Fleet;
use App\cities;
use App\User;
use App\region;
use App\Provider;
use App\ProviderProfile;
use App\Marketers;
use App\MarketerReferrals;
use App\ProviderDevice;
use Storage;
use Carbon\Carbon;
use \GuzzleHttp\Client;

class TokenController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function cities(Request $request)
    {
        $cities = cities::orderBy('city_name','asc')->get();

        return response()->json(['success' => TRUE, 'cities' => $cities]);
    }

    public function region(Request $request)
    {
        $region = region::orderBy('region_name','asc')->get();

        return response()->json(['success' => TRUE, 'region' => $region]);
    }

    public function register(Request $request)
    {
        $this->validate($request, [
                'device_id' => 'required',
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|email|max:255',
                // 'mobile' => 'required|between:6,13',
                'password' => 'required|min:6|confirmed',
                'picture' => 'mimes:jpeg,bmp,png',
            ]);
        try{

            $Providers = Provider::where('email',$request->email)
                        ->first();
            if($Providers)
            {
                return response()->json(['success' => FALSE, 'message'=> 'Already Registered with us, Please login'], 200);
            }
            else{
                if($request->mobile[0] == "0"){
                    $request->mobile = ltrim($request->mobile, 0);
                }
                $Provider = $request->all();
                $Provider['password'] = bcrypt($request->password);
                $otp = rand(1000, 9999);
                $Provider['otp'] = $otp;
                $Provider['fleet'] = 1;
                $rand = rand(100, 999);
                $name =  substr($request->first_name, 0, 3);
                $referral_code = strtoupper($name.$rand);
                $Provider['referal'] = $referral_code;
                if($request->has('country_code')){
                $Provider['country_code'] = $request->country_code;
                }
                if($request->login_by == 'manual'){

                if ($request->hasFile('picture')){
                    $name = $rand."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replacce with an actual url';                    
                    $contents = file_get_contents($request->picture);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $Provider['avatar'] = $s3_url;   
                }

                $Provider['password'] = bcrypt($request->password);
                }
                else{
                    $Providers = Provider::where('social_unique_id',$request->social_unique_id)
                            // ->where('user_id', Auth::user()->id)
                            ->first();
                    if(!$Providers)
                    {
                    $Provider['avatar'] = $request->image;
                    $Provider['social_unique_id'] = $request->social_unique_id;
                    $Provider['password'] = bcrypt('t@mi2h'); 
                    }
                    else{
                        return response()->json(['success' => FALSE, 'message'=> 'Already Registered with us, Please login'], 200);
                    }
                    
                }
                $Provider['status'] = 'onboarding';
                $Provider['otp_activation'] = 0;
                $Provider['wallet_balance'] = 0.00;

                $Provider = Provider::create($Provider);
                ProviderProfile::create(['provider_id' => $Provider->id]);

                ProviderDevice::create([
                        'provider_id' => $Provider->id,
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);

                if($request->has('referral')){
                    try{
                        $driver = Provider::find($Provider->id);
                        $marketer = Marketers::where('referral_code', $request->referral)->first();
                    
                
                        $user_referal = User::where('referal', $request->referral)->first();
                        $driver_referal = Provider::where('referal', $request->referral)->first();
                        $fleet_referal = Fleet::where('referal', $request->referral)->first();
                        if($user_referal)
                        {  $driver->user_referred = $request->referral;
                           $driver->wallet_balance = Setting::get('referal_balance');
                           $driver->referral_used = 1;
                           $driver->save();
                           $user_referal->wallet_balance += Setting::get('user_to_driver_referral');
                           $user_referal->save();
                        }else if($driver_referal)
                        {  $driver->driver_referred = $request->referral;
                           $driver->wallet_balance = Setting::get('referal_balance');
                           $driver->referral_used = 1;
                           $driver->save();
                           $driver_referal->wallet_balance += Setting::get('driver_to_driver_referral');
                           $driver_referal->save();
                           if($driver_referal->official_drivers == 1){
                            $driver_referal->work_pay_balance += Setting::get('work_pay_to_driver_referral');
                            $driver_referal->save();
                        }
                        }else if($fleet_referal)
                        {  $driver->fleet = $fleet_referal->id;
                           $driver->save();
                        }else if($marketer){
                            $marketer = Marketers::where('referral_code', $request->referral)->first();
                            $driver = Provider::find($Provider->id);
                            $driver->marketer = $marketer->id;
                            $marketer_referrals = new MarketerReferrals;
                            $marketer_referrals->marketer_id = $marketer->id;
                            $marketer_referrals->driver_id = $driver->id;
                            $marketer_referrals->referrer_code = $request->referral;
                            $marketer->total_referrals = $marketer->total_referrals + 1;
                            $marketer_referrals->save();
                            $marketer->save();
                            $driver->referral_used = 1;
                            $driver->save(); 
                        }
                    }
                    catch (Exception $e) {
                        
                    }
                    
                }

                

                return response()->json(['success' => TRUE, 'data'=> $Provider], 200);
            }

        } catch (QueryException $e) {
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Something went wrong, Please try again later!'], 500);
            }
            return abort(500);
        }
        
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function authenticate(Request $request)
    {
        $this->validate($request, [
                'device_id' => 'required',
                'device_type' => 'required|in:android,ios',
                // 'device_token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

        Config::set('auth.providers.users.model', 'App\Provider');

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'The email address or password you entered is incorrect.'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Something went wrong, Please try again later!'], 500);
        }

        $User = Provider::with('service', 'device')->find(Auth::user()->id);

        $User->access_token = $token;
        $User->currency = Setting::get('currency', 'GHS');
        $User->delete_menu = 1;
        if($User->delete_acc == 1){
            return response()->json(['success' => FALSE,'error' => 'Account was deleted!'], 200);
        }

        if($User->device) {
            if($User->device->token != $request->device_token) {
                $User->device->update([
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
            }
        } else {
            ProviderDevice::create([
                    'provider_id' => $User->id,
                    'udid' => $request->device_id,
                    'token' => $request->device_token,
                    'type' => $request->device_type,
                ]);
        }

        return response()->json(['success' => TRUE, 'data'=> $User], 200);
    }



 /**
     * Forgot Password.
     *
     * @return \Illuminate\Http\Response
     */


    public function forgot_password(Request $request){

        // $this->validate($request, [
        //         'email' => 'required|email|exists:providers,email',
        //     ]);

        try{  
            $email = str_replace(" ", "", $request->email);
            $provider = Provider::where('email' , $email)->first();

            if(!$provider){
                return response()->json(['success' => FALSE, 'message' => 'Account not found, please try again with different account!',],200);
            }
            $otp = rand(100000, 999999);
            $provider->password = bcrypt($otp);
            $provider->save();
            $to = $provider->mobile;
            $to = str_replace(" ", "", $to);
            $cc = $provider->country_code;
            $from = "DriveNow";
            if(str_contains($cc,"23") == true){
                $content = urlencode("DriveNow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on DriveNow, Drive for your future.");
                $clientId = env("HUBTEL_API_KEY");
                $clientSecret = env("HUBTEL_API_SECRET");

                // $sendSms =  (new HubtelMessage)
                // ->from($from)
                // ->to($to)
                // ->content($content);
                $rec = $cc.$to;

                $sendSms = sendSMS($from, $rec, $content, $clientId, $clientSecret);
                if(count($sendSms) > 1){
                    return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Hubtel'], 200);
                }
                else if(count($sendSms) == 1 || $sendSms == FALSE){
                    // $content = urlencode("DriveNow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on DriveNow, Drive for your future.");
                    $content = "DriveNow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on DriveNow, Drive for your future.";
                    $mobile = $provider->mobile;
                    if($mobile[0] == 0){
                        $receiver = $mobile;
                    }else{
                        $receiver = "0".$mobile; 
                    }
                    $sendMessage = sendMessageRancard($receiver, $content);
                    Log::info($sendMessage);

                    // $client1 = new \GuzzleHttp\Client();

                    // $url1 = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&getBalance=true";

                    // $headers1 = ['Content-Type' => 'application/json'];
                    
                    // $res1 = $client1->get($url1, ['headers' => $headers1]);

                    // $data = json_decode($res1->getBody());

                    // $balance = round(str_replace("Messaging balance for API User: f3En@x is","", $data));

                    // $client = new \GuzzleHttp\Client();

                    // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=DriveNow&text=".$content."&smsc=RANCARD";
                    // Log::info($url);

                    // $headers = ['Content-Type' => 'application/json'];
                    
                    // $res = $client->get($url, ['headers' => $headers]);

                    // $code = (string)$res->getBody();
                    // $codeT = str_replace("\n","",$code);
                
                    // if($codeT == "000"){
                    if($sendMessage['code'] == "200"){
                        return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Rancard'], 200);
                    }else{
                        
                        $rec = $cc.$to;
                        $content = "DriveNow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on DriveNow, Drive for your future.";
                        $sendTwilio = sendMessageTwilio($rec, $content);
                        //Log::info($sendTwilio);
                        if($sendTwilio){
                           return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Twilio'], 200); 
                        }else{
                            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                        }
                    }
                }
                
                
            }
            else{
                
                $rec = $cc.$to;               
                $content = "DriveNow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on DriveNow, Drive for your future.";
                $sendTwilio = sendMessageTwilio($rec, $content);
                Log::info($sendTwilio);
                if($sendTwilio){
                   return response()->json(['success' => TRUE, 'otp' => $otp, 'company' => 'Twilio'], 200); 
                }else{
                    return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent to your email!',
                'provider' => $provider
            ]);

        }catch(Exception $e){
            Log::info($e);
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }


    /**
     * Reset Password.
     *
     * @return \Illuminate\Http\Response
     */

    public function reset_password(Request $request){

        $this->validate($request, [
                'password' => 'required|confirmed|min:6',
                'id' => 'required|numeric|exists:providers,id'
            ]);

        try{

            $Provider = Provider::findOrFail($request->id);
            $Provider->password = bcrypt($request->password);
            $Provider->save();

            if($request->ajax()) {
                return response()->json(['message' => 'Password Updated']);
            }

        }catch (Exception $e) {
            if($request->ajax()) {
                return response()->json(['error' => trans('api.something_went_wrong')]);
            }
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
            ProviderDevice::where('provider_id', $request->id)->update(['udid'=> '', 'token' => '']);
            return response()->json(['message' => trans('api.logout_success')]);
        } catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }

    // public function social_login(Request $request)
    // {
    //     $this->validate($request, [
    //             'device_id' => 'required',
    //             'device_type' => 'required|in:android,ios',
    //             'device_token' => 'required',
    //             'social_unique_id' => 'required',
    //             'password' => 'required|min:6',
    //         ]);

    //     Config::set('auth.providers.users.model', 'App\Provider');

    //     $credentials = $request->only('social_unique_id', 'password');

    //     try {
    //         if (! $token = JWTAuth::attempt($credentials)) {
    //             return response()->json(['success' => FALSE, 'error' => 'You\'re not registered with us.'], 200);
    //         }
    //     } catch (JWTException $e) {
    //         return response()->json(['success' => FALSE, 'error' => 'Something went wrong, Please try again later!'], 200);
    //     }

    //     $User = Provider::with('service', 'device')->find(Auth::user()->id);

    //     $User->access_token = $token;
    //     $User->currency = Setting::get('currency', '$');

    //     if($User->device) {
    //         if($User->device->token != $request->token) {
    //             $User->device->update([
    //                     'udid' => $request->device_id,
    //                     'token' => $request->device_token,
    //                     'type' => $request->device_type,
    //                 ]);
    //         }
    //     } else {
    //         ProviderDevice::create([
    //                 'provider_id' => $User->id,
    //                 'udid' => $request->device_id,
    //                 'token' => $request->device_token,
    //                 'type' => $request->device_type,
    //             ]);
    //     }

    //     return response()->json($User);
    // }



    public function social_login(Request $request) { 

        $validator = Validator::make(
            $request->all(),
            [
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'social_unique_id'=>'required',
                'mobile' => 'required',
                'first_name' => 'required',
                'email' => 'required|email|max:255',
                'device_id' => 'required',
                'login_by' => 'required|in:manual,facebook,google'
            ]
        );
        
        if($validator->fails()) {
            return response()->json(['status'=>false,'message' => $validator->messages()->all()]);
        }
        // $user = Socialite::driver('facebook')->stateless();
        // $FacebookDrive = $user->userFromToken( $request->accessToken);
       
        try{
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }
            $FacebookSql = Provider::where('social_unique_id',$request->social_unique_id);
            if($request->email !=""){
                $FacebookSql->orWhere('email',$request->email);
            }
            $AuthUser = $FacebookSql->first();

            if($AuthUser){ 
                $AuthUser->social_unique_id=$request->social_unique_id;
                $AuthUser->login_by="facebook";
                $AuthUser->mobile=$request->mobile?:'';
                $AuthUser->country_code=$request->country_code?:'233';
                $AuthUser->otp_activation=1; 
                $AuthUser->fleet='1'; 
                if($AuthUser->wallet_balance == ''){
                    $AuthUser->wallet_balance = 0.00;
                }
                $AuthUser->avatar = $request->avatar;
                $AuthUser->save();  
                $Profile = ProviderProfile::where('provider_id', $AuthUser->id)->first();
                if(!$Profile){
                    ProviderProfile::create(['provider_id' => $AuthUser->id]);
                }
            }else{   
                $AuthUser= new Provider();

                $AuthUser->email=$request->email;
                $AuthUser->first_name=$request->first_name;
                $AuthUser->last_name= $request->last_name;
                $AuthUser->password=bcrypt($request->social_unique_id);
                $AuthUser->social_unique_id=$request->social_unique_id;
                $AuthUser->mobile=$request->mobile?:'';
                $AuthUser->country_code=$request->country_code?:'233';
                $AuthUser->avatar = $request->avatar;
                $AuthUser->otp_activation=1; 
                $AuthUser->fleet=1; 
                $AuthUser->wallet_balance=0.00; 
                $AuthUser->login_by=$request->login_by;
                
                $rand = rand(100, 999);
                $name =  substr($request->first_name, 0, 3);
                $referral_code = strtoupper($name.$rand);
                $AuthUser->referal = $referral_code;


                $AuthUser->save();
                ProviderProfile::create(['provider_id' => $AuthUser->id]);

            }    
            if($AuthUser){ 
                $userToken = JWTAuth::fromUser($AuthUser);
                $User = Provider::with('service', 'device')->find($AuthUser->id);
                if($User->device) {
                    ProviderDevice::where('id',$User->device->id)->update([
                        
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                    
                } else {
                    ProviderDevice::create([
                        'provider_id' => $User->id,
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                }
                return response()->json([
                            "status" => true,
                            "token_type" => "Bearer",
                            "access_token" => $userToken,
                            'currency' => Setting::get('currency', '$'),
                            'sos' => Setting::get('sos_number', '911')
                        ]);
            }else{
                return response()->json(['status'=>false,'message' => "Invalid credentials!"]);
            }  
        } catch (Exception $e) {
            
            return response()->json(['status'=>false,'message' => trans('api.something_went_wrong')]);
        }
    }




    public function facebookViaAPI(Request $request) { 

        $validator = Validator::make(
            $request->all(),
            [
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'accessToken'=>'required',
                //'mobile' => 'required',
                'device_id' => 'required',
                'login_by' => 'required|in:manual,facebook,google'
            ]
        );
        
        if($validator->fails()) {
            return response()->json(['status'=>false,'message' => $validator->messages()->all()]);
        }
        $user = Socialite::driver('facebook')->stateless();
        $FacebookDrive = $user->userFromToken( $request->accessToken);
       
        try{
            $FacebookSql = Provider::where('social_unique_id',$FacebookDrive->id);
            if($FacebookDrive->email !=""){
                $FacebookSql->orWhere('email',$FacebookDrive->email);
            }
            $AuthUser = $FacebookSql->first();
            if($AuthUser){ 
                $AuthUser->social_unique_id=$FacebookDrive->id;
                $AuthUser->login_by="facebook";
                $AuthUser->mobile=$request->mobile?:'';
                $AuthUser["otp_activation"]=1; 
                $AuthUser->save();  
            }else{   
                $AuthUser["email"]=$FacebookDrive->email;
                $name = explode(' ', $FacebookDrive->name, 2);
                $AuthUser["first_name"]=$name[0];
                $AuthUser["last_name"]=isset($name[1]) ? $name[1] : '';
                $AuthUser["password"]=bcrypt($FacebookDrive->id);
                $AuthUser["social_unique_id"]=$FacebookDrive->id;
                $AuthUser["avatar"]=$FacebookDrive->avatar;
                $AuthUser["mobile"]=$request->mobile?:'';
                $AuthUser["otp_activation"]=1; 
                $AuthUser["country_code"]=$request->country_code?:'+233';
                $AuthUser["login_by"]="facebook";

                $otp = rand(1000, 9999);
                $AuthUser['otp'] = $otp;
                $rand = rand(1000, 9999);
                $name =  substr($name[0], 0, 2);
                $referral_code = strtoupper($name.'FNX'.$rand);
                $AuthUser['referal'] = $referral_code;


                $AuthUser = Provider::create($AuthUser);

                // if(Setting::get('demo_mode', 0) == 1) {
                //     $AuthUser->update(['status' => 'approved']);
                //     ProviderService::create([
                //         'provider_id' => $AuthUser->id,
                //         'service_type_id' => '1',
                //         'status' => 'active',
                //         'service_number' => '4pp03ets',
                //         'service_model' => 'Audi R8',
                //     ]);
                // }
            }    
            if($AuthUser){ 
                $userToken = JWTAuth::fromUser($AuthUser);
                $User = Provider::with('service', 'device')->find($AuthUser->id);
                if($User->device) {
                    ProviderDevice::where('id',$User->device->id)->update([
                        
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                    
                } else {
                    ProviderDevice::create([
                        'provider_id' => $User->id,
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                }
                return response()->json([
                            "status" => true,
                            "token_type" => "Bearer",
                            "access_token" => $userToken,
                            'currency' => Setting::get('currency', '$'),
                            'sos' => Setting::get('sos_number', '911')
                        ]);
            }else{
                return response()->json(['status'=>false,'message' => "Invalid credentials!"]);
            }  
        } catch (Exception $e) {
            return response()->json(['status'=>false,'message' => trans('api.something_went_wrong')]);
        }
    }



    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function googleViaAPI(Request $request) { 

        $validator = Validator::make(
            $request->all(),
            [
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'accessToken'=>'required',
                //'mobile' => 'required',
                'device_id' => 'required',
                'login_by' => 'required|in:manual,facebook,google'
            ]
        );
        
        if($validator->fails()) {
            return response()->json(['status'=>false,'message' => $validator->messages()->all()]);
        }
        $user = Socialite::driver('google')->stateless();
        $GoogleDrive = $user->userFromToken( $request->accessToken);
       
        try{
            $GoogleSql = Provider::where('social_unique_id',$GoogleDrive->id);
            if($GoogleDrive->email !=""){
                $GoogleSql->orWhere('email',$GoogleDrive->email);
            }
            $AuthUser = $GoogleSql->first();
            if($AuthUser){
                $AuthUser->social_unique_id=$GoogleDrive->id;
                $AuthUser->mobile=$request->mobile?:'';  
                $AuthUser->login_by="google";
                $AuthUser["otp_activation"]=1; 
                $AuthUser->save();
            }else{   
                $AuthUser["email"]=$GoogleDrive->email;
                $name = explode(' ', $GoogleDrive->name, 2);
                $AuthUser["first_name"]=$name[0];
                $AuthUser["last_name"]=isset($name[1]) ? $name[1] : '';
                $AuthUser["password"]=($GoogleDrive->id);
                $AuthUser["social_unique_id"]=$GoogleDrive->id;
                $AuthUser["avatar"]=$GoogleDrive->avatar;
                $AuthUser["mobile"]=$request->mobile?:''; 
                $AuthUser["otp_activation"]=1; 
                $AuthUser["country_code"]=$request->country_code?:'+233';
                $AuthUser["login_by"]="google";

                $otp = rand(1000, 9999);
                $AuthUser['otp'] = $otp;
                $rand = rand(1000, 9999);
                $name =  substr($name[0], 0, 2);
                $referral_code = strtoupper($name.'FNX'.$rand);
                $AuthUser['referal'] = $referral_code;

                $AuthUser = Provider::create($AuthUser);

                // if(Setting::get('demo_mode', 0) == 1) {
                //     $AuthUser->update(['status' => 'approved']);
                //     ProviderService::create([
                //         'provider_id' => $AuthUser->id,
                //         'service_type_id' => '1',
                //         'status' => 'active',
                //         'service_number' => '4pp03ets',
                //         'service_model' => 'Audi R8',
                //     ]);
                // }
            }    
            if($AuthUser){
                $userToken = JWTAuth::fromUser($AuthUser);
                $User = Provider::with('service', 'device')->find($AuthUser->id);
                if($User->device) {
                    ProviderDevice::where('id',$User->device->id)->update([
                        
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                    
                } else {
                    ProviderDevice::create([
                        'provider_id' => $User->id,
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                }
                return response()->json([
                            "status" => true,
                            "token_type" => "Bearer",
                            "access_token" => $userToken,
                            'currency' => Setting::get('currency', '$'),
                            'sos' => Setting::get('sos_number', '911')
                        ]);
            }else{
                return response()->json(['status'=>false,'message' => "Invalid credentials!"]);
            }  
        } catch (Exception $e) {
            return response()->json(['status'=>false,'message' => trans('api.something_went_wrong')]);
        }
    }

    public function drivenow_token(Request $request)
    {
        $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

        Config::set('auth.providers.users.model', 'App\Provider');

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'The email address or password you entered is incorrect.'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Something went wrong, Please try again later!'], 500);
        }

        return response()->json(['success' => TRUE, 'API_Key'=> $token], 200);
    }
    
}
