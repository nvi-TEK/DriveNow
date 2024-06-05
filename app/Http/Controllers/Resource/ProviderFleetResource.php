<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use DB;
use Exception;
use Auth;
use Log;

use App\Provider;
use App\Marketers;
use App\ServiceType;
use App\ProviderService;
use App\Fleet;
use App\Document;
use App\UserRequests;
use App\UserRequestPayment;
use App\Helpers\Helper;
use GuzzleHttp\Client;
use App\DriverSubaccount;
use App\ProviderProfile;
use App\DriverComments;
use App\DriverActivity;
use Carbon\Carbon;
use App\ProviderDocument;
use App\Bank;
use Storage;
use App\DriverRequestReceived;
use App\User;
use App\RaveTransaction;
use Setting;
use App\IndividualPush;


class ProviderFleetResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index()
    // {
       
    //     $providers = Provider::with('service','accepted','cancelled')
    //                 ->where('archive', '!=', '1')
    //                 ->where('fleet', Auth::user()->id )
    //                 ->orderBy('id', 'DESC')
    //                 ->paginate(300);
    //             for ($i=0; $i < count($providers); $i++) {                 
    //                 if($providers[$i]->marketer != 0){
    //                     $marketer = Marketers::find($providers[$i]->marketer);
    //                     $providers[$i]->marketer_name = $marketer->first_name .' '.$marketer->last_name;
    //                 }                
    //                 if($providers[$i]->fleet != 0){
    //                     $fleet = Fleet::find($providers[$i]->fleet);    
    //                     $providers[$i]->fleet_name = $fleet->name;
    //                 }
    //                 if(count($providers[$i]->service) !=0)
    //                 {
    //                     $online = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'offline')->where('status', '!=', 'riding')->get();
    //                     $riding = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'offline')->where('status', 'riding')->get();
    //                     $offline = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'active')->where('status', '!=', 'riding')->get();
    //                     if(count($offline) != 0 && count($online) == 0){
    //                         $providers[$i]->online = "Offline";
    //                     }
    //                     else if(count($online) != 0 && count($riding) == 0){
    //                         $providers[$i]->online = "Online";
    //                     }
    //                     else if(count($riding) != 0 && count($online) != 0){
    //                         $providers[$i]->online = "In Service";
    //                     }
    //                 }
                    
    //                 $providers[$i]->total_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','COMPLETED')->count();

    //                 $providers[$i]->accepted_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','!=','COMPLETED')->count();

    //                  $providers[$i]->cancelled_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','CANCELLED')->count();

    //             }
    //             $page = 'List Drivers';
    //             $approved_drivers = Provider::where('archive', '!=', 1)->where('status','approved')->where('fleet', Auth::user()->id )->count();
    //             $online_drivers = Provider::where('archive', '!=', 1)->where('status','approved')->where('availability', 1)->where('fleet', Auth::user()->id )->count();
    //             $total_drivers = Provider::where('archive', '!=', 1)->where('fleet', Auth::user()->id )->count();
    //             $document = Document::all()->count();$document = Document::all()->count();

    //     return view('fleet.providers.index', compact('providers','document','approved_drivers','online_drivers', 'total_drivers'));
    // }

        public function index(Request $request)
    {
        try {
            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)
                        ->where('fleet', Auth::user()->id )
                        ->orderBy('created_at', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->where('fleet', Auth::user()->id )->count();
            $offline_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->where('fleet', Auth::user()->id )->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
                
            if(request()->has('marketer')){
                $providers = $AllProviders->where('marketer',$request->marketer)->get();                
                $marketer = Marketers::find($request->marketer);
                for ($i=0; $i < count($providers); $i++) { 
                    if($providers[$i]->marketer != 0){
                        $marketer = Marketers::find($request->marketer);
                        $providers[$i]->marketer_name = $marketer->first_name .' '.$marketer->last_name;
                    }
                    if($providers[$i]->fleet != 0){
                        $fleet = Fleet::find($providers[$i]->fleet);
                        $providers[$i]->fleet_name = $fleet->name;
                    }
                    $providers[$i]->total_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','COMPLETED')->count();
                }
                $approved_drivers = Provider::where('status','approved')->where('marketer',$request->marketer)->where('archive', '!=', 1)->count();
                $online_drivers = Provider::where('availability', 1)->where('marketer',$request->marketer)->where('archive', '!=', 1)->count();
                $total_drivers = Provider::where('marketer',$request->marketer)->where('archive', '!=', 1)->count();

                $drivers = Provider::where('marketer',$request->marketer)->where('archive', '!=', 1)->pluck('id');
                $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();  
                
                $page = 'Drivers referred by '. $marketer->first_name .' '.$marketer->last_name;
            }
            else if(request()->has('filter')){

                if ($request->filter == 1) {
                    $page = 'List of Approved Drivers';
                    $providers = $AllProviders->where('status','approved')->paginate(300);
                }else if($request->filter == 2){
                    $page = 'List of Online Drivers';
                    $providers = $AllProviders->where('status','approved')->where('availability', 1)->paginate(300);
                }else if($request->filter == 3){
                    $page = 'List of Offline Drivers';
                    $providers = $AllProviders->where('status','approved')->where('availability', 0)->paginate(300);
                }else if($request->filter == 4){
                    $driverComments = DriverComments::whereHas('provider')->groupBy('driver_id')->distinct()->pluck('driver_id');
                    $page = 'List of Contacted Drivers';
                    $providers = $AllProviders->whereIn('id', $driverComments)->paginate(300);
                    $contacted_drivers = DriverComments::whereIn('driver_id',$AllProviders->pluck('id'))->groupBy('driver_id')->distinct()->paginate(300);
                }else{
                    $page = 'List of Drivers';
                    $providers = $AllProviders->paginate(300);
                }
                

                for ($i=0; $i < count($providers); $i++) {                 
                    if($providers[$i]->marketer != 0){
                        $marketer = Marketers::find($providers[$i]->marketer);
                        $providers[$i]->marketer_name = $marketer->first_name .' '.$marketer->last_name;
                    }                
                    if($providers[$i]->fleet != 0){
                        $fleet = Fleet::find($providers[$i]->fleet); 
                        if(count($fleet) != 0){
                            $providers[$i]->fleet_name = $fleet->name;
                        } else{
                            $providers[$i]->fleet_name = "N / A";
                        }
                        
                    }
                    if(count($providers[$i]->service) !=0)
                    {
                        $online = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'offline')->where('status', '!=', 'riding')->get();
                        $riding = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'offline')->where('status', 'riding')->get();
                        $offline = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'active')->where('status', '!=', 'riding')->get();
                        if(count($offline) != 0 && count($online) == 0){
                            $providers[$i]->online = "Offline";
                        }
                        else if(count($online) != 0 && count($riding) == 0){
                            $providers[$i]->online = "Online";
                        }
                        else if(count($riding) != 0 && count($online) != 0){
                            $providers[$i]->online = "In Service";
                        }
                    }
                    
                    $providers[$i]->total_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','COMPLETED')->count();

                }
                
            }else if($request->has('search')){

                $offline = $online = $riding = array();
                $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                            ->where('archive', '!=', 1)
                            ->where('fleet', Auth::user()->id )
                            ->orderBy('created_at', 'DESC');

                $approved_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('archive', '!=', 1)->count();
                $online_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('availability', 1)->where('archive', '!=', 1)->count();
                $total_drivers = Provider::all()->where('archive', '!=', 1)->where('fleet', Auth::user()->id )->count();
                $offline_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('availability', 0)->where('archive', '!=', 1)->count();
                $drivers = Provider::where('archive', '!=', 1)->where('fleet', Auth::user()->id )->pluck('id');
                $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
                
                
                if($request->has('search')){
                    $page = 'Search result for "'.$request->search .'"';
                    $search = $request->search;
                    $providers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                            ->where('archive', '!=', 1)
                            ->where('fleet', Auth::user()->id )->where(function($q) use ($search) { $q->where('first_name','like', '%'.$search.'%')->orwhere('email','like', '%'.$search.'%')->orwhere('mobile','like', '%'.$search.'%');
                                    })->get();
                }
                
                $document = Document::all()->count();
                $fleet = Auth::user();
                $recent_documents = ProviderDocument::whereHas('provider', function($query) use ($fleet) {
                        $query->where('fleet','=', $fleet->id);})->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
                for ($i=0; $i < count($providers) ; $i++) { 
                    $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                    if($activeHours[0] > 0){ 

                        if($activeHours[0] >= 60){
                            $activeHour = $activeHours[0] / 60 ." Hrs";
                        }else{
                            $activeHour = $activeHours[0] . " mins";
                        }

                        $providers[$i]->activeHoursFormat = $activeHour;
                        $providers[$i]->activeHours = $activeHours[0] / 60;
                    }else{
                        $providers[$i]->activeHoursFormat = "N / A";
                        $providers[$i]->activeHours = 0;
                    }

                        
                }
                
                return view('fleet.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents'));

            } else if(request()->has('filter_date')){
                        $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                        $providers = $AllProviders->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        $page = "List of drivers registered from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
                }
            else{
                $providers = $AllProviders->paginate(300);

                for ($i=0; $i < count($providers); $i++) {                 
                    if($providers[$i]->marketer != 0){
                        $marketer = Marketers::find($providers[$i]->marketer);
                        $providers[$i]->marketer_name = $marketer->first_name .' '.$marketer->last_name;
                    }                
                    if($providers[$i]->fleet != 0){
                        $fleet = Fleet::find($providers[$i]->fleet); 
                        if(count($fleet) != 0){
                            $providers[$i]->fleet_name = $fleet->name;
                        } else{
                            $providers[$i]->fleet_name = "N / A";
                        }
                        
                    }
                    if(count($providers[$i]->service) !=0)
                    {
                        $online = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'offline')->where('status', '!=', 'riding')->get();
                        $riding = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'offline')->where('status', 'riding')->get();
                        $offline = ProviderService::where('provider_id', $providers[$i]->id)->where('status', '!=', 'active')->where('status', '!=', 'riding')->get();
                        if(count($offline) != 0 && count($online) == 0){
                            $providers[$i]->online = "Offline";
                        }
                        else if(count($online) != 0 && count($riding) == 0){
                            $providers[$i]->online = "Online";
                        }
                        else if(count($riding) != 0 && count($online) != 0){
                            $providers[$i]->online = "In Service";
                        }
                    }
                    
                    $providers[$i]->total_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','COMPLETED')->count();

                }
                $page = 'List Drivers';
            }  
            for ($i=0; $i < count($providers) ; $i++) { 
                $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                if($activeHours[0] > 0){ 

                     if($activeHours[0] >= 60){
                            $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $activeHour = number_format($activeHours[0], 2) . " mins";
                        }

                    $providers[$i]->activeHoursFormat = $activeHour;
                    $providers[$i]->activeHours = $activeHours[0] / 60;
                }else{
                    $providers[$i]->activeHoursFormat = "N / A";
                    $providers[$i]->activeHours = 0;
                }

                        
            }
            
            $document = Document::all()->count();

                $fleet = Auth::user();
                $recent_documents = ProviderDocument::whereHas('provider', function($query) use ($fleet) {
                        $query->where('fleet', $fleet->id);
                    })->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('fleet.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents'));
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        // $client = new Client(['http_errors' => false]);
        // $url = 'https://api.ravepay.co/v2/banks/NG?public_key='.env('RAVE_PUBLIC_KEY');
        // $res = $client->get($url);
        // $banks = json_decode($res->getBody(), true); 
        $banks = Bank::all();                     
        return view('fleet.providers.create', compact('banks'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|unique:providers,email|email|max:255',
            'mobile' => 'between:6,13',
            'avatar' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            // 'password' => 'required|min:6|confirmed',
            'acc_no' => 'required|numeric',
            'acc_name' => 'required',
            'bank_name' => 'required',
            // 'bankcountry' => 'required',
            'bank_code' => 'required',
        ]);

        try{
            // $client = new Client(['http_errors' => false]);
            // $url ="https://api.ravepay.co/v2/gpx/subaccounts/create";
            // $headers = [
            //     'Content-Type' => 'application/json',
            // ];
            // $body = [
            //             "account_bank"              =>  $request->bank_code,
            //             "account_number"            =>  $request->acc_no, 
            //             "business_name"             =>  $request->first_name,
            //             "business_email"            =>  $request->email,
            //             "business_contact"          =>  $request->first_name,
            //             "business_contact_mobile"   =>  $request->mobile,
            //             "business_mobile"           =>  $request->mobile,
            //             "country"                   =>  $request->bankcountry,
            //             "meta"                      =>  ["metaname" => "MarketplaceID", "metavalue" => "ggs-920900"],
            //             "seckey"                    =>  env("RAVE_SECRET_KEY")
            //         ];            
            // $res = $client->post($url, [
            //     'headers' => $headers,
            //     'body' => json_encode($body),
            // ]);
            // $subaccount = json_decode($res->getBody(),true);
            // \Log::info($subaccount);
            // if($subaccount['status'] == 'success')
            // {
                if($request->mobile[0] == "0"){
                    $request->mobile = ltrim($request->mobile, 0);
                }

                $provider = $request->all();
                $request->country_code = "233";
                $otp = rand(100000, 999999);
                $provider['password'] = bcrypt($otp);
                $to = $request->mobile;
                $to = str_replace(" ", "", $to);
                $cc = $request->country_code;
                $from = "Eganow";
            if(str_contains($cc,"23") == true){
                $content = urlencode("Eganow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on Eganow, Drive for your future.");
                $clientId = env("HUBTEL_API_KEY");
                $clientSecret = env("HUBTEL_API_SECRET");

                $rec = $cc.$to;

                $sendSms = sendSMS($from, $rec, $content, $clientId, $clientSecret);
                if(count($sendSms) == 1 || $sendSms == FALSE){
                    $content = urlencode("Eganow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on Eganow, Drive for your future.");
                    $mobile = $provider->mobile;
                    if($mobile[0] == 0){
                        $receiver = $mobile;
                    }else{
                        $receiver = "0".$mobile; 
                    }

                    $client = new \GuzzleHttp\Client();

                    $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";
                    Log::info($url);

                    $headers = ['Content-Type' => 'application/json'];
                    
                    $res = $client->get($url, ['headers' => $headers]);

                    $code = (string)$res->getBody();
                    $codeT = str_replace("\n","",$code);
                
                    if($codeT != "000"){
                        
                        $rec = $cc.$to;
                        $content = "Eganow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on Eganow, Drive for your future.";
                        $sendTwilio = sendMessageTwilio($to, $content);
                        //Log::info($sendTwilio);
                    }
                }
                
                
            }
            else{
                
                $rec = $cc.$to;               
                $content = "Eganow Driver: Use this temporary code as your password to login and change password: ".$otp.". Drive on Eganow, Drive for your future.";
                $sendTwilio = sendMessageTwilio($to, $content);
                Log::info($sendTwilio);
            }

                $provider['fleet'] = Auth::user()->id;
               
                if ($request->hasFile('avatar')) {
                    $name = $request->first_name."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual asset url';                    
                    $contents = file_get_contents($request->avatar);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $provider['avatar'] = $s3_url;
                }
                $provider = Provider::create($provider);

                $Provider = ProviderProfile::where('provider_id', $provider->id)->first();

                if(!$Provider){
                    $Provider = new ProviderProfile;
                    $Provider->provider_id = $provider->id;
                }
                if($request->has('acc_no')) 
                    $Provider->acc_no = $request->acc_no;

                if($request->has('acc_name')) 
                    $Provider->acc_name = $request->acc_name;

                if ($request->has('bank_name'))
                    $Provider->bank_name = $request->bank_name;

                if ($request->has('bank_name_id'))
                    $Provider->bank_name_id = $request->bank_name_id;

                if ($request->has('bank_code'))
                    $Provider->bank_code = $request->bank_code;

                if ($request->has('dl_no'))
                    $Provider->dl_no = $request->dl_no;

                if ($request->has('dl_exp'))
                    $Provider->dl_exp = date('Y-m-d h:i:s', strtotime($request->dl_exp));

                if ($request->has('dl_country'))
                    $Provider->dl_country = $request->dl_country;

                if ($request->has('dl_state'))
                    $Provider->dl_state = $request->dl_state;

                if ($request->has('dl_city'))
                    $Provider->dl_city = $request->dl_city;


                if ($request->has('wallet_balance')){
                    $provider->wallet_balance = $request->wallet_balance;
                }else{
                    $provider->wallet_balance = 0;   
                }
                
                $Provider->save();

                // $split                  = new DriverSubaccount;
                // $split->driver_id       = $provider->id;
                // $split->split_id        = $subaccount['data']['id'];
                // $split->account_number  = $subaccount['data']['account_number'];
                // $split->bank_code       = $subaccount['data']['account_bank'];
                // $split->business_name   = $subaccount['data']['business_name'];
                // $split->fullname        = $subaccount['data']['fullname'];
                // $split->date_created    = $subaccount['data']['date_created'];
                // $split->account_id      = $subaccount['data']['account_id'];
                // $split->split_ratio     = $subaccount['data']['split_ratio'];
                // $split->split_type      = $subaccount['data']['split_type'];
                // $split->split_value     = $subaccount['data']['split_value'];
                // $split->subaccount_id   = $subaccount['data']['subaccount_id'];
                // $split->bank_name       = $subaccount['data']['bank_name'];
                // $split->country         = $subaccount['data']['country'];
                // $split->save();

                return back()->with('flash_success','Driver Details Saved Successfully');
            // }else{
            //     $client = new Client(['http_errors' => false]);
            //     $url ="https://api.ravepay.co/v2/gpx/subaccounts/delete";
            //     $headers = [
            //         'Content-Type' => 'application/json',
            //     ];
            //     $body = ['id' => $subaccount['data']['id'], 'seckey' => env("RAVE_SECRET_KEY")];
            //     $res = $client->post($url, [
            //         'headers' => $headers,
            //         'body' => json_encode($body),
            //     ]);
            //     $delete = json_decode($res->getBody(),true);
            //     \Log::info($subaccount['message']);
            //     return back()->with('flash_error', $subaccount['message']);
            // }



        } 

        catch (Exception $e) {
            return $e->getMessage();
            return back()->with('flash_error', 'Driver Not Found');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {   
        try {
            $provider = Provider::with('trips')->where('id', $id)->first();
            $requests = UserRequests::where('provider_id', $id)->with('payment')->orderBy('created_at', 'desc')->get();
            $trip_earning = $earnings = $total_request = $completed_request = $cancelled_request = $trip_tot = $trip_com = $trip_can = 0;
            if(count($requests) > 0) {
                for($i=0; $i < count($requests); $i++) {
                    $trip_earning += ($requests[$i]['payment']['total']- $requests[$i]['payment']['commision']);
                    if($requests[$i]['status'] == 'COMPLETED'){
                        $trip_com +=1;
                    }
                    if($requests[$i]['status'] == 'CANCELLED'){
                        $trip_can +=1;
                    }
                       
                }
                $trip_tot = count($requests);
                $earnings = round($trip_earning);
                $total_request = $trip_tot;
                $completed_request = $trip_com;
                $cancelled_request = $trip_can;

            }
            $missed_rides = DriverRequestReceived::where('provider_id', $provider->id)->where('status',0)->count();
            $rejected_rides = DriverRequestReceived::where('provider_id', $provider->id)->where('status',2)->count();
            $user_referral = User::where('driver_referred', $provider->referal)->count();
            $driver_referral = Provider::where('driver_referred', $provider->referal)->count();
            $document = Document::all()->count();
            $driverComments = DriverComments::where('driver_id', $id)->with('provider','moderator')->orderBy('created_at', 'desc')->get();
            for($i = 0; $i < count($driverComments); $i++){
                $driverComments[$i]->posts = DriverComments::where('marketer_id',$driverComments[$i]->moderator->id)->count();
            }
            // $moderator_posts = DriverComments::where('marketer_id', Auth::guard('admin')->user()->id)->count();

            $driver = Provider::find($id);
            
            $user = Provider::find($provider->id);
            $credit_pending_transactions = RaveTransaction::where('driver_id', $provider->id)->where('status', 2)->where('type', 'credit')->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                $payToken = $credit_pending_transaction->rave_ref_id;

                $client1 = new \GuzzleHttp\Client();
                $headers = ['Content-Type' => 'application/json'];

                $status_url = "https://app.slydepay.com/api/merchant/invoice/checkstatus";
                $status = $client1->post($status_url, [ 
                    'headers' => $headers,
                    'json' => ["emailOrMobileNumber"=>"replace with actual payment emaol",
                                "merchantKey"=>"replace with actual merchant key",
                                "payToken"=>$payToken,
                                "confirmTransaction" => true]]);

                $result = array();
                $result = json_decode($status->getBody(),'true');
                Log::info("Driver Wallet balance status: ". $payToken." - ". $result['result']);
                if($result['success'] == TRUE && $result['result'] == "CONFIRMED"){

                    $credit_pending_transaction->last_balance = $user->wallet_balance;
                    $user = Provider::find($provider->id);
                    $user->wallet_balance += $credit_pending_transaction->amount;
                    $user->save();
                    $credit_pending_transaction->narration = "Wallet Topup";
                    $credit_pending_transaction->status = 1;
                    $credit_pending_transaction->save();
                }else if($result['success'] == TRUE && $result['result'] == "CANCELLED"){
                    $credit_pending_transaction->status = 0;
                    $credit_pending_transaction->narration = "Wallet topup failed";
                }else if($result['success'] == TRUE && $result['result'] == "PENDING"){
                    $credit_pending_transaction->status = 2;
                    $credit_pending_transaction->narration = "Wallet topup Pending";
                }
                $credit_pending_transaction->save();
            }
        $user = Provider::find($provider->id);

        $transactions = RaveTransaction::where('driver_id', $provider->id)->orderBy('created_at', 'desc')->get();


        $available_balance_duration = Setting::get('available_balance_time', '24');
        $credit = $debit = 0;
        $driver = Provider::find($provider->id);

        //Transactions for available balance calculation
        $available_transactions = RaveTransaction::where('driver_id', $provider->id)->where('status', 1)->where('credit', '!=', 1)->where('created_at', '<=', Carbon::now()->subHours($available_balance_duration))->orderBy('created_at', 'desc')->get();

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

        $transactions = RaveTransaction::where('driver_id', $provider->id)->orderBy('created_at', 'desc')->paginate(300);
        $requests = UserRequests::where('provider_id', $id)->with('payment')->orderBy('created_at', 'desc')->paginate(300);

            $custom_pushes = IndividualPush::where('driver_id', $provider->id)->with('moderator')->orderBy('created_at', 'desc')->get();
            
            return view('fleet.providers.provider-details', compact('provider', 'earnings', 'document', 'requests', 'total_request', 'completed_request', 'cancelled_request','driverComments','user_referral', 'driver_referral', 'missed_rides','rejected_rides','custom_pushes', 'transactions'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $provider = Provider::with('provider_subaccount')->findOrFail($id);
                                 
            return view('fleet.providers.edit',compact('provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // $this->validate($request, [
        //     'first_name' => 'required|max:255',
        //     'last_name' => 'required|max:255',
        //     'mobile' => 'between:6,13',
        //     'avatar' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        //     'acc_no' => 'required|numeric',
        //     'acc_name' => 'required',
        //     'bank_name' => 'required',
        //     'bankcountry' => 'required',
        //     'bank_code' => 'required',
        // ]);

        try {
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }

            $provider = Provider::with('provider_subaccount')->findOrFail($id);            
            if($provider->bank_code != $request->bank_code && $provider->acc_no != $request->acc_no)
            {
                if(!empty($provider->provider_subaccount))
                {
                    $client = new Client(['http_errors' => false]);
                    $url ="https://api.ravepay.co/v2/gpx/subaccounts/edit";
                    $headers = [
                        'Content-Type' => 'application/json',
                    ];
                    $body = [
                                "id"                        =>  $provider->provider_subaccount->split_id,
                                "account_bank"              =>  $request->bank_code,
                                "account_number"            =>  $request->acc_no, 
                                "business_name"             =>  $provider->first_name,
                                "business_email"            =>  $provider->email,                            
                                "seckey"                    =>  env("RAVE_SECRET_KEY")
                            ];            
                    $res = $client->post($url, [
                        'headers' => $headers,
                        'body' => json_encode($body),
                    ]);
                    $subaccount = json_decode($res->getBody(),true);
                } 
                $subaccount = DriverSubaccount::where('driver_id',$provider->id)->first();
                $subaccount->account_number = $request->acc_no;
                $subaccount->bank_code = $request->bank_code;
                $subaccount->save();               
            }

             if ($request->hasFile('avatar')) {
                $provider->avatar = Helper::upload_picture($request->avatar);
            }
            
            if ($request->has('wallet_balance')){
                $provider->wallet_balance += $request->wallet_balance;
            }

            if($request->has('first_name')) 
            $provider->first_name = $request->first_name;

            if($request->has('last_name')) 
            $provider->last_name = $request->last_name;

            // if ($request->has('fleet')){
            //     $provider->fleet = $request->fleet;
            // }
            // else{
            //     $provider->fleet = 0;
            // }
            if($request->has('mobile')) 
            $provider->mobile = $request->mobile;

            $provider->save();

            $Provider = ProviderProfile::where('provider_id', $id)->first();

            if(!$Provider){
                $Provider = new ProviderProfile;
                $Provider->provider_id = $id;
            }
            if($request->has('acc_no')) 
                $Provider->acc_no = $request->acc_no;

            if($request->has('acc_name')) 
                $Provider->acc_name = $request->acc_name;

            if ($request->has('bank_name'))
                $Provider->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $Provider->bank_name_id = $request->bank_name_id;

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

            if ($request->has('car_registration'))
                $Provider->car_registration = $request->car_registration;

            if ($request->has('car_make'))
                $Provider->car_make = $request->car_make;

            if ($request->has('car_model'))
                $Provider->car_model = $request->car_model;

            if ($request->hasFile('car_picture'))
                $Provider->car_picture = Helper::upload_picture($request->car_picture);

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

            

            return redirect()->back()->with('flash_success', 'Provider Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Provider Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Provider::find($id)->delete();
            $user = Provider::find($id);
            $user->email = $user->email . $user->id;
            $user->archive = 1;
            $user->save();
            return back()->with('flash_success', 'Driver deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Driver Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function approve($id)
    {
        try {
            $Provider = Provider::findOrFail($id);
            if($Provider->service) {
                $Provider->update(['status' => 'approved']);
                return back()->with('flash_success', "Driver Approved");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver has not been assigned a service type!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function disapprove($id)
    {
        Provider::where('id',$id)->update(['status' => 'banned']);
        return back()->with('flash_success', "Driver Disapproved");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function request($id){

        try{

            $requests = UserRequests::where('user_requests.provider_id',$id)
                    ->RequestHistory()
                    ->get();

            return view('fleet.request.index', compact('requests'));
        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function statement($id){

        try{

            $requests = UserRequests::where('provider_id',$id)
                        ->where('status','COMPLETED')
                        ->with('payment')
                        ->get();

            $rides = UserRequests::where('provider_id',$id)->with('payment')->orderBy('id','desc')->paginate(10);
            $cancel_rides = UserRequests::where('status','CANCELLED')->where('provider_id',$id)->count();
            $Provider = Provider::find($id);
            $revenue = UserRequestPayment::whereHas('request', function($query) use($id) {
                                    $query->where('provider_id', $id );
                                })->select(\DB::raw(
                                   'SUM(ROUND(total)) as overall, SUM(ROUND(drivercommision)) as commission' 
                               ))->get();


            $Joined = $Provider->created_at ? '- Joined '.$Provider->created_at->diffForHumans() : '';

            return view('fleet.statement.statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->first_name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function getBanks(Request $request){
        try {            
            $client = new Client(['http_errors' => false]);
            $url = 'https://api.ravepay.co/v2/banks/'.$request->country.'?public_key='.env('RAVE_PUBLIC_KEY');
            $res = $client->get($url);
            $banks = json_decode($res->getBody(), true);            
            $html = '';
            $html .= '<option value="">Select Bank</option>';
            if(!empty($banks))
            {
                if($banks['status'] == 'success'){
                    foreach($banks['data']['Banks'] as $bank)
                    {
                        $html .= '<option value="'.$bank['Code'].'" data-name="'.$bank['Name'].'">'.$bank['Name'].'</option>';
                    }
                }                
            }            
            return $html;                                        
        } catch (\Throwable $th) {  
            return $th->getMessage();            
            $banks = [];
            return $banks;
        }
    }

    public function bank($id)
    {
        try {
            $provider = Provider::with('provider_subaccount')->findOrFail($id);
            $fleet = Fleet::all();
            $banks = Bank::all();                       
            return view('fleet.providers.bank-details',compact('provider', 'fleet','banks'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function license($id)
    {
        try {
            $provider = Provider::with('provider_subaccount')->findOrFail($id);
                                  
            return view('fleet.providers.license-details',compact('provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function vehicle($id)
    {
        try {
            $provider = Provider::with('provider_subaccount')->findOrFail($id);
            return view('fleet.providers.vehicle-details',compact('provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function recent_uploaded(){
        try {
            $fleet = Auth::user();
                $recent_documents = ProviderDocument::whereHas('provider', function($query) use ($fleet) {
                        $query->where('fleet','=', $fleet->id);
                    })->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)
                        ->where('fleet', Auth::user()->id )
                        ->orderBy('upload_notify', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->where('fleet', Auth::user()->id )->count();
            $offline_drivers = Provider::where('status','approved')->where('fleet', Auth::user()->id )->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->where('fleet', Auth::user()->id )->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();

            return view('fleet.providers.document_uploaded', compact('recent_documents','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers'));
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }        

    }

    public function online_drivers(Request $request)
    {
        try {

            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('availability', 1)
                        ->orderBy('upload_notify', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $providers = $AllProviders->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%')->get();
            }else{
                $page = 'List of Online Drivers';
                $providers = $AllProviders->paginate(300);
            }
            
            $document = Document::all()->count();

           $fleet = Auth::user();
                $recent_documents = ProviderDocument::whereHas('provider', function($query) use ($fleet) {
                        $query->where('fleet','<>', $fleet->id);
                    })->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();

            for ($i=0; $i < count($providers) ; $i++) { 
                $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                if($activeHours[0] > 0){ 

                     if($activeHours[0] >= 60){
                            $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $activeHour = number_format($activeHours[0], 2) . " mins";
                        }

                    $providers[$i]->activeHoursFormat = $activeHour;
                    $providers[$i]->activeHours = $activeHours[0] / 60;
                }else{
                    $providers[$i]->activeHoursFormat = "N / A";
                    $providers[$i]->activeHours = 0;
                }

                        
            }
            
            return view('fleet.providers.online', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function offline_drivers(Request $request)
    {
        try {
            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('availability', 0)
                        ->orderBy('created_at', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
            
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $providers = $AllProviders->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%')->get();
            }else{
                $page = 'List of Offline Drivers';
                $providers = $AllProviders->paginate(300);
            }
            
            $document = Document::all()->count();

            $fleet = Auth::user();
                $recent_documents = ProviderDocument::whereHas('provider', function($query) use ($fleet) {
                        $query->where('fleet','<>', $fleet->id);
                    })->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();

            for ($i=0; $i < count($providers) ; $i++) { 
                $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                if($activeHours[0] > 0){ 

                     if($activeHours[0] >= 60){
                            $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $activeHour = number_format($activeHours[0], 2) . " mins";
                        }

                    $providers[$i]->activeHoursFormat = $activeHour;
                    $providers[$i]->activeHours = $activeHours[0] / 60;
                }else{
                    $providers[$i]->activeHoursFormat = "N / A";
                    $providers[$i]->activeHours = 0;
                }

                        
            }
            
            return view('fleet.providers.offline', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function search_drivers(Request $request)
    {
        try {
            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)
                        ->orderBy('created_at', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
            
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $providers = $AllProviders->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%')->get();
            }
            
            $document = Document::all()->count();

            $fleet = Auth::user();
                $recent_documents = ProviderDocument::whereHas('provider', function($query) use ($fleet) {
                        $query->where('fleet','<>', $fleet->id);
                    })->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('fleet.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }
}
