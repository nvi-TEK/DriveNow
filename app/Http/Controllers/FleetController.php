<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;
use Log;

use Auth;
use Setting;
use Exception;
use \Carbon\Carbon;
use App\User;
use App\Fleet;
use App\FleetPrice;
use App\Provider;
use App\UserPayment;
use App\ServiceType;
use App\UserRequests;
use App\ProviderService;
use App\UserRequestRating;
use App\UserRequestPayment;
use App\RequestFilter;
use App\ProviderDocument;
use GuzzleHttp\Client;
use Storage;

class FleetController extends Controller
{
    protected $UserAPI;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserApiController $UserAPI)
    {
        $this->middleware('fleet');
        $this->UserAPI = $UserAPI;
    }


    /**
     * Dashboard.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        try{
            $fleet = Auth::user();
            $getting_ride = UserRequests::whereHas('provider', function($query) use ($fleet) {
                            $query->where('fleet', $fleet->id);
                        })->where('provider_id', '!=', 0)
                    ->orderBy('id','desc');

            $rides = $getting_ride->take(10)->get();
            $all_rides = $getting_ride->get()->pluck('id');
            $cancel_rides = UserRequests::where('status','CANCELLED') 
                            ->whereHas('provider', function($query) use ($fleet) {
                            $query->where('fleet', $fleet->id);
                            })->where('provider_id', '!=', 0)->count();
            $completed = UserRequests::where('status','COMPLETED') 
                            ->whereHas('provider', function($query) use ($fleet) {
                            $query->where('fleet', $fleet->id);
                            })->where('provider_id', '!=', 0)->count(); 
            $service = FleetPrice::where('fleet_id', Auth::user()->id)->count();
            $total = UserRequestPayment::whereIn('request_id',$all_rides)->sum('total');
            $commission = UserRequestPayment::whereIn('request_id',$all_rides)->sum('drivercommision');
            $revenue = $total - $commission;
            $providers_count = Provider::where('archive', '!=', 1)->where('fleet', Auth::user()->id)->count();
            $providers_fleet = Provider::where('archive', '!=', 1)->where('fleet', Auth::user()->id)->pluck('id');
            $providers = Provider::where('fleet', Auth::user()->id)->take(10)->orderBy('rating','desc')->get();
            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->whereIn('provider_id',$providers_fleet)->take(10)->orderBy('updated_at','desc')->with('provider','document')->groupby('provider_id')->get();
            if(Auth::user()->roles == 1){
                return redirect()->route('fleet.provider.index');
            }else{
                return view('fleet.dashboard',compact('providers','service','rides','cancel_rides','revenue', 'providers_count', 'completed','recent_documents'));
            }
            
        }
        catch(Exception $e){
            return redirect()->route('fleet.user.index')->with('flash_error','Something Went Wrong with Dashboard!');
        }
    }

    /**
     * Map of all Users and Drivers.
     *
     * @return \Illuminate\Http\Response
     */
    public function map_index()
    {
        return view('fleet.map.index');
    }

    /**
     * Map of all Users and Drivers.
     *
     * @return \Illuminate\Http\Response
     */
    public function map_ajax()
    {
        try {

            $Providers = Provider::where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    ->where('fleet', Auth::user()->id)
                    ->with('service')
                    ->get();

            $Users = User::where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    ->get();

            for ($i=0; $i < sizeof($Users); $i++) { 
                $Users[$i]->status = 'user';
            }

            $All = $Users->merge($Providers);

            return $All;

        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        return view('fleet.account.profile');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function profile_update(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        $this->validate($request,[
            'name' => 'required|max:255',
            'company' => 'required|max:255',
            'mobile' => 'required|between:6,13',
            'logo' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        ]);
        if($request->mobile[0] == "0"){
            $request->mobile = ltrim($request->mobile, 0);
        }
    
        try{
            $fleet = Auth::guard('fleet')->user();
            $fleet->name = $request->name;
            $fleet->mobile = $request->mobile;
            $fleet->company = $request->company;
            if($request->has('latitude')){
                $fleet->latitude = $request->latitude;
            }
            if($request->has('longitude')){
                $fleet->longitude = $request->longitude;
            }
            if($request->has('address')){
                $fleet->address = $request->address;
            }
            
            if($request->hasFile('logo')){
                $fleet->logo = Helper::upload_picture($request->file('logo'));  
            }
             if($request->has('dispatch_method')){
                $fleet->dispatch_method = $request->dispatch_method;
            }
            $fleet->driver_payout = ($request->driverpayout == 'on') ? 1 : 0;
            $fleet->save();

            return redirect()->back()->with('flash_success','Profile Updated');
        }

        catch (Exception $e) {
            
             return back()->with('flash_error','Something Went Wrong!');
        }
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function password()
    {
        return view('fleet.account.change-password');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function password_update(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error','Disabled for demo purposes! Please contact us at ...');
        }

        $this->validate($request,[
            'old_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        try {

           $Fleet = Fleet::find(Auth::guard('fleet')->user()->id);

            if(password_verify($request->old_password, $Fleet->password))
            {
                $Fleet->password = bcrypt($request->password);
                $Fleet->save();

                return redirect()->back()->with('flash_success','Password Updated');
            } else {
                return back()->with('flash_error','Password entered doesn\'t match');
            }
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Provider Rating.
     *
     * @return \Illuminate\Http\Response
     */
    public function provider_review()
    {
        try {

            $rides = UserRequests::whereHas('provider', function($query) {
                            $query->where('fleet', Auth::user()->id );
                        })->get()->pluck('id');

            $Reviews = UserRequestRating::whereIn('request_id',$rides)
                        ->where('provider_id','!=',0)
                        ->with('user','provider')
                        ->get();

            return view('fleet.review.provider_review',compact('Reviews'));

        } catch(Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ProviderService
     * @return \Illuminate\Http\Response
     */
    public function destory_provider_service($id){
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }
        try {
            ProviderService::find($id)->delete();
            return back()->with('flash_success', 'Service deleted successfully');
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function createrequest(){
        try {
            $fleet = Auth::user();
            $user = User::where('archive', '!=', '1')->where('fleet',$fleet->id)->get();
            $driver = Provider::where('archive', '!=', '1')->where('fleet',$fleet->id)->get();            
            $products = FleetPrice::where('fleet_id',$fleet->id)->with('service')->get();
            return view('fleet.request.create',compact('products','driver','user'));
        } catch (Exception $e) {
            return back()->with('flash_error',trans('ownerdashboard.something_went_wrong'));
        }
    }

    public function estimate_fare(Request $request){

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
            Log::info($details);
            $meter = $details['rows'][0]['elements'][0]['distance']['value'];
            $time = $details['rows'][0]['elements'][0]['duration']['text'];
            $seconds = $details['rows'][0]['elements'][0]['duration']['value'];

            $kilometer = round($meter/1000);
            $minutes = round($seconds/60);

            $tax_percentage = Setting::get('tax_percentage');
            $commission_percentage = Setting::get('commission_percentage');
            
            $service_type = FleetPrice::where('service_id', $request->service_type)->where('fleet_id', $request->fleet)->first();
            if(!$service_type){
                $service_type = ServiceType::findOrFail($request->service_type);
            }

            $price_base = $service_type->fixed;

            $price = ($kilometer * $service_type->price) + ($service_type->time * $minutes);

            $time_price = $service_type->time* $minutes;
            $distance_price = $kilometer * $service_type->price;
            

            $price += ( $commission_percentage/100 ) * $price;
            $tax_price = ( $tax_percentage/100 ) * $price;
            $total = $price + $tax_price + $price_base;

            $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');

            $distance = Setting::get('provider_search_radius', '10');
            $latitude = $request->s_latitude;
            $longitude = $request->s_longitude;

            $Providers = Provider::whereIn('id', $ActiveProviders)
                ->where('status', 'approved')
                ->where('fleet', $request->fleet)
                ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
                ->get();

            if($Providers->count() <= Setting::get('surge_trigger') && $Providers->count() > 0){
                $surge_price = (Setting::get('surge_percentage')/100) * $total;
                $total += $surge_price;
            }
            $service_type = ServiceType::find($request->service_type);
                    $value['service_type'] =$service_type->name;
                    $value['estimated_fare'] =number_format($total,2); 
                    $value['distance'] =$kilometer;
                    $value['distance_price'] =number_format($distance_price,2);
                    $value['time'] =$time;
                    $value['time_price'] =number_format($time_price,2);
                    $value['tax_price'] =number_format($tax_price,2);
                    $value['base_price'] =number_format($service_type->fixed,2);
                    $value['wallet_balance'] =number_format(Auth::user()->wallet_balance,2);
                    $value['total'] =$total;
             return $value;

        } catch(Exception $e) {
            
            return response()->json(['success' => FALSE, 'message' => trans('api.something_went_wrong')], 200);
        }
    }

    /**
     * Used to Create Fleet
     */
    public function postrequest(Request $request){
        try {
            $fare = $this->UserAPI->estimated_fare($request)->getData();
        
            if($request->new_user == 0){
                $user_id = $request->user_id;
            }else{
                $user = new User;
                $user->first_name = $request->first_name;
                $user->last_name = $request->first_name;
                $user->payment_mode = 'CASH';
                $user->email = $request->email;
                $user->password = bcrypt('123456');
                $user->device_type = 'ios';
                $user->login_by = 'manual';
                $user->mobile = $request->mobile;
                $user->save(); 
                $user_id = $user->id;
            }
            $UserRequest = new UserRequests;
            $UserRequest->booking_id = Helper::generate_booking_id();
            $UserRequest->user_id = $user_id;
            $UserRequest->current_provider_id = $request->provider_id;
            $UserRequest->service_type_id = $request->service_type;
            $UserRequest->fleet_id = Auth::user()->id;
            $UserRequest->payment_mode = $request->payment_mode;        
            $UserRequest->status = 'SEARCHING';
            $UserRequest->s_address = $request->s_address ? : "";
            $UserRequest->d_address = $request->d_address ? : "";
            $UserRequest->s_latitude = $request->s_latitude;
            $UserRequest->s_longitude = $request->s_longitude;
            $UserRequest->d_latitude = $request->d_latitude;
            $UserRequest->d_longitude = $request->d_longitude;
            $UserRequest->distance = $fare->distance;
            $UserRequest->assigned_at = Carbon::now();
            if($request->has('schedule_date') && $request->has('schedule_time')){
                $UserRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$request->schedule_date $request->schedule_time"));
            }
            $UserRequest->save();

            (new SendPushNotification)->IncomingRequest($request->provider_id);
            
            $Filter = new RequestFilter;
            $Filter->request_id = $UserRequest->id;
            $Filter->provider_id = $request->provider_id; 
            $Filter->save();
            
            return back()->with('flash_success', 'Request Created Successfully');
        } catch (\Throwable $th) {
            dd($th->getMessage());
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

    public function getuserlocation(Request $request)
    {
        $html = '';
        $locations = UserLocation::where('user_id', $request->value)->get();
        if(!empty($locations))
        {
            $html .= '<option value="">Select Address</option>';
            foreach($locations as $location)
            {
                $html .= '<option value="'.$location->id.'" data-lat="'.$location->latitude.'" data-lon="'.$location->longitude.'" data-address="'.$location->address.'">'.$location->address.'</option>';   
            }
        }
        return $html;
    }

         public function makeonline(Request $request){

        $this->validate($request,[
            'user_id' => 'required_if:new_user.*,in:0',
            'first_name' => 'required_if:new_user.*,in:1',
            'mobile' => 'required_if:new_user.*,in:1|unique:users,mobile',
            'dob' => 'required_if:new_user.*,in:1',
            'provider_id' => 'required',
            'payment_mode' => 'required',
            // 'value' => 'required',
            'product' => 'required',
            // 's_address' => 'required',
        ]);        
        // if($request->has('schedule_date') && $request->has('schedule_time')){

        //     if(time() > strtotime($request->schedule_date.$request->schedule_time)){
        //         if($request->ajax()) {
        //             return response()->json(['success'=>FALSE,'message' => trans('api.ride.request_inprogress')], 200);
        //         }else{
        //             return redirect('dashboard')->with('flash_error', 'Unable to Create Request! Schedule time minimum 1 hour in advance');
        //         }
        //     }

        //     $beforeschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->subHour(1);
        //     $afterschedule_time = (new Carbon("$request->schedule_date $request->schedule_time"))->addHour(1);
            
        //     $CheckScheduling = UserRequests::where('status','SCHEDULED')
        //                     ->where('user_id', Auth::user()->id)
        //                     ->whereBetween('schedule_at',[$beforeschedule_time,$afterschedule_time])
        //                     ->get();

        //     if($CheckScheduling->count() > 0){
        //         if($request->ajax()) {
        //             return response()->json(['success'=>FALSE, 'message' => trans('api.ride.no_drivers_found')], 200);
        //         }else{
        //             return redirect('dashboard')->with('flash_error', trans('ownerdashboard.already_request_is_scheduled_on_this_time'));
        //         }
        //     }

        // }
   
         try{            
            if($request->new_user == 0){

                $user = User::where('id',$request->user_id)->first();
            }elseif($request->new_user == 1){
                $user = new User;
                $user->first_name = $request->first_name;
                $user->mobile = $request->mobile;
                $user->email = $request->email;
                $user->dob = $request->dob;
                $user->payment_mode = $request->payment_mode;
                $user->wallet_balance = 0;
                $user->password = bcrypt("123456");
                $user->store_id = Auth::user()->id;
                $user->save(); 
            }else{
                return back()->with('flash_error', 'Something went wrong while creating request. Please try again.');
            }

            $create_address = new UserLocation;
            $create_address->address = $request->s_address;
            $create_address->latitude = $request->latitude;
            $create_address->longitude = $request->longitude;
            $create_address->user_id = $user->id;
            $create_address->title = "Home";
            $create_address->status = 0;
            $create_address->save();
 
            $address = UserLocation::findOrFail($create_address->id);

            $UserRequest = new UserRequests;
            $UserRequest->booking_id = Helper::generate_booking_id();
            $UserRequest->user_id = $user->id;
            $UserRequest->payment_mode = $request->payment_mode;
            
            
            $UserRequest->s_address = $request->s_address ? : "";
            $UserRequest->d_address = $request->d_address ? : "";

            $UserRequest->s_latitude = $request->s_latitude;
            $UserRequest->s_longitude = $request->s_longitude;

            $UserRequest->d_latitude = $request->d_latitude;
            $UserRequest->d_longitude = $request->d_longitude;
            $UserRequest->distance = $request->distance;

            if($service_type->is_delivery == 1){
            $UserRequest->receiver_name = $request->receiver_name;
            $UserRequest->receiver_mobile = $request->receiver_mobile;
            $UserRequest->pickup_instruction = $request->pickup_instruction;
            $UserRequest->delivery_instruction = $request->delivery_instruction;
            $UserRequest->package_type = $request->package_type;
            $UserRequest->package_details = $request->package_details;
            }
            $UserRequest->current_provider_id = $request->provider_id;
            $UserRequest->service_type_id = $request->service_type;
            
            $UserRequest->fleet_id = Auth::user()->id;

            // if($request->provider_id){
            // $UserRequest->provider_id = $request->provider_id;

            // }

            $UserRequest->use_wallet = $request->use_wallet ? : 0;

            $UserRequest->total = 0;
             
            
            $UserRequest->assigned_at = Carbon::now();

            if($request->has('schedule_date') && $request->has('schedule_time')){
                $time = date($request->schedule_time);
                $scheduled_time = date("H:i:s", strtotime($time));
                $date = str_replace('/', '-', $request->schedule_date);
                $scheduled_date = date($date);
                $UserRequest->schedule_at = date("Y-m-d H:i:s",strtotime("$scheduled_date $scheduled_time"));

                $UserRequest->status = 'SCHEDULED';
            }else{
                $UserRequest->status = 'SEARCHING'; 
            }

            $UserRequest->save();

            // for($i=0; $i<sizeof($request->product);$i++)
            // {   

            // $ids = $request->product[$i];

            // $items = ServiceType::where('id',$ids)->first();

            //     if($request->value[$i] != 0){

            //         $order = new OrderList;

            //         $order->user_id = $request->user_id ? $request->user_id : $user->id ;
            //         $order->store_id = Auth::user()->id ;
            //         $order->request_id = $UserRequest->id;
            //         $order->service_id = $request->product[$i];
            //         $order->price = ($request->price[$i] != 0) ? $request->price[$i] : $items->fixed;                    
            //         $order->type =  $items->type;
            //         $order->quantity = $request->value[$i];
            //         $order->name = $items->name;
            //         $order->save();

            //         $total = $order->price * $order->quantity;
                
            //         $UserRequest->total += $total ;
            //         $UserRequest->save();

            //         $income = new IncomeManagement;
            //         $income->income_category = 1;
            //         $income->description = 'Order';
            //         $income->amount = $total;
            //         $income->date = Carbon::now();
            //         $income->created_by = 'owner';
            //         $income->user_id = Auth::user()->id;
            //         $income->save();
                    
            //         if($request->offline){
            //             $offline->request_id = $UserRequest->id;
            //             $offline->status = '1';
            //             $offline->save();
            //         }

            //     }
            // }

             
             // (new SendPushNotification)->RequestCreated($UserRequest);
             // (new SendPushNotification)->RequestAccepted($UserRequest);
             (new SendPushNotification)->IncomingRequest($UserRequest->provider_id);

         
            session(['request_id' => $UserRequest->id]);
            Log::info('New Request id : '. $UserRequest->id .' Assigned to Guard : '. $UserRequest->current_provider_id);

           
            User::where('id',Auth::user()->id)->update(['payment_mode' => $request->payment_mode]);


            if($request->ajax()) {
                return response()->json([
                        'success' => TRUE,
                        'message' => 'New request Created!',
                        'request' => $UserRequest     
                    ]);
            }else{

                $product = ServiceType::get();
                return redirect()->route('owner.requestsassigned')->with('flash_success',trans('ownerdashboard.order_request_created_successfully'));
            }

        } catch (Exception $e) {
           //
            if($request->ajax()) {
                return response()->json(['success'=>FALSE, 'message' => trans('api.something_went_wrong')], 200);
            }else{
                return back()->with('flash_error', trans('api.something_went_wrong'));
            }
        }
    }

    //  public function updateDocument(Request $request)
    // {
    //     $this->validate($request, [
    //             'document' => 'mimes:jpg,jpeg,png,pdf',
    //         ]);

    //     try {
            
    //         $Document = ProviderDocument::where('provider_id', $request->provider_id)
    //             ->where('document_id', $request->document_id)
    //             ->firstOrFail();

    //         $url = Helper::upload_picture($request->document);
    //         $Document->url = $url;
    //         $Document->status = 'ASSESSING';
    //         $Document->save();

    //     } catch (Exception $e) {
            
    //         $url = Helper::upload_picture($request->document);
            
    //         $Document = new ProviderDocument;
    //         $Document->url = $url;
    //         $Document->provider_id = $request->provider_id;
    //         $Document->document_id = $request->document_id;
    //         $Document->status = 'ASSESSING';
    //         $Document->save();
            
    //     }
        
    //     return back()->with('flash_success','Document Uploaded successfully');
        
    // }

     public function updatedocument(Request $request){

        $document_id = $request->document_id;
        $id = $request->provider_id;
        try {
            
            $Document = ProviderDocument::where('provider_id', $id)
                ->where('document_id', $document_id)
                ->first();
                if($Document){

                    $name = $Document->provider_id."-doc-".$Document->id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = ''; // Replac with base url                  
                    $contents = file_get_contents($request->document);
                    $path = Storage::disk('s3')->put('driver_documents/'.$name, $contents);
                    $url = $baseurl.'/'.$name;
                    $Document->update([
                        'url' => $url,
                        'status' => 'ASSESSING',
                    ]);

                return back()->with('flash_success', 'Document Uploaded Successfully');
            }
            else{
                 $name = $id."-doc-".$document_id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = '';  //Replac ewiht base url                    
                    $contents = file_get_contents($request->document);
                    $path = Storage::disk('s3')->put('driver_documents/'.$name, $contents);
                    $url = $baseurl.'/'.$name;
            ProviderDocument::create([
                    'url' => $url,
                    'provider_id' => $id,
                    'document_id' => $document_id,
                    'status' => 'ASSESSING',
                ]);
            return back()->with('flash_success', 'Document Uploaded Successfully');
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Server Error! Please try again later');
        }
    }


     public function statement($type = 'individual'){
        try{
            $page = 'Ride Statement';

            if($type == 'individual'){
                $page = 'Overall Statement';
            }elseif($type == 'today'){
                $page = 'Today Statement - '. date('d M Y');
            }elseif($type == 'monthly'){
                $page = 'This Month Statement - '. date('F');
            }elseif($type == 'yearly'){
                $page = 'This Year Statement - '. date('Y');
            }
            $Rides = UserRequests::where('fleet_id', Auth::user()->id)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

            $rides = UserRequests::with('payment')->where('fleet_id', Auth::user()->id)->orderBy('id','desc');

            $cancel_rides = UserRequests::where('status','CANCELLED')->where('fleet_id', Auth::user()->id);
            $revenue = UserRequestPayment::whereIn('request_id', $Rides)->select(\DB::raw(
                           'SUM(ROUND(total)) as overall, SUM(ROUND(drivercommision)) as commission' 
                       ));

            if($type == 'today'){

                $rides->where('created_at', '>=', Carbon::today());
                $cancel_rides->where('created_at', '>=', Carbon::today());
                $revenue->where('created_at', '>=', Carbon::today());

            }elseif($type == 'monthly'){

                $rides->where('created_at', '>=', Carbon::now()->month);
                $cancel_rides->where('created_at', '>=', Carbon::now()->month);
                $revenue->where('created_at', '>=', Carbon::now()->month);

            }elseif($type == 'yearly'){

                $rides->where('created_at', '>=', Carbon::now()->year);
                $cancel_rides->where('created_at', '>=', Carbon::now()->year);
                $revenue->where('created_at', '>=', Carbon::now()->year);

            }

            $rides = $rides->get();
            $cancel_rides = $cancel_rides->count();
            $revenue = $revenue->get();
            $completed = UserRequests::where('status','COMPLETED') 
                            ->whereHas('provider', function($query) {
                                $query->where('fleet', Auth::user()->id );
                            })->count(); 

            return view('fleet.statement.statement', compact('rides','cancel_rides','revenue','completed'))
                    ->with('page',$page);

        } catch (Exception $e) {
            
            return back()->with('flash_error','Something Went Wrong!');
        }
    }


    /**
     * account statements today.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function statement_today(){
        return $this->statement('today');
    }

    /**
     * account statements monthly.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function statement_monthly(){
        return $this->statement('monthly');
    }

     /**
     * account statements monthly.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function statement_yearly(){
        return $this->statement('yearly');
    }


    /**
     * account statements.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function statement_provider($fleet){

        try{
                $fleets = Fleet::find(Auth::user()->id);
                $page = $fleets->name.'\'s Drivers Statement';
                $Providers = Provider::where('fleet', Auth::user()->id)->get();
            

            foreach($Providers as $index => $Provider){

                $Rides = UserRequests::where('provider_id',$Provider->id)
                            ->where('fleet_id', Auth::user()->id)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

                $Providers[$index]->rides_count = $Rides->count();

                $Providers[$index]->payment = UserRequestPayment::whereIn('request_id', $Rides)
                                ->select(\DB::raw(
                                   'SUM(ROUND(total)) as overall, SUM(ROUND(drivercommision)) as commission' 
                                ))->get();
            }

            return view('fleet.statement.provider-statement', compact('Providers'))->with('page', $page);

        } catch (Exception $e) {
            
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function statement_service(){

        try{
            $services = FleetPrice::where('fleet_id',Auth::user()->id)->with('service')->orderBy('created_at','asc')->get();

            $page = 'Statement by Services';
            foreach($services as $index => $service){

                $Rides = UserRequests::where('service_type_id',$service->service->id)
                            ->where('fleet_id', Auth::user()->id)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

                $services[$index]->rides_count = $Rides->count();

                $services[$index]->payment = UserRequestPayment::whereIn('request_id', $Rides)
                                ->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance_taken)) as overall' 
                                ))->get();
            }

            return view('fleet.statement.services-statement', compact('services'))->with('page', $page);

        } catch (Exception $e) {
            
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function statement_fleet(){

        try{

            $Providers = Fleet::all();

            foreach($Providers as $index => $Provider){
                $driver = Provider::where('fleet', $Provider->id)->first();
                $Rides = UserRequests::where('provider_id',$driver->id)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

                $Providers[$index]->rides_count = $Rides->count();

                $Providers[$index]->payment = UserRequestPayment::whereIn('request_id', $Rides)
                                ->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance)) as overall, SUM(ROUND(commision)) as commission' 
                                ))->get();
            }

            return view('fleet.statement.fleet-statement', compact('Providers'))->with('page','Fleet Statement');

        } catch (Exception $e) {
            
            $Providers = Fleet::all();
            return view('fleet.providers.fleet-statement', compact('Providers'))->with('page','Fleet Statement');
        }
    }

    public function payment()
    {
        try {
             $payments = UserRequests::where('paid', 1)->where('fleet_id', Auth::user()->id)
                    ->has('user')
                    ->has('provider')
                    ->has('payment')
                    ->orderBy('user_requests.created_at','desc')
                    ->get();
            
            return view('fleet.payment.payment-history', compact('payments'));
        } catch (Exception $e) {
            
             return back()->with('flash_error','Something Went Wrong!');
         }
     }
    /**
     * Used to show Driver Payouts
     */
    public function showPayout()
    {
        try {
            $requests = UserRequests::with('payment','service_type','user','provider')->where(['status' => 'COMPLETED', 'payment_mode' => 'CARD', 'driver_payout' => 0])->where('fleet_id', Auth::user()->id)->get();
            return view('fleet.driverPayout', compact('requests'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }
    }

    /**
     * Used to Proceed driver payout
     */
    public function driverPayout(Request $request)
    {
        try {
            $requests = UserRequests::with('provider_profiles','payment')->whereIn("id", $request->id)->get();                  
            $bulkdata = [];
            if(!empty($requests))
            {
                foreach($requests as $index => $bank)
                {
                    $commission = $bank->payment->drivercommision;
                    $data = [
                        'Bank'              =>  $bank->provider_profiles->bank_code,
                        'Account Number'    =>  $bank->provider_profiles->acc_no,
                        'Amount'            =>  $commission,
                        'Currency'          =>  'NGN',
                        'Narration'         =>  "Bulk transfer ".$index,
                        'Reference'         =>  "mk-".rand()
                    ];                    
                    array_push($bulkdata, $data);
                }
            }                 
            $client = new Client(['http_errors' => false]);
            $url ="https://api.ravepay.co/v2/gpx/transfers/create_bulk";
            $headers = [
                'Content-Type' => 'application/json',
            ];
            $body = [
                        "seckey"                    =>  env("RAVE_SECRET_KEY"),
                        "title"                     =>  "Bulk Pay for Owners",
                        'bulk_data'                 =>  json_encode($bulkdata),
                    ];                       
            $res = $client->post($url, [
                'headers' => $headers,
                'body' => json_encode($body),
            ]);
            $bulkpay = json_decode($res->getBody(),true); 
            if($bulkpay['status'] == 'success'){
                $id = $bulkpay['data']['id'];
                $sql = UserRequests::whereIn("id",$request->id)->update(['driver_payout' => 1, 'driver_payment_id' => $id]);  
                return back()->with('flash_success', 'Payment sent to driver successfully');                                           
            }else{ 
                return back()->with('flash_error', 'Unable to made payment. Please try again');
            }    
        } catch (\Throwable $th) {
            return back()->with('flash_error','Something went wrong');
        }
    }

    /**
     * Used to Get Current Requests
     */
    public function getRequests(){
        $request = UserRequests::with('user')->where('fleet_id', Auth::user()->id)->where(['status' => 'SEARCHING', 'notification' => 1])->orderBy('created_at', 'desc')->first();
        return $request;
    }

    /**
     * Used to Update Notification
     */
    public function UpdateNotification(Request $request){
        $userrequest = UserRequests::find($request->id);
        $userrequest->notification = 0;
        $userrequest->save();
        return 1;
    }

    /**
     * Used to Show Notifications
     */
    public function getNotifications(){
        $requests = UserRequests::with('user')->where(['status' => 'SEARCHING'])->where('fleet_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();
        $html = '';
        $requesti = UserRequests::with('user')->where('fleet_id', Auth::user()->id)->where(['status' => 'SEARCHING', 'notification' => 1])->orderBy('created_at', 'desc')->get();
        $count = count($requesti);
        if(count($requests) > 0){
            foreach($requests as $request){
                $url = url('fleet/requests/'.$request->id);
                $html .= '<li>
                            <a class="text-body-color-dark media mb-15" href="'.$url.'">
                                <div class="ml-5 mr-15">
                                    <i class="fa fa-fw fa-check text-success"></i>
                                </div>
                                <div class="media-body pr-10">
                                    <p class="mb-0">New Request from '.$request->user->first_name.' </p>
                                    <div class="text-muted font-size-sm font-italic">'.$request->created_at->diffForHumans().'</div>
                                </div>
                            </a>
                        </li>';
            }
        }
        else{
            $html .= '<li>
                        <a class="text-body-color-dark media mb-15" href="javascript:void(0)">
                            <div class="ml-5 mr-15">
                                <i class="fa fa-fw fa-check text-success"></i>
                            </div>
                            <div class="media-body pr-10">
                                <p class="mb-0">No Notification Found</p>
                                <!-- <div class="text-muted font-size-sm font-italic">15 min ago</div> -->
                            </div>
                        </a>
                    </li>';
        }
        return response()->json(['html' => $html, 'count' => $count]);
    }
}
