<?php

namespace App\Http\Controllers\ProviderResources;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Auth;
use Setting;
use Storage;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use Log;
use Carbon\Carbon;
use App\DriverSubaccount;
use App\cities;
use App\region;
use App\ProviderProfile;
use App\User;
use App\Provider;
use App\ProviderService;
use App\Document;
use App\Fleet;
use App\ProviderDocument;
use App\UserRequests;
use App\ServiceType;
use App\ProviderDevice;
use App\DriverActivity;
use App\OfficialDriver;
use App\Http\Controllers\SendPushNotification;
use App\Bank;
use DB;
use File;
use App\DriverDayOff;
use App\DriveNowVehicle;
class ProfileController extends Controller
{
    /**
     * Create a new user instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('provider.api', ['except' => ['show', 'store', 'available', 'location_edit', 'location_update']
            ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
         try {
            $service = Auth::user()->service = (object) array();
            Auth::user()->service = ProviderService::where('provider_id',Auth::user()->id)
                                            ->with('service_type')
                                            ->get();
            if(!Auth::user()->service){
                Auth::user()->service = $service;
            }
            Auth::user()->user_referral = User::where('driver_referred', Auth::user()->referal)->count();
            Auth::user()->driver_referral = Provider::where('driver_referred', Auth::user()->referal)->count();
            Auth::user()->currency = Setting::get('currency', '$');
            Auth::user()->time_out = Setting::get('provider_select_timeout', 180);
            Auth::user()->welcome_image = Setting::get('welcome_image_driver');
                
            Auth::user()->fleet = Fleet::find(Auth::user()->fleet);
            Auth::user()->android_driver_version = Setting::get('android_driver_version');
            Auth::user()->ios_driver_version = Setting::get('ios_driver_version');
            Auth::user()->surge = Setting::get('surge_percentage');

            Auth::user()->android_driver_mapkey = Setting::get('android_driver_mapkey');
            Auth::user()->ios_driver_mapkey = Setting::get('ios_driver_mapkey');

            $Driver = Provider::with('service', 'device')->find(Auth::user()->id);
                if($Driver->device) {
                    if($request->device_token != "" || $request->device_token != null){
                        $Device = ProviderDevice::where('provider_id',$Driver->id)->first();
                        $Device->udid = $request->device_id;
                        $Device->token = $request->device_token;
                        $Device->type = $request->device_type;
                        $Device->save();
                    }
                    
                } else {
                    ProviderDevice::create([
                        'provider_id' => $Driver->id,
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
                }

            if($request->has('android_app_version')){
                $Driver->android_app_version = $request->android_app_version;
                $Driver->save();
            }  

            if($request->has('ios_app_version')){
                $Driver->ios_app_version = $request->ios_app_version;
                $Driver->save();
            }   


            $activeHours = DriverActivity::where('driver_id', Auth::user()->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                    if($activeHours[0] > 0){ 

                        // if($activeHours[0] >= 60){
                        //     $activeHour = $activeHours[0] / 60 ." Hrs";
                        // }else{
                        //     $activeHour = $activeHours[0] . " mins";
                        // }

                        // Auth::user()->activeHoursFormat = $activeHour;
                        Auth::user()->activeHours = date('H\h i\m', mktime(0,$activeHours[0]));
                    }else{
                        Auth::user()->activeHours = 0;
                    }

                $today_earning = $today_tot = $today_com = $today_can = 0;

                $today = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->get();

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

                //Today Earnings

               Auth::user()->earnings = number_format($today_earning, 2);
               Auth::user()->total_request = $today_tot;
               Auth::user()->completed_request = $today_com;
               Auth::user()->cancelled_request = $today_can;

               $total_earning = $total_tot = $total_com = $total_can = 0;
                $total = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->get();

                for($i=0; $i < count($total); $i++) {
                        $total_earning += ($total[$i]['payment']['driver_earnings']);
                        if($total[$i]['status'] == 'COMPLETED'){
                            $total_com +=1;
                        }
                        if($total[$i]['status'] == 'CANCELLED'){
                            $total_can +=1;
                        }
                        $total_tot = count($total);
                    }

                //Today Earnings

               Auth::user()->total_earnings = number_format($total_earning, 2);
               Auth::user()->total_total_request = $total_tot;
               Auth::user()->total_completed_request = $total_com;
               Auth::user()->total_cancelled_request = $total_can;
               Auth::user()->delete_menu = 1;
               $last_trip = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment','user','service_type')
                    ->where('created_at', '>=', Carbon::today())
                    ->orderBy('created_at', 'desc')
                    ->first();

            $User = Auth::user();
            $bank_details = ProviderProfile::where('provider_id',Auth::user()->id)->first();
            return response()->json(['success' => TRUE, 'data'=> $User, 'bank_details' => $bank_details, 'last_trip' => $last_trip], 200);

        } catch(Exception $e) { 
            return $e->getMessage();
        }
    }

    public function referals()
    {
        try{
        $user = Provider::find(Auth::user()->id);
        $user_referral = User::where('driver_referred', $user->referal)->count();
        $driver_referral = Provider::where('driver_referred', $user->referal)->count();

        $response_array = array(
            'success' => true,
            'user_referals'=> $user_referral,
            'driver_referals' => $driver_referral,
        );
        $response = response()->json($response_array, 200);
        return $response;
        }catch(Exception $e){
            return response()->json(['success' => FALSE, 'message'=> 'Network Error, Please check your internet connection'], 200);
        }
    }

    public function upload_document(Request $request)
    {

        $this->validate($request, [
                'file' => 'max:25000|mimes:jpg,jpeg,png',
            ]);
        try{

            $Document = ProviderDocument::where('provider_id', Auth::user()->id)
                ->where('document_id', $request->file_id)
                ->first();
            if(count($Document)){

                    $name = $Document->provider_id."-doc-".$Document->id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual url for drivenow';                    
                    $contents = file_get_contents($request->file);
                    $path = Storage::disk('s3')->put('driver_documents/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;

                $Document->update([
                        'url' => $s3_url,
                        'status' => 'ASSESSING',
                    ]);
            }
            else{
                    $name = Auth::user()->id."-doc-".$request->file_id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual url for drivenow';                    
                    $contents = file_get_contents($request->file);
                    $path = Storage::disk('s3')->put('driver_documents/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    
                $Document = ProviderDocument::create([
                        'url' => $s3_url,
                        'provider_id' => Auth::user()->id,
                        'document_id' => $request->file_id,
                        'status' => 'ASSESSING',
                    ]);
            }
            Log::info($Document);
            return response()->json(['success' => TRUE, 'data'=> $Document], 200);
        }
        catch(Exception $e) { 
            
            return response()->json(['success' => FALSE, 'error' => 'Upload Error!'], 200);
        }
    }

    public function documents() {

        if($Documents = Document::all()) {
            foreach ($Documents as $key => $value) {

                $price = ProviderDocument::where('provider_id',Auth::user()->id)
                            ->where('document_id',$value->id)
                            ->first();

                if(count($price) > 0) {
                    $Documents[$key]->available = true;
                    $Documents[$key]->image = $price->url;
                    $Documents[$key]->status = $price->status;
                }else{
                    $Documents[$key]->available = false;
                    $Documents[$key]->image = '';
                    $Documents[$key]->status = 'Not uploaded';
                }
            }
            $Provider = ProviderProfile::where('provider_id',Auth::user()->id)
                                            ->first();
            $Provider_status = array('bank_status' => $Provider->bank_status,
                                    'license_status' => $Provider->license_status,
                                    'vehicle_status' => $Provider->vehicle_status);
        $Provider = array(
                        // 'id' => $Provider->id,
                        'provider_id' => $Provider->provider_id,
                        'language' => $Provider->language,
                        'address' => $Provider->address,
                        'address_secondary' => $Provider->address_secondary,
                        'city' => $Provider->city,
                        'country' => $Provider->country,
                        'acc_no'=> $Provider->acc_no,
                        'acc_name'=> $Provider->acc_name,
                        'bank_name'=> $Provider->bank_name,
                        'bank_name_id'=> $Provider->bank_name_id,
                        'bank_code'=> $Provider->bank_code,
                        'dl_city'=> $Provider->dl_city,
                        'dl_city_id'=> $Provider->dl_city_id,
                        'dl_country'=> $Provider->dl_country,
                        'dl_state'=> $Provider->dl_state,
                        'dl_state_id'=> $Provider->dl_state_id,
                        'dl_exp'=> $Provider->dl_exp,
                        'dl_no'=> $Provider->dl_no,
                        'postal_code' => $Provider->postal_code,
                        'car_registration' => $Provider->car_registration,
                        'car_make' => $Provider->car_make,
                        'car_model' => $Provider->car_model,
                        'car_picture' => $Provider->car_picture,
                        'mileage' => $Provider->mileage,
                        'car_make_year' => $Provider->car_make_year,
                        'road_worthy_expire' => $Provider->road_worthy_expire,
                        'insurance_type' => $Provider->insurance_type,
                        'insurance_expire' => $Provider->insurance_expire,
                        
                        );

        $Provider = Helper::null_safe($Provider);
       
        $Provider= array($Provider);

// $banks = Bank::all();
        $banks = array();

// $banks['1'] = 'Access Bank Ghana Limited';
// $banks['2'] = 'ADB Bank Limited';
// $banks['3'] = 'Bank of Africa Ghana Limited';
// $banks['4'] = 'Bank of Baroda Ghana Limited';
// $banks['5'] = 'BSIC Ghana Limited';
// $banks['6'] = 'Barclays Bank of Ghana Limited';
// $banks['7'] = 'CAL Bank Limited';
// $banks['8'] = 'Ecobank Ghana Limited';
// $banks['9'] = 'Energy Bank Ghana Limited';
// $banks['10'] = 'FBNBank Ghana Limited [2]';
// $banks['11'] = 'Fidelity Bank Ghana Limited';
// $banks['12'] = 'First Atlantic Bank Limited';
// $banks['13'] = 'First National Bank Ghana Limited';
// $banks['14'] = 'GCB Bank Limited';
// $banks['15'] = 'GN Bank Limited';
// $banks['16'] = 'Guaranty Trust Bank (Ghana) Limited';
// $banks['17'] = 'HFC Bank Ghana Limited';
// $banks['18'] = 'National Investment Bank Limited';
// $banks['19'] = 'Prudential Bank Limited';
// $banks['20'] = 'Société Générale Ghana Limited';
// $banks['21'] = 'Stanbic Bank Ghana Limited';
// $banks['22'] = 'Standard Chartered Bank Ghana Limited';
// $banks['23'] = 'The Royal Bank Limited';
// $banks['24'] = 'UniBank Ghana Limited';
// $banks['25'] = 'United Bank for Africa Ghana Limited';
// $banks['26'] = 'Universal Merchant Bank Ghana Limited';
// $banks['27'] = 'Zenith Bank Ghana';
// $banks['28'] = 'Sovereign Bank Ghana';
// $banks['29'] = 'Premium Bank Limited';
// $banks['30'] = 'OmniBank Ghana Limited';
// $banks['31'] = 'Heritage Bank Limited';
// $banks['32'] = 'The Construction Bank Ghana Limited';
// $banks['33'] = 'The Beige Bank Limited';
// $banks['34'] = 'GHL Bank Limited';
// $banks['35'] = 'ARB Apex Bank Limited';

$banks['1']['bank_name'] = "Slydepay";
$banks['1']['bank_code'] = "SLYDEPAY";
$banks['2']['bank_name'] = "MTN Mobile Money";
$banks['2']['bank_code'] = "MTN_MONEY";
$banks['3']['bank_name'] = "AirtelTigo Money";
$banks['3']['bank_code'] = "AIRTEL_MONEY";
$banks['4']['bank_name'] = "Vodafone Cash";
$banks['4']['bank_code'] = "VODAFONE_CASH";
$banks['5']['bank_name'] = "National Investment Bank";
$banks['5']['bank_code'] = "nib-account-fi-service";
$banks['6']['bank_name'] = "Prudential Bank Limited";
$banks['6']['bank_code'] = "prudential-account-fi-service";
$banks['7']['bank_name'] = "Guaranty Trust (GH) Limited";
$banks['7']['bank_code'] = "gt-account-fi-service";
$banks['8']['bank_name'] = "Heritage Bank";
$banks['8']['bank_code'] = "heritage-account-fi-service";
$banks['9']['bank_name'] = "First National Bank";
$banks['9']['bank_code'] = "fnb-account-fi-service";
$banks['10']['bank_name'] = "Sovereign Bank";
$banks['10']['bank_code'] = "sovereign-account-fi-service";
$banks['11']['bank_name'] = "Universal Merchant Bank";
$banks['11']['bank_code'] = "umb-account-fi-service";
$banks['12']['bank_name'] = "Zenith Bank Limited";
$banks['12']['bank_code'] = "zenith-account-fi-service";
$banks['13']['bank_name'] = "Bank of Baroda";
$banks['13']['bank_code'] = "baroda-account-fi-service";
$banks['14']['bank_name'] = "Access Bank Limited";
$banks['14']['bank_code'] = "access-account-fi-service";
$banks['15']['bank_name'] = "CAL Bank";
$banks['15']['bank_code'] = "cal-account-fi-service";
$banks['16']['bank_name'] = "Energy Bank";
$banks['16']['bank_code'] = "energy-account-fi-service";
$banks['17']['bank_name'] = "Standard Chartered Bank";
$banks['17']['bank_code'] = "standardchartered-account-fi-service";
$banks['18']['bank_name'] = "Ecobank Ghana";
$banks['18']['bank_code'] = "ecobank-account-fi-service";
$banks['19']['bank_name'] = "Barclays Bank";
$banks['19']['bank_code'] = "barclays-account-fi-service";
$banks['20']['bank_name'] = "GCB Bank";
$banks['20']['bank_code'] = "gcb-account-fi-service";
$banks['21']['bank_name'] = "Stanbic Bank";
$banks['21']['bank_code'] = "stanbic-account-fi-service";
$banks['22']['bank_name'] = "Agricultural Development Bank";
$banks['22']['bank_code'] = "adb-account-fi-service";
$banks['23']['bank_name'] = "United Bank of Africa";
$banks['23']['bank_code'] = "uba-account-fi-service";
$banks['24']['bank_name'] = "The Royal Bank";
$banks['24']['bank_code'] = "royal-account-fi-service";
$banks['25']['bank_name'] = "Fidelity Bank";
$banks['25']['bank_code'] = "fidelity-account-fi-service";

$bank = Bank::all();


$banks = array($banks);
$cities = cities::orderBy('city_name','asc')->get();
$region = region::orderBy('region_name','asc')->get();

            
            return response()->json(['success' => TRUE, 'Documents'=> $Documents, 'Details' => $Provider, 'Provider_status' => $Provider_status, 'Banks' => $banks, 'bank' => $bank, 'Cities' => $cities, 'Regions' => $region], 200);
        } else {
            return response()->json(['error' => 'No Documents!'], 500);
        }

    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        $this->validate($request, [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'mobile' => 'required|between:6,13',
                'avatar' => 'mimes:jpeg,bmp,png',
                'language' => 'max:255',
                'address' => 'max:255',
                'address_secondary' => 'max:255',
                'city' => 'max:255',
                'country' => 'max:255',
                'postal_code' => 'max:255',
            ]);


        try {

            $Provider = Auth::user();

            if($request->has('first_name')) 
                $Provider->first_name = $request->first_name;

            if($request->has('last_name')) 
                $Provider->last_name = $request->last_name;

            if ($request->has('mobile'))
                $Provider->mobile = $request->mobile;

            if ($request->has('description'))
                $Provider->description = $request->description;

             if ($request->hasFile('avatar')) {
                $name = $Provider->id."-profile-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual url for profile avatar';                    
                $contents = file_get_contents($request->avatar);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $Provider->avatar = $s3_url; 
            }

            if($Provider->profile) {
                $Provider->profile->update([
                        'address' => $request->address ? : $Provider->profile->address,
                    ]);
            } else {
                ProviderProfile::create([
                        'provider_id' => $Provider->id,
                        'address' => $request->address,
                    ]);
            }


            $Provider->save();

            return redirect(route('provider.profile.index'))->with('flash_success','Profile Updated');
        }

        catch (ModelNotFoundException $e) {
            
            return response()->json(['error' => 'Provider Not Found!'], 404);
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $Provider = ProviderService::where('provider_id',Auth::user()->id)
                                            ->with('service_type')
                                            ->get();
        return view('provider.profile.index',compact('Provider'));
    }

    public function acc_details()
    {
        $Provider = ProviderProfile::where('provider_id',Auth::user()->id)
                                            ->first();
        $Provider = array(
                        'id' => $Provider->id,
                        'provider_id' => $Provider->provider_id,
                        'language' => $Provider->language,
                        'address' => $Provider->address,
                        'address_secondary' => $Provider->address_secondary,
                        'city' => $Provider->city,
                        'country' => $Provider->country,
                        'acc_no'=> $Provider->acc_no,
                        'acc_name'=> $Provider->acc_name,
                        'bank_name'=> $Provider->bank_name,
                        'bank_name_id'=> $Provider->bank_name_id,
                        'bank_code'=> $Provider->bank_code,
                        'dl_city'=> $Provider->dl_city,
                        'dl_country'=> $Provider->dl_country,
                        'dl_state'=> $Provider->dl_state,
                        'dl_exp'=> $Provider->dl_exp,
                        'dl_no'=> $Provider->dl_no,
                        'postal_code' => $Provider->postal_code,
                        'car_registration' => $Provider->car_registration,
                        'car_make' => $Provider->car_make,
                        'car_model' => $Provider->car_model,
                        'car_picture' => $Provider->car_picture,
                        'mileage' => $Provider->mileage,
                        'car_make_year' => $Provider->car_make_year,
                        'road_worthy_expire' => $Provider->road_worthy_expire,
                        'insurance_type' => $Provider->insurance_type,
                        'insurance_expire' => $Provider->insurance_expire,
                        );
        $Provider = Helper::null_safe($Provider);
        $Provider = array($Provider);
        $bank = Bank::all();
        return response()->json(['success' => TRUE, 'data'=> $Provider, 'bank' => $bank], 200);
    }

    public function upload_details(Request $request)
    {
        $this->validate($request, [
            // 'bank_code' => 'required',
            // 'acc_no'    => 'required',
            // 'bankcountry'   =>  'required',
        ]);
        
        try {
            $user = Auth::user();
            $Provider = ProviderProfile::where('provider_id',$user->id)->first();

            if(!$Provider){
                $Provider = new ProviderProfile;
                $Provider->provider_id = $user->id;
            }
            if($request->has('acc_no')) 
                $Provider->acc_no = $request->acc_no;

            if($request->has('acc_name')) 
                $Provider->acc_name = $request->acc_name;

            if ($request->has('bank_name'))
                $Provider->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $Provider->bank_name_id = $request->bank_name_id-1;

            if ($request->has('bank_code'))
                $Provider->bank_code = $request->bank_code;

            if ($request->has('dl_no'))
                $Provider->dl_no = $request->dl_no;

            if ($request->has('dl_exp'))
                $Provider->dl_exp = $request->dl_exp;

            if ($request->has('dl_country'))
                $Provider->dl_country = $request->dl_country;

            if ($request->has('dl_state'))
                $Provider->dl_state = $request->dl_state;

            if ($request->has('dl_city'))
                $Provider->dl_city = $request->dl_city;

            if ($request->has('dl_state_id'))
                $Provider->dl_state_id = $request->dl_state_id-1;

            if ($request->has('dl_city_id'))
                $Provider->dl_city_id = $request->dl_city_id-1;

            if ($request->has('car_registration'))
                $Provider->car_registration = $request->car_registration;

            if ($request->has('car_make'))
                $Provider->car_make = $request->car_make;

            if ($request->has('car_model'))
                $Provider->car_model = $request->car_model;

            if ($request->hasFile('car_picture')){
                $name = $Provider->provider_id."-car-".$Provider->id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual for car picture';                    
                    $contents = file_get_contents($request->car_picture);
                    $path = Storage::disk('s3')->put('driver_cars/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                $Provider->car_picture = $s3_url;
            }

            if ($request->has('mileage'))
                $Provider->mileage = $request->mileage;

            if ($request->has('car_make_year'))
                $Provider->car_make_year = $request->car_make_year;

            if ($request->has('road_worthy_expire'))
                $Provider->road_worthy_expire = $request->road_worthy_expire;

            if ($request->has('insurance_type'))
                $Provider->insurance_type = $request->insurance_type;

            if ($request->has('insurance_expire'))
                $Provider->insurance_expire = $request->insurance_expire;

            $Provider->save();

            $user->document_uploaded = 1;
            $user->save();
            // if($request->has('acc_no')) {
            //     $driver = DriverSubaccount::where('driver_id', $user->id)->first();
            //     if(empty($driver)){
            //         $client = new Client(['http_errors' => false]);
            //         $url ="https://api.ravepay.co/v2/gpx/subaccounts/create";
            //         $headers = [
            //             'Content-Type' => 'application/json',
            //         ];
            //         $body = [
            //                     "account_bank"              =>  $Provider->bank_code,
            //                     "account_number"            =>  $Provider->acc_no, 
            //                     "business_name"             =>  $user->first_name,
            //                     "business_email"            =>  $user->email,
            //                     "business_contact"          =>  $user->first_name,
            //                     "business_contact_mobile"   =>  $user->mobile,
            //                     "business_mobile"           =>  $user->mobile,
            //                     "country"                   =>  'GH',
            //                     "meta"                      =>  ["metaname" => "MarketplaceID", "metavalue" => "ggs-920900"],
            //                     "seckey"                    =>  env("RAVE_SECRET_KEY")
            //                 ];            
            //         $res = $client->post($url, [
            //             'headers' => $headers,
            //             'body' => json_encode($body),
            //         ]);
            //         $subaccount = json_decode($res->getBody(),true);
            //         if($subaccount['status'] == 'success'){
            //             $split                  = new DriverSubaccount;
            //             $split->driver_id       = $user->id;
            //             $split->split_id        = $subaccount['data']['id'];
            //             $split->account_number  = $subaccount['data']['account_number'];
            //             $split->bank_code       = $subaccount['data']['account_bank'];
            //             $split->business_name   = $subaccount['data']['business_name'];
            //             $split->fullname        = $subaccount['data']['fullname'];
            //             $split->date_created    = $subaccount['data']['date_created'];
            //             $split->account_id      = $subaccount['data']['account_id'];
            //             $split->split_ratio     = $subaccount['data']['split_ratio'];
            //             $split->split_type      = $subaccount['data']['split_type'];
            //             $split->split_value     = $subaccount['data']['split_value'];
            //             $split->subaccount_id   = $subaccount['data']['subaccount_id'];
            //             $split->bank_name       = $subaccount['data']['bank_name'];
            //             $split->country         = $subaccount['data']['country'];
            //             $split->save();
            //         }        
            //         else{
            //             return response()->json(['success'=>FALSE, 'message' => $subaccount['message']], 200);
            //         } 
            //     }       
            // }
            // else{
            //     $client = new Client(['http_errors' => false]);
            //     $url ="https://api.ravepay.co/v2/gpx/subaccounts/edit";
            //     $headers = [
            //         'Content-Type' => 'application/json',
            //     ];
            //     $body = [
            //                 "id"                        =>  $driver->split_id,
            //                 "account_bank"              =>  $Provider->bank_code,
            //                 "account_number"            =>  $Provider->acc_no, 
            //                 "business_name"             =>  $user->first_name,
            //                 "business_email"            =>  $user->email,                            
            //                 "seckey"                    =>  env("RAVE_SECRET_KEY")
            //             ];            
            //     $res = $client->post($url, [
            //         'headers' => $headers,
            //         'body' => json_encode($body),
            //     ]);
            //     $subaccount = json_decode($res->getBody(),true);
            //     dd($subaccount);
            // }

           return response()->json(['success' => TRUE, 'data'=> $Provider], 200);
        }

        catch (ModelNotFoundException $e) {
            
            return response()->json(['error' => 'Provider Not Found!'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        $this->validate($request, [
                'first_name' => 'max:255',
                'last_name' => 'max:255',
                'mobile' => 'between:6,13',
                'avatar' => 'mimes:jpeg,bmp,png',
                'address' => 'max:255',
            ]);

        try {

            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }

            $Provider = Auth::user();

            if($request->has('first_name')) 
                $Provider->first_name = $request->first_name;

            if($request->has('last_name')) 
                $Provider->last_name = $request->last_name;

            if ($request->has('mobile'))
                $Provider->mobile = $request->mobile;

            if ($request->has('country_code'))
                $Provider->country_code = $request->country_code;

            if ($request->has('description'))
                $Provider->description = $request->description;

            if ($request->hasFile('avatar')) {

                $name = $Provider->id."-profile-".str_replace(' ','_',Carbon::now());
                $baseurl = 'replace with actual profile url';                    
                $contents = file_get_contents($request->avatar);
                $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                $s3_url = $baseurl.'/'.$name;
                $Provider->avatar = $s3_url;            
            }

            if($request->has('fleet')){
                $Provider->fleet = $request->fleet;
            }

            if($request->has('android_app_version')){
                $Provider->android_app_version = $request->android_app_version;
            }  

            if($request->has('ios_app_version')){
                $Provider->ios_app_version = $request->ios_app_version;
            }            

            if($Provider->profile) {
                $Provider->profile->update([
                        'address' => $request->address ? : $Provider->profile->address,
                    ]);
            } else {
                ProviderProfile::create([
                        'provider_id' => $Provider->id,
                        'address' => $request->address,
                    ]);
            }

            if($request->has('zen_token')){
                $Provider->zen_token = $request->zen_token;
            }


            $Provider->save();

        //     if($Provider->device) {
        //     if($Provider->device->token != $request->device_token) {
        //         $Provider->device->update([
        //                 'udid' => $request->device_id,
        //                 'token' => $request->device_token,
        //                 'type' => $request->device_type,
        //             ]);
        //     }
        // } else {
        //     ProviderDevice::create([
        //             'provider_id' => $Provider->id,
        //             'udid' => $request->device_id,
        //             'token' => $request->device_token,
        //             'type' => $request->device_type,
        //         ]);
        // }

           return response()->json(['success' => TRUE, 'data'=> $Provider], 200);
        }

        catch (ModelNotFoundException $e) {
            
            return response()->json(['error' => 'Provider Not Found!'], 404);
        }
    }

    /**
     * Update latitude and longitude of the user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function location(Request $request)
    {
        $this->validate($request, [
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            $Provider =  Provider::find(Auth::user()->id);
        // if($Provider = \Auth::user()){

            $Provider->latitude = $request->latitude;
            $Provider->longitude = $request->longitude;
            $Provider->location_updated = Carbon::now();
            
            $Provider->save();

            return response()->json(['success' => TRUE,'message' => 'Location Updated successfully!'],200);

        // } else {
        //     return response()->json(['error' => 'Provider Not Found!']);
        // }
    }

    public function driver_cards(Request $request)
    {

        if($request->ajax()) {
            
            $Jobs = UserRequests::whereIn('status',['COMPLETED', 'CANCELLED'])
                                ->where('provider_id', Auth::user()->id)
                                ->orderBy('created_at','asc')
                                ->with('payment','service_type','user','rating','provider','provider_profiles')
                                ->get();
            $ScheduledJobs = UserRequests::where('status', 'SCHEDULED')
                                ->where('current_provider_id', Auth::user()->id)
                                ->orderBy('created_at','desc')
                                ->with('payment','service_type','user','rating','provider','provider_profiles')
                                ->first();
            
            if(!empty($ScheduledJobs)){
                $ScheduledJobs->type = "Scheduled";
            }
            else{
               $ScheduledJobs = (Object)[]; 
            }
            
            if(!empty($Jobs)){
                foreach ($Jobs as $key => $Job) {
               
                $service_type = ServiceType::findOrFail($Jobs[$key]->service_type_id);
                    $Jobs[$key]->static_map = "";
                        if($Jobs[$key]->provider_profiles->car_picture == ""){
                            $Jobs[$key]->provider_profiles->car_picture = $service_type->image;
                        }
                    if($Jobs[$key]->payment_mode == 'MOBILE'){
                            $Jobs[$key]->payment_image = asset('asset/img/mobile.png');
                        }
                        if($Jobs[$key]->payment_mode == 'CARD'){
                            $Jobs[$key]->payment_image = asset('asset/img/card.png');
                        }
                        if($Jobs[$key]->payment_mode == 'CASH'){
                            $Jobs[$key]->payment_image = asset('asset/img/cash.png');
                        }
                        $Jobs[$key]->type = "Last";
                    }
            }
            else{
                $Jobs = (Object)[];

            }

            $Provider = ProviderProfile::where('provider_id',Auth::user()->id)
                                            ->first();

                $week_earning = $week_tot = $week_com = $week_can = $month_earning = $month_tot = $month_com = $month_can = $today_earning = $today_tot = $today_com = $today_can = $total_earning = $total_tot = $total_com = $total_can = 0;
                $week = $month = $today = $total = array();
                $provider = Provider::where('id',Auth::user()->id)
                            ->with('service','accepted','cancelled')
                            ->get();

                $today = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::today())
                    ->get();

                $fully = UserRequests::where('provider_id',Auth::user()->id)
                            ->with('payment','service_type')
                             ->where('created_at', '>=', Carbon::now()->subMonth(30))
                            ->get();

                $weekly = UserRequests::where('provider_id',Auth::user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::now()->subWeekdays(7))
                    ->get();


                $total_req = UserRequests::where('provider_id',Auth::user()->id)
                            ->with('payment','service_type')
                            ->get();

               //Monthly Earnings

               for($i=0; $i < count($fully); $i++) {
                    $month_earning += ($fully[$i]['payment']['total']- $fully[$i]['payment']['commision']);
                    if($fully[$i]['status'] == 'COMPLETED'){
                        $month_com +=1;
                    }
                    if($fully[$i]['status'] == 'CANCELLED'){
                        $month_can +=1;
                    }
                    $month_tot = count($fully);
                }
               $month['earnings'] = number_format($month_earning, 2);
               $month['total_request'] = $month_tot;
               $month['completed_request'] = $month_com;
               $month['cancelled_request'] = $month_can;

               //Weekly Earnings

        for($i=0; $i < count($weekly); $i++) {
            $week_earning += ($weekly[$i]['payment']['total'] - $weekly[$i]['payment']['commision']);
            if($weekly[$i]['status'] == 'COMPLETED'){
                $week_com +=1;
            }
            if($weekly[$i]['status'] == 'CANCELLED'){
                $week_can +=1;
            }
            $week_tot = count($weekly);
        }
       $week['earnings'] = number_format($week_earning, 2);
       $week['total_request'] = $week_tot;
       $week['completed_request'] = $week_com;
       $week['cancelled_request'] = $week_can;

               //Total Earnings

               for($i=0; $i < count($total_req); $i++) {
                    $total_earning += ($total_req[$i]['payment']['total'] - $total_req[$i]['payment']['commision']);
                    if($total_req[$i]['status'] == 'COMPLETED'){
                        $total_com +=1;
                    }
                    if($total_req[$i]['status'] == 'CANCELLED'){
                        $total_can +=1;
                    }
                    $total_tot = count($total_req);
                }
               $total['earnings'] = number_format($total_earning, 2);
               $total['total_request'] = $total_tot;
               $total['completed_request'] = $total_com;
               $total['cancelled_request'] = $total_can;

               for($i=0; $i < count($today); $i++) {
            $today_earning += ($today[$i]['payment']['total']- $today[$i]['payment']['commision']);
            if($today[$i]['status'] == 'COMPLETED'){
                $today_com +=1;
            }
            if($today[$i]['status'] == 'CANCELLED'){
                $today_can +=1;
            }
            $today_tot = count($today);
        }
       $today['earnings'] = number_format($today_earning, 2);
       $today['total_request'] = $today_tot;
       $today['completed_request'] = $today_com;
       $today['cancelled_request'] = $today_can;


                $Provider = array(
                                'id' => $Provider->id,
                                'car_registration' => $Provider->car_registration,
                                'car_make' => $Provider->car_make,
                                'car_model' => $Provider->car_model,
                                'car_picture' => $Provider->car_picture,
                                'car_make_year' => $Provider->car_make_year,
                                'today_earnings' => number_format($today_earning, 2),
                                'today_requests' => $today_com,
                                'total_earning' => number_format($today_earning, 2),
                                'total_requests' => $today_tot,
                                'type' => 'Earnings',
                                );
                $Provider = Helper::null_safe($Provider);
                $Provider = array($Provider);

            return response()->json(['success' => TRUE, 'last_trip'=> $Jobs, 'schedule_trip' => $ScheduledJobs, 'earnings' => $Provider], 200);
        }

    }

    /**
     * Toggle service availability of the provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function available(Request $request)
    {
        $this->validate($request, [
                'service_status' => 'required|in:active,offline',
            ]);

            $day_start = Setting::get('day_start', '08:00').":00";
            $day_end = Setting::get('day_end', '18:00').":00";
            $break_time = Setting::get('driver_break_time','45');

            $drivenow_start = Setting::get('drivenow_start', '08:00').":00";
            $drivenow_end = Setting::get('drivenow_end', '18:00').":00";

            $global_engine = Setting::get('global_engine', 0);
            $tday = Carbon::today()->toDateString();

            $Provider = Provider::where('id',Auth::user()->id)->first();

            $breakHours = DriverActivity::where('driver_id', Auth::user()->id)->whereTime('start','>=',$drivenow_start)
                                            ->whereTime('end','<=',$drivenow_end)->whereDate('created_at', $tday)
                                            ->select([DB::raw("SUM(break_time) as breakHours")])->pluck('breakHours');

            $max_break_time = $break_time + ($break_time * (10/100) );
            $break_left = $break_time - $breakHours[0];

            if($request->service_status == "active"){
                $Driveractivity = DriverActivity::where('driver_id', Auth::user()->id)->where('is_active', 1)->first();
                $last_activity = DriverActivity::where('driver_id', Auth::user()->id)->where('is_active', 0)->orderBy('updated_at', 'desc')->first();
                
                    if($Provider->official_drivers == 1){
                        
                        //Turning On the engine 
                        $official_driver = OfficialDriver::where('driver_id', Auth::user()->id)->first();

                        if($global_engine == 1 && $official_driver->engine_control == 1 && $official_driver->imei_number != '' && $official_driver->day_off == 0 &&  $official_driver->engine_off_reason == 'Offline' && $Provider->availability ==0 && $official_driver->engine_status == 1){

                            //Giving special attention to the drivers who reached more than maximum no of offline hours

                            // if($breakHours[0] > $max_break_time){
                            //     $message = "Alert: Engine Switch-on failed. You have been offline for more than allowed duration. Please contact Eganow Office";
                            //      Log::info($message);
                            //      (new SendPushNotification)->DriverBreakTime(Auth::user()->id,$message);
                            // }else{
                                    if(count($Driveractivity) == 0){
                                        $Driveractivity = new DriverActivity;
                                        $Driveractivity->is_active = 1;
                                        $Driveractivity->driver_id = Auth::user()->id;
                                        $Driveractivity->start = Carbon::now();
                                        if($last_activity){
                                            if($last_activity->end != '' && $last_activity->break_time == ''){
                                                $now = Carbon::now();
                                                $min = $Driveractivity->start->diffInMinutes($last_activity->end, true);
                                                $last_activity->break_time = $min;
                                                $last_activity->save();
                                            }
                                        }
                                        $Driveractivity->save();
                                    }

                                    $Provider->available_on = Carbon::now();
                                    $Provider->availability = 1;
                                    $Provider->save();

                                    // Restoring the Engine for Going Online
                                    $tro_access_token = Setting::get('tro_access_token','');
                                    if($tro_access_token == ''){
                                        $time = Carbon::now()->timestamp;
                                        $account = "replace with actual account name";
                                        $password = "replace with account password";
                                        $signature = md5(md5($password).$time);

                                        $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                                        $token_json = curl($token_url);

                                        $token_details = json_decode($token_json, TRUE);

                                        $tro_access_token = $token_details['record']['access_token'];
                                        Setting::set('tro_access_token', $tro_access_token);
                                        Setting::save();
                                        Log::info("Tro Access Token Called");
                                    }
                                    if($tro_access_token !=''){
                                        $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->vehicle->imei."&command=RELAY,0";

                                        $json = curl($url);

                                        $details = json_decode($json, TRUE);

                                        if($details['code']== '10012'){
                                            $time = Carbon::now()->timestamp;
                                            $account = "replace with actual account name";
                                            $password = "replace with account password";
                                            $signature = md5(md5($password).$time);

                                            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                                            $token_json = curl($token_url);

                                            $token_details = json_decode($token_json, TRUE);

                                            $tro_access_token = $token_details['record']['access_token'];
                                            Setting::set('tro_access_token', $tro_access_token);
                                            Setting::save();
                                            Log::info("Tro Access Token Called");
                                            $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->vehicle->imei."&command=RELAY,0";

                                            $json = curl($url);

                                            $details = json_decode($json, TRUE);
                                        }

                                        $vehicle = DriveNowVehicle::where('imei',$official_driver->vehicle->imei)->first();

                                        if($vehicle->sim !=''){
                                            $mobile = $vehicle->sim;
                                            // if($mobile[0] == 0){
                                            //     $receiver = $mobile;
                                            // }else{
                                            //     $receiver = "0".$mobile; 
                                            // }
                                            $content = "*22*3#";
                                            if($mobile[0] == 0){
                                                $receiver = "233".substr($mobile,1);
                                            }else{
                                                $receiver = "233".$mobile;
                                            }
                                        $sendMessage = sendMessageRancard($receiver, $content);
                                            // $client = new \GuzzleHttp\Client();

                                            // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";
                                            // Log::info("Engine Block SMS: ". $url);
                                            // $headers = ['Content-Type' => 'application/json'];
                                            
                                            // $res = $client->get($url, ['headers' => $headers]);

                                            // $code = (string)$res->getBody();
                                            // $codeT = str_replace("\n","",$code);
                                            Log::info("Engine Restore SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                                        }
                                        $message = "Your vehicle has been reactivated. Contact Eganow driver support team if you have any issues.";

                                        (new SendPushNotification)->DriverEngineUpdate($Provider->id,$message);

                                        $official_driver->engine_restore_reason = 'Offline';
                                        $official_driver->engine_restore_on = Carbon::now();
                                        $official_driver->engine_restore_by = 0;
                                        $official_driver->engine_status = 0;
                                        $official_driver->save();

                                        //Send SMS Notification
                                        $content = "Your vehicle has been reactivated. Contact Eganow driver support team if you have any issues.";
                                        $mobile = $Provider->mobile;

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

                                        // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                                        // $headers = ['Content-Type' => 'application/json'];
                                        
                                        // $res = $client->get($url, ['headers' => $headers]);

                                        // $code = (string)$res->getBody();
                                        // $codeT = str_replace("\n","",$code);           
                                    }
                            // }
                        }else{
                            if(count($Driveractivity) == 0){
                                $Driveractivity = new DriverActivity;
                                $Driveractivity->is_active = 1;
                                $Driveractivity->driver_id = Auth::user()->id;
                                $Driveractivity->start = Carbon::now();
                                if($last_activity){

                                   if($last_activity->end != '' && $last_activity->break_time == ''){
                                    $now = Carbon::now();
                                        $min = $Driveractivity->start->diffInMinutes($last_activity->end, true);
                                        $last_activity->break_time = $min;
                                        $last_activity->save();
                                    } 
                                }
                                
                                $Driveractivity->save();
                            }

                            $Provider->available_on = Carbon::now();
                            $Provider->availability = 1;
                            $Provider->save();
                        }
                    }else{
                        if(count($Driveractivity) == 0){
                            $Driveractivity = new DriverActivity;
                            $Driveractivity->is_active = 1;
                            $Driveractivity->driver_id = Auth::user()->id;
                            $Driveractivity->start = Carbon::now();
                            
                            if($last_activity){
                               if($last_activity->end != '' && $last_activity->break_time == ''){
                                    $now = Carbon::now();
                                    $min = $Driveractivity->start->diffInMinutes($last_activity->end, true);
                                    $last_activity->break_time = $min;
                                    $last_activity->save();
                                } 
                            }
                            
                            $Driveractivity->save();
                        }

                        $Provider->available_on = Carbon::now();
                        $Provider->availability = 1;
                        $Provider->save();
                    }

            }else{
                $Driveractivity = DriverActivity::where('driver_id', Auth::user()->id)->where('is_active', 1)->first();
                if(count($Driveractivity) != 0){

                    $Driveractivity->is_active = 0;
                    $Driveractivity->driver_id = Auth::user()->id;
                    $Driveractivity->end = Carbon::now();
                    $min = $Driveractivity->end->diffInMinutes($Driveractivity->start, true);

                    $Driveractivity->working_time = $min;
                    $Driveractivity->save();
                    
                    $Provider->available_on = Carbon::now();
                    $Provider->availability = 0;
                    $Provider->save();
                    $break_left = $break_time - $breakHours[0];

                    if($Provider->official_drivers == 1){
                        
                        //Turning off the engine for driver going offline after break time
                        
                            $start = (int)substr($drivenow_start,'0','2');
                            $end = (int)substr($drivenow_end,'0','2');

                            $official_driver = OfficialDriver::where('driver_id', Auth::user()->id)->first();

                            if($global_engine == 1 && $official_driver->engine_control == 1 && $official_driver->imei_number != '' && $official_driver->day_off == 0 && (int)date('H') >= $start && (int)date('H') <= $end && $official_driver->engine_status != 1){


                                $offline_driver = Provider::where('id',$Provider->id)->where('availability', 0)->whereTime('available_on','>=',$drivenow_start)->whereDate('available_on', $tday)->first();

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

                                // if($break_left > 0){
                                //     $message = "Alert: You have ".$break_left." mins of allowed offline time remaining.";
                                //      Log::info($message);
                                //      (new SendPushNotification)->DriverBreakTime(Auth::user()->id,$message);
                                // }
                                // if($breakHours[0] >= $break_time){
                                if($total_offline >= $break_time){
                                    // Get Access Token of TroTro Tracker
                                    $time = Carbon::now()->timestamp;
                                    $account = "replace with actual account name";
                                    $password = "replace with account password";
                                    $signature = md5(md5($password).$time);

                                    $url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                                    $json = curl($url);

                                    $details = json_decode($json, TRUE);

                                    $tro_access_token = $details['record']['access_token'];
                                    if($tro_access_token !=''){
                                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->imei_number;

                                        $status_json = curl($status_url);

                                        $status_details = json_decode($status_json, TRUE);

                                        $car_speed = $status_details['record'][0]['speed'];
                                        $offline_status = $status_details['record'][0]['datastatus'];

                                        if($car_speed > 5){
                                            $message = "Alert: Engine switch-off pending. You have gone over your allowed offline time. Please go online now to reset";
                                            (new SendPushNotification)->DriverBreakTime(Auth::user()->id,$message);
                                            $content = urlencode("Alert: Engine switch-off pending. You have gone over your allowed offline time. Please go online now to reset");
                                            $mobile = $Provider->mobile;
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
                                        }else if($offline_status == 2){
                                            Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". Auth::user()->id ." )");
                                            //Turn off the Engine
                                             $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,1";

                                            $json = curl($url);

                                            $details = json_decode($json, TRUE);

                                            $official_driver->engine_off_reason = 'Offline';
                                            $official_driver->engine_off_on = Carbon::now();
                                            $official_driver->engine_off_by = 0;
                                            $official_driver->engine_status = 1;
                                            $official_driver->save();

                                            $message = "Alert: Vehicle deactivated. You have no offline hours remaining. Go online now to reactivate your vehicle.";
                                            Log::info($message);
                                            (new SendPushNotification)->DriverEngineUpdate(Auth::user()->id,$message);
                                            $content = urlencode("Alert: Vehicle deactivated. You have no offline hours remaining. Go online now to reactivate your vehicle.");
                                            $mobile = $Provider->mobile;
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
                                        }
                                    }

                                }
                            }
                    }
                }else{
                    
                    // $Driveractivity = new DriverActivity;
                    // $Driveractivity->is_active = 0;
                    // $Driveractivity->driver_id = Auth::user()->id;
                    // $Driveractivity->end = Carbon::now();
                    // $Driveractivity->start = $Provider->available_on;
                    // $min = $Driveractivity->end->diffInMinutes($Driveractivity->start, true);

                    // $Driveractivity->working_time = $min;
                    // $Driveractivity->save();

                    $Provider->available_on = Carbon::now();
                    $Provider->availability = 0;
                    $Provider->save();
                }
            }
            $activeHours = DriverActivity::where('driver_id', Auth::user()->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');

            if($activeHours){
                if($activeHours[0] >= 60){
                    $activeHours = $activeHours[0] / 60 ." Hrs";
                }else{
                    $activeHours = $activeHours[0] . " mins";
                }
                $Provider->activeHours = $activeHours;
            }
            

        if($Provider->service) {
            $ProviderServices = ProviderService::where('provider_id', $Provider->id)->get();
            foreach ($ProviderServices as $ProviderService) {
                $service = ProviderService::find($ProviderService->id);
                $service->status = $request->service_status;
                $service->save();
            }
            // $Provider->service->update(['status' => $request->service_status]);
        } else {
            return response()->json(['error' => 'You account has not been approved for driving']);
        }
        return response()->json(['success' => TRUE, 'data'=> $Provider], 200);
    }

    public function connectivity(Request $request)
    {
        $this->validate($request, [
                'status' => 'required',
            ]);

        $Provider = Auth::user();
        if($request->status == 0){
            $Provider->disconnected_on = Carbon::now();
        }else{
            $Provider->connected_on = Carbon::now();
        }
        $Provider->save();
        

        return response()->json(['success' => TRUE, 'data'=> $Provider], 200);
    }


    /**
     * Update password of the provider.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function password(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        // $this->validate($request, [
        //         'password' => 'required|confirmed',
        //         // 'password_old' => 'required',
        //     ]);

        if($request->password != $request->password_confirmation){
            return response()->json(['error' => TRUE, 'message' => trans('api.user.password_mismatch')], 200);
        }

        $Provider = \Auth::user();

        // if(password_verify($request->password_old, $Provider->password))
        // {
            $Provider->password = bcrypt($request->password);
            $Provider->save();

           return response()->json(['success' => TRUE, 'message'=> trans('api.user.password_updated')], 200);
        // } else {
        //     if($request->ajax()) {
        //         return response()->json(['success' => FALSE, 'message' => trans('api.user.incorrect_password')], 200);
        //     }else{
        //         return back()->with('flash_error', 'InCorrect Password');
        //     }
        // }
    }
}
