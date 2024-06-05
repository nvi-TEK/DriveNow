<?php

namespace App\Http\Controllers\Resource;

use App\Fleet;
use App\Provider;
use App\Bank;
use Auth;
use App\ProviderProfile;
use App\ProviderDevice;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Exception;
use Setting;
use Log;
use App\FleetSubaccount;
use GuzzleHttp\Client;

class FleetResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $fleets = Fleet::orderBy('created_at' , 'desc')->where('roles', '0')->get();
        return view('admin.fleet.index', compact('fleets'));
    }

    public function fleet_driver(){

        try{


            $fleets = Fleet::orderBy('created_at' , 'desc')->where('roles', '0')->get();

            foreach ($fleets as $fleet) {
                
                $provider = Provider::where('email', $fleet->email)->first();

                if(!$provider){
                    $provider = new Provider;
                }
                

                $provider->first_name = $fleet->company;

                $provider->password = $fleet->password;

                $provider->last_name = '';

                $provider->email = $fleet->email;

                $provider->wallet_balance = 0;

                $provider->fleet = $fleet->id;

                $provider->latitude = $fleet->latitude;

                $provider->longitude = $fleet->longitude;

                $provider->fleet_driver = 1;
            
                $provider->mobile = $fleet->mobile;
                
                $provider->country_code = "+233";
                
                $provider->status = "approved";

                $provider->approved_by = Auth::guard('admin')->user()->id;

                $provider->save();

                $provider->avatar = $fleet->logo;;   

                $provider->save();

                $Provider = ProviderProfile::where('provider_id', $provider->id)->first();

                 ProviderDevice::create([
                        'provider_id' => $provider->id,
                        'udid' => 'testing',
                        'token' => 'testing',
                        'type' => 'android',
                    ]);

                    if(!$Provider){
                        $Provider = new ProviderProfile;
                        $Provider->provider_id = $provider->id;
                    }
                   
                    $Provider->acc_no = $fleet->acc_no;

                    $Provider->acc_name = $fleet->acc_name;

                    $Provider->bank_name = $fleet->bank_name;

                    $Provider->bank_name_id = $fleet->bank_name_id;

                    $Provider->bank_code = $fleet->bank_code;

                    $Provider->dl_no = $fleet->dl_no;

                    $Provider->dl_exp = $fleet->dl_exp;

                    $Provider->dl_country = $fleet->dl_country;

                    $Provider->dl_state = $fleet->dl_state;

                    $Provider->dl_city = $fleet->dl_city;

                    $Provider->car_registration = 'Assigning...';

                    $Provider->car_make = $fleet->car_make;

                    $Provider->car_model = $fleet->car_model;

                    $Provider->car_picture = $fleet->logo;
            

                    $Provider->mileage = $fleet->mileage;

                    $Provider->car_make_year = $fleet->car_make_year;

                    $Provider->road_worthy_expire = $fleet->road_worthy_expire;

                    $Provider->insurance_type = $fleet->insurance_type;

                    $Provider->insurance_expire = $fleet->insurance_expire;            
                    
                    $Provider->save(); 

            }
            return view('admin.fleet.index', compact('fleets'));
        }catch(Exception $e){
            Log::info($e);
            return view('admin.fleet.index', compact('fleets'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {        
        try {
            $banks = Bank::all();     
            return view('admin.fleet.create', compact('banks'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
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
        $this->validate($request, [
            'name' => 'required|max:255',
            'company' => 'required|max:255',
            'email' => 'required|unique:fleets,email|email|max:255',
            'mobile' => 'between:6,13',
            'logo' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password' => 'required|min:6|confirmed',
                     
        ]);
        try{     
                $fleet = $request->all();
                $fleet['password'] = bcrypt($request->password);
                if($request->hasFile('logo')) {
                    $fleet['logo'] = Helper::upload_picture($request->logo);
                }

                $fleet = Fleet::create($fleet);
                if($request->has('acc_no')) 
                    $fleet->acc_no = $request->acc_no;

                if($request->has('acc_name')) 
                    $fleet->acc_name = $request->acc_name;

                if ($request->has('bank_name'))
                    $fleet->bank_name = $request->bank_name;

                // if ($request->has('bank_name_id'))
                //     $fleet->bank_name_id = $request->bank_name_id;

                if ($request->has('bank_code'))
                    $fleet->bank_code = $request->bank_code;

                if ($request->has('dl_no'))
                    $fleet->dl_no = $request->dl_no;

                if ($request->has('dl_exp'))
                    $fleet->dl_exp = date('Y-m-d h:i:s', strtotime($request->dl_exp));

                if ($request->has('dl_country'))
                    $fleet->dl_country = $request->dl_country;

                if ($request->has('dl_state'))
                    $fleet->dl_state = $request->dl_state;

                if ($request->has('dl_city'))
                    $fleet->dl_city = $request->dl_city;

                
                $fleet->latitude = $request->latitude;
                $fleet->longitude = $request->longitude;
                $fleet->address = $request->address;
                $referral_code = strtoupper(substr(str_shuffle("0123456789abcdefghijklmnopqrstvwxyz"), 0, 6));
                $fleet->referal = $referral_code;

                $fleet->save(); 

                //Create Default Driver 
                $provider = new Provider;
                $provider->first_name = $request->company;

                $provider->password = bcrypt($request->password);

                $provider->last_name = '';

                $provider->email = $request->email;

                $provider->wallet_balance = 0;

                $provider->fleet = $fleet->id;

                $provider->latitude = $request->latitude;
                $provider->longitude = $request->longitude;


                $provider->fleet_driver = 1;

               
                $provider->mobile = $request->mobile;
                if($request->has('country_code')){
                    $provider->country_code = $request->country_code;
                }else{
                    $provider->country_code = "+233";
                }
                $provider->status = "approved";
                $provider->approved_by = Auth::guard('admin')->user()->id;

                $provider->save();

                if ($request->hasFile('logo')){
                    $name = $Provider->id."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actyual url to logo';                    
                    $contents = file_get_contents($request->logo);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $Provider->avatar = $s3_url;   
                }

                $provider->save();

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

                if ($request->hasFile('car_picture')){
                        $name = $Provider->provider_id."-car-".$Provider->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with url to car pictures';                    
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
                
                // return back()->with('flash_success','Fleet Details Saved Successfully');
                return redirect()->route('admin.fleet.assign.service', $fleet->id )->with('flash_success','Fleet Details Saved Successfully');
            
        } 

        catch (Exception $e) {
            Log::info($e);
            // $fleet = Fleet::find($fleet->id)->delete();
            // $client = new Client(['http_errors' => false]);
            // $url ="https://api.ravepay.co/v2/gpx/subaccounts/delete";
            // $headers = [
            //     'Content-Type' => 'application/json',
            // ];
            // $body = ['id' => $subaccount['data']['id'], 'seckey' => env("RAVE_SECRET_KEY")];
            // $res = $client->post($url, [
            //     'headers' => $headers,
            //     'body' => json_encode($body),
            // ]);
            // $subaccount = json_decode($res->getBody(),true);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // 
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $fleet = Fleet::with('fleet_subaccount')->findOrFail($id);
            $banks = Bank::all();                     
            return view('admin.fleet.edit',compact('fleet','banks'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Fleet  $fleet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }
        
        $this->validate($request, [
            'name' => 'required|max:255',
            'company' => 'required|max:255',
            'mobile' => 'between:6,13',
            'logo' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            
        ]);

        try {

            $fleet = Fleet::with('fleet_subaccount')->findOrFail($id);

                // if(!empty($fleet->fleet_subaccount))
                // {
                //     $client = new Client(['http_errors' => false]);
                //     $url ="https://api.ravepay.co/v2/gpx/subaccounts/edit";
                //     $headers = [
                //         'Content-Type' => 'application/json',
                //     ];
                //     $body = [
                //                 "id"                        =>  $fleet->fleet_subaccount->split_id,
                //                 "account_bank"              =>  $request->bank_code,
                //                 "account_number"            =>  $request->acc_no, 
                //                 "business_name"             =>  $fleet->first_name,
                //                 "business_email"            =>  $fleet->email,                            
                //                 "seckey"                    =>  env("RAVE_SECRET_KEY")
                //             ];            
                //     $res = $client->post($url, [
                //         'headers' => $headers,
                //         'body' => json_encode($body),
                //     ]);
                //     $subaccount = json_decode($res->getBody(),true);
                    
                // } else if($request->acc_no != '' && $request->bank_code !='')
                //     {
                //         $client = new Client(['http_errors' => false]);
                //         $url ="https://api.ravepay.co/v2/gpx/subaccounts/create";
                //         $headers = [
                //             'Content-Type' => 'application/json',
                //         ];
                //         $body = [
                //                     "account_bank"              => $request->bank_code,
                //                     "account_number"            => $request->acc_no, 
                //                     "business_name"             => $request->company,
                //                     "business_email"            => $request->email,
                //                     "business_contact"          => $request->name,
                //                     "business_contact_mobile"   =>  $request->mobile,
                //                     "business_mobile"           =>  $request->mobile,
                //                     "country"                   =>  $request->bankcountry,
                //                     "meta"                      =>  ["metaname" => "MarketplaceID", "metavalue" => "ggs-920900"],
                //                     "seckey"                    =>  env("RAVE_SECRET_KEY")
                //                 ];

                //         $res = $client->post($url, [
                //             'headers' => $headers,
                //             'body' => json_encode($body),
                //         ]);
                //         $subaccount = json_decode($res->getBody(),true);
                //         if($subaccount['status'] == 'success')
                //         {
                //             $split                  = new FleetSubaccount;
                //             $split->fleet_id        = $fleet->id;
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
                //             return back()->with('flash_error', $subaccount['message']);
                //         }
                //     }     

            if($request->hasFile('logo')) {
                \Storage::delete($fleet->logo);
                $fleet->logo = Helper::upload_picture($request->logo);
            }

            $fleet->name = $request->name;
            $fleet->email = $request->email;
            $fleet->company = $request->company;
            $fleet->mobile = $request->mobile;
            if($request->has('acc_no')) 
                $fleet->acc_no = $request->acc_no;

            if($request->has('acc_name')) 
                $fleet->acc_name = $request->acc_name;

            if ($request->has('bank_name'))
                $fleet->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $fleet->bank_name_id = $request->bank_name_id;

            if ($request->has('bank_code'))
                $fleet->bank_code = $request->bank_code;

            if ($request->has('dl_no'))
                $fleet->dl_no = $request->dl_no;

            if ($request->has('dl_exp'))
                $fleet->dl_exp = $request->dl_exp;

            if ($request->has('dl_country'))
                $fleet->dl_country = $request->dl_country;

            if ($request->has('dl_state'))
                $fleet->dl_state = $request->dl_state;

            if ($request->has('dl_city'))
                $fleet->dl_city = $request->dl_city;

            
           if($request->has('latitude')){
                $fleet->latitude = $request->latitude;
            }
            if($request->has('longitude')){
                $fleet->longitude = $request->longitude;
            }
            if($request->has('address')){
                $fleet->address = $request->address;
            }

            if($request->has('dispatch_method')){
                $fleet->dispatch_method = $request->dispatch_method;
            }
            $fleet->save();
            
            $subaccount = FleetSubaccount::where('fleet_id',$fleet->id)->first();
            if($subaccount){
                $subaccount->account_number = $request->acc_no;
                $subaccount->bank_code = $request->bank_code;
                $subaccount->save();
            }
            

            return redirect()->route('admin.fleet.index')->with('flash_success', 'Fleet Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Fleet Not Found');
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
                                   'SUM(ROUND(fixed) + ROUND(distance)) as overall, SUM(ROUND(commision)) as commission' 
                               ))->get();


            $Joined = $Provider->created_at ? '- Joined '.$Provider->created_at->diffForHumans() : '';

            return view('admin.providers.statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->first_name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Fleet  $Fleet
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }
        
        try {
            Fleet::find($id)->delete();
            return back()->with('flash_success', 'Fleet deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Fleet Not Found');
        }
    }

    /**
     * Used to update fleet auto payout status
     */
    public function autopayout(Request $request){
         try {
             $fleet = Fleet::find($request->id);
            if($request->status == '1') {
                $fleet->auto_payout = 0;
            }
            else{
                $fleet->auto_payout = 1;
            }
             $fleet->save();
             return 1; 
         } catch (\Throwable $th) {
             return 0;
         }
    }
}
