<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Helpers\Helper;
use Log;
use DB;
use Auth;
use Setting;
use Exception;
use \Carbon\Carbon;
use App\FleetPrice;
use App\User;
use App\Fleet;
use App\Admin;
use App\Provider;
use App\ProviderDocument;
use App\ProviderProfile;
use App\Document;
use App\UserPayment;
use App\ServiceType;
use App\UserRequests;
use App\ProviderService;
use App\UserRequestRating;
use App\UserRequestPayment;
use GuzzleHttp\Client;
use App\Http\Controllers\SendPushNotification;
use App\DriverComments;
use App\UserComments;
use App\FailedRequest;
use Storage;
use File;
use App\RaveTransaction;
use App\CustomPushes;
use App\IndividualPush;
use Session;
use App\DriverActivity;
use App\RequestActivity;
use App\DriverRequestReceived;
use App\OfflineRequestFilter;
use App\MLMUserNetwork;
use App\MLMDriverNetwork;
use App\MLMUserCommission;
use App\MLMDriverCommission;
use App\OfficialDriver;
use App\OfficeExpense;
use App\DriveNowVehicle;
use App\ExpenseCategory;
use App\DriveNowContracts;
use App\Bank;
use App\DriveNowRaveTransaction;
use App\DriveNowTransaction;
use App\DriveNowExtraPayment;
use App\DriverContracts;
use App\DriveNowVehicleSupplier;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('admin');
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
            if(Auth::guard('admin')->user()->id == 43){
               return redirect()->route('admin.driveNow.driver.list'); 
            }
            $rides = UserRequests::has('user')->take(10)->orderBy('id','desc')->get();
            $trips = UserRequests::has('user')->orderBy('id','desc')->count();
            $cancel_rides = UserRequests::where('status','CANCELLED');
            $scheduled_rides = UserRequests::where('status','SCHEDULED')->count();
            $user_cancelled = $cancel_rides->where('cancelled_by','USER')->count();
            $provider_cancelled = $cancel_rides->where('cancelled_by','PROVIDER')->count();
            $cancel_rides = UserRequests::where('status','CANCELLED')->count();
            $service = ServiceType::count();
            $fleet = Fleet::count();
            $users = User::where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->count();
            $official_drivers = Provider::where('archive', '!=', 1)->where('official_drivers', '1')->count();
            $revenue = UserRequestPayment::sum('total');
            $providers = Provider::take(10)->orderBy('rating','desc')->get();
            $providers_count = Provider::where('archive', '!=', 1)->count();
            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            $failed_trips = FailedRequest::count();
// dd($recent_documents);
            $total_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
            $overall_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
            $drivenow_revenue = $total_paid - $total_fees;
            $month = Carbon::now();
            $month = $month->format('m');
            // dd(Carbon::now()->startOfMonth()->subMonths(11));
                        $viewer = DriveNowRaveTransaction::select(DB::raw("ROUND(SUM(amount)) as drivenow_total"))
                        ->orderBy("created_at")
                        ->where('status',1)
                        ->where('network', '=','Eganow')
                        ->whereDate('created_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
                        ->groupBy(DB::raw("month(created_at)"))
                        ->get()->toArray();
            $viewer = array_column($viewer, 'drivenow_total');

            $paystack = DriveNowRaveTransaction::select(DB::raw("ROUND(SUM(amount)) as drivenow_ptotal, created_at"))
                        ->orderBy("created_at")
                        ->where('status',1)
                        ->where('network', '!=','Eganow')
                        ->where('created_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
                        ->groupBy(DB::raw("month(created_at)"))
                        ->get()->toArray();
            $paystack = array_column($paystack, 'drivenow_ptotal');
            
                        // dd($paystack);
            $click = UserRequestPayment::select(DB::raw("ROUND(SUM(total))  as trip_total"))
                // ->whereYear('created_at', Carbon::now()->year)
                ->where('created_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
                ->orderBy("created_at")
                ->groupBy(DB::raw("month(created_at)"))
                ->get()->toArray();
                
            $click = array_column($click, 'trip_total');

            $official = OfficialDriver::select(DB::raw("COUNT(*) as driver, agreement_start_date as reg"))
                        ->where('status', '!=', 1)
                        // ->where('agreement_start_date', '>=', Carbon::now()->startOfMonth()->subMonths(11))
                        ->orderBy("agreement_start_date")
                        ->groupBy(DB::raw("month(agreement_start_date)"),DB::raw("year(agreement_start_date)"))
                        ->get()->toArray();
            $off_driver = array_column($official, 'driver');
            $off_month = array_column($official, 'reg');

            // dd($off_month);
            $off_months = array();
            for($i=0; $i < count($off_month); $i++){
                $off_months[] = date('M-Y',strtotime($off_month[$i]));
            }

                        $overall_revenue = array();
            for ($i=0; $i < 12 ; $i++) { 
                if(empty($click[$i])){
                    $click[$i] = 0;
                }
                if(empty($paystack[$i])){
                    $paystack[$i] = 0;
                }
                if(empty($viewer[$i])){
                    $viewer[$i] = 0;
                }
                $overall_revenue[] = round($click[$i]+$viewer[$i]+$paystack[$i]); 
            }
            $months = array();
            for ($j = 11; $j >= 0; $j--) {
                $month = Carbon::now()->startOfMonth()->subMonths($j);
                $year = Carbon::now()->startOfMonth()->subMonths($j)->format('Y');
                $months[] = $month->format('M').'-'.$year;
                // array_push($data, array(
                //     'months' => $month->format('M').'-'.$year
                // ));
            }
            // dd($months);
            $viewer = json_encode($viewer,JSON_NUMERIC_CHECK);
            $click = json_encode($click,JSON_NUMERIC_CHECK);
            $paystack = json_encode($paystack,JSON_NUMERIC_CHECK);
            $overall_revenue = json_encode($overall_revenue,JSON_NUMERIC_CHECK);
            $months = json_encode($months,JSON_NUMERIC_CHECK);
            $off_months = json_encode($off_months,JSON_NUMERIC_CHECK);
            $off_driver = json_encode($off_driver,JSON_NUMERIC_CHECK);

            

            return view('admin.dashboard',compact('providers','fleet','scheduled_rides','service','rides','user_cancelled','provider_cancelled','cancel_rides','revenue','users','drivers','recent_documents', 'trips', 'failed_trips','official_drivers','total_due','overall_due','total_paid','total_fees','drivenow_revenue','click', 'viewer','overall_revenue','months','paystack','off_months', 'off_driver')); 
        }
        catch(Exception $e){
            Log::info($e);
            return redirect()->route('admin.user.index')->with('flash_error','Something Went Wrong with Dashboard!');
        }
    }

    public function search(Request $request)
    {
        try {
            if($request->search_by == 'driver'){
                $vehicles = DriveNowVehicle::where('status', 4)->get();
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
                    $page = 'Driver search result for "'.$request->search .'"';
                    $providers = $AllProviders->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%')->paginate(100);
                }
                
                $document = Document::all()->count();

                $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
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
                $suppliers = DriveNowVehicleSupplier::orderBy('created_at' , 'desc')->where('status', '!=',1)->get();
                $contracts = DriveNowContracts::where('status',1)->get();

                return view('admin.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','vehicles','suppliers','contracts'));

            }
            if($request->search_by == 'user'){
                $filter = 0;
                $AllUsers = User::where('archive', '!=', '1')->orderBy('created_at' , 'desc');
                $page = 'User search result for "'.$request->search .'"';
                        $users = $AllUsers->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%');
                $total_users = User::where('archive', '!=', '1')->count();
                $valid_users = User::where('first_name', "!=", '')->count();
                $unverified_users = User::where('first_name', '')->count();
                $newuser = User::where('archive', '!=', '1')->whereMonth('created_at',Carbon::now()->month)->count();
                $requests = UserRequests::whereMonth('created_at', Carbon::now()->month)->groupBy('user_id')->count();
                $users = $users->paginate(100);
                return view('admin.users.index', compact('users','newuser', 'requests', 'page', 'total_users', 'valid_users','filter','unverified_users'));
            }

            if($request->search_by == 'request'){
                $UserRequest = UserRequests::where('booking_id',$request->search)->first();
                if($UserRequest){
                   return redirect()->route('admin.requests.show', $UserRequest->id);
                }else{
                    return back()->with('flash_error', 'No Request found!');
                }
                
            }
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        } 
    }

    public function getDownload($id, $doc)
    {
        $Document = ProviderDocument::where('provider_id', $id)
                    ->where('document_id', $doc)
                    ->first();
        if($Document){
            $doc = Document::find($Document->document_id);
            $url = $Document->url;
            // $headers = [
            //           'Content-Type' => 'application/png',
            //        ];

            // return response()->download($url, $doc->name.'.png', $headers);

            return redirect(Storage::disk('s3')->temporaryUrl(
                    $url,
                    Carbon::now()->addHour(),
                    ['ResponseContentDisposition' => 'attachment']
                ));
   
        }else{
            return back()->with('flash_error','No Document Found');
        }
        
    }


    /**
     * Heat Map.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function heatmap()
    {
        try{
            $rides = UserRequests::has('user')->orderBy('id','desc')->get();
            $providers = Provider::take(10)->orderBy('rating','desc')->get();
            return view('admin.heatmap',compact('providers','rides'));
        }
        catch(Exception $e){
            return redirect()->route('admin.user.index')->with('flash_error','Something Went Wrong with Dashboard!');
        }
    }

    /**
     * Map of all Users and Drivers.
     *
     * @return \Illuminate\Http\Response
     */
    public function map_index()
    {
            $users = User::where('archive', '!=', 1)->count();
            $approved_drivers = Provider::where('status','!=','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
        return view('admin.map.index', compact('approved_drivers','online_drivers','total_drivers','offline_drivers', 'users'));
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
                    ->where('availability', 1)
                    ->where('status', 'approved')
                    ->get();

            
            for ($i=0; $i < sizeof($Providers); $i++) { 
                if($Providers[$i]->availability == 1 && $Providers[$i]->status == "approved"){
                    $Providers[$i]->status = 'active';
                    $Providers[$i]->location_updated = Carbon::parse($Providers[$i]->updated_at)->diffForHumans();
                }
            }

            
            return $Providers;

        } catch (Exception $e) {
            return [];
        }
    }

    public function driver_map($id)
    {
        try {

            $Providers = Provider::where('id', $id)
                    ->get();

            
            return $Providers;

        } catch (Exception $e) {
            return [];
        }
    }

    public function user_map_index()
    {
            $users = User::where('archive', '!=', 1)->count();
            $approved_drivers = Provider::where('status','!=','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
        return view('admin.map.user', compact('approved_drivers','online_drivers','total_drivers','offline_drivers', 'users'));
    }

    /**
     * Map of all Users and Drivers.
     *
     * @return \Illuminate\Http\Response
     */
    public function user_map_ajax()
    {
        try {

            

            $Users = User::where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    ->get();

            for ($i=0; $i < sizeof($Users); $i++) { 
                $Users[$i]->status = 'user';
            }
            
         
            return $Users;

        } catch (Exception $e) {
            return [];
        }
    }

    public function offline_map_index()
    {
            $users = User::where('archive', '!=', 1)->count();
            $approved_drivers = Provider::where('status','!=','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
        return view('admin.map.offline', compact('approved_drivers','online_drivers','total_drivers','offline_drivers', 'users'));
    }

    /**
     * Map of all Users and Drivers.
     *
     * @return \Illuminate\Http\Response
     */
    public function offline_map_ajax()
    {
        try {

           $Providers = Provider::where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    ->where('availability', 0)
                    ->where('status', 'approved')
                    ->get();

            for ($i=0; $i < sizeof($Providers); $i++) { 
                if($Providers[$i]->availability == 0 && $Providers[$i]->status == "approved"){
                    $Providers[$i]->status = 'offline';
                    $Providers[$i]->location_updated = Carbon::parse($Providers[$i]->updated_at)->diffForHumans();
                } 
            }
            
         
            return $Providers;

        } catch (Exception $e) {
            return [];
        }
    }

        public function unactivated_map_index()
    {
            $users = User::where('archive', '!=', 1)->count();
            $approved_drivers = Provider::where('status','!=','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
        return view('admin.map.unactivated', compact('approved_drivers','online_drivers','total_drivers','offline_drivers', 'users'));
    }

    /**
     * Map of all Users and Drivers.
     *
     * @return \Illuminate\Http\Response
     */
    public function unactivated_map_ajax()
    {
        try {

           $Providers = Provider::where('latitude', '!=', 0)
                    ->where('longitude', '!=', 0)
                    ->where('status','!=', 'approved')
                    ->get();

            for ($i=0; $i < sizeof($Providers); $i++) { 
                if($Providers[$i]->status != "approved"){
                    $Providers[$i]->status = 'unactivated';
                }
            }
            
         
            return $Providers;

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
    public function settings()
    {
        return view('admin.settings.application');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function settings_store(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error','Disabled for demo purposes! Please contact us at info@Eganow.com');
        }

        $this->validate($request,[
                'site_title' => 'required',
                'site_icon' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
                'site_logo' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            ]);

        if($request->hasFile('site_icon')) {
            $site_icon = Helper::upload_picture($request->file('site_icon'));
            Setting::set('site_icon', $site_icon);
        }

        if($request->hasFile('site_logo')) {
            $site_logo = Helper::upload_picture($request->file('site_logo'));
            Setting::set('site_logo', $site_logo);
        }

        if($request->hasFile('welcome_image')) {
            $welcome_image = Helper::upload_picture($request->file('welcome_image'));
            Setting::set('welcome_image', $welcome_image);
        }

        if($request->hasFile('welcome_image_driver')) {
            $welcome_image_driver = Helper::upload_picture($request->file('welcome_image_driver'));
            Setting::set('welcome_image_driver', $welcome_image_driver);
        }

        if($request->hasFile('site_email_logo')) {
            $site_email_logo = Helper::upload_picture($request->file('site_email_logo'));
            Setting::set('site_email_logo', $site_email_logo);
        }

        Setting::set('site_title', $request->site_title);
        Setting::set('store_link_android', $request->store_link_android);
        Setting::set('store_link_ios', $request->store_link_ios);
        
        Setting::set('sos_number', $request->sos_number);
        Setting::set('Eganow_sos_number', $request->Eganow_sos_number);
        Setting::set('contact_number', $request->contact_number);
        Setting::set('contact_email', $request->contact_email);
        Setting::set('driver_link', $request->driver_link);
        Setting::set('user_link', $request->user_link);
        Setting::set('android_driver_version', $request->android_driver_version);
        Setting::set('android_user_version', $request->android_user_version);
        Setting::set('ios_driver_version', $request->ios_driver_version);
        Setting::set('ios_user_version', $request->ios_user_version);

        Setting::set('android_driver_mapkey', $request->android_driver_mapkey);
        Setting::set('android_user_mapkey', $request->android_user_mapkey);
        Setting::set('ios_driver_mapkey', $request->ios_driver_mapkey);
        Setting::set('ios_user_mapkey', $request->ios_user_mapkey);
        
        Setting::save();
        
        return back()->with('flash_success','Settings Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function settings_payment()
    {
        return view('admin.payment.settings');
    }

    public function settings_ride()
    {
 
        return view('admin.settings.ride');
    }

    public function settings_referral()
    {
        return view('admin.settings.referral');
    }

    /**
     * Save payment related settings.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function settings_payment_store(Request $request)
    {

            Setting::set('daily_target', $request->daily_target);
            Setting::set('tax_percentage', $request->tax_percentage);
            Setting::set('commission_percentage', $request->commission_percentage);
            Setting::set('currency', $request->currency);
            Setting::set('referral_bonus', $request->referral_bonus);
            Setting::set('minimum_balance', $request->minimum_balance);
            Setting::set('cash_offer', $request->cash_offer);
            Setting::set('monthly_commission', $request->monthly_commission);
            Setting::set('ambassadors_online_bonus', $request->ambassadors_online_bonus);
            Setting::set('active_hours_limit', $request->active_hours_limit);

            Setting::set('reward_hours_limit', $request->reward_hours_limit);
            
            Setting::set('ambassadors_commission_discount', $request->ambassadors_commission_discount);
            Setting::set('available_balance_time', $request->available_balance_time);

            Setting::set('trip_completed_bonus', $request->trip_completed_bonus);
            Setting::set('manager_approval_limit', $request->manager_approval_limit);
            
        
        Setting::save();

        return back()->with('flash_success','Payment Settings Updated Successfully');
    }

    public function settings_ride_store(Request $request)
    {
        Setting::set('surge_percentage', $request->surge_percentage);
        Setting::set('surge_trigger', $request->surge_trigger);
        Setting::set('active_hours_limit', $request->active_hours_limit);
        Setting::set('promotion_count', $request->promotion_count);
        Setting::set('location_update_interval', $request->location_update_interval);
        Setting::set('provider_select_timeout', $request->provider_select_timeout);
        Setting::set('trip_search_time', $request->trip_search_time);
        Setting::set('provider_search_radius', $request->provider_search_radius);
        Setting::set('fleet_search_radius', $request->fleet_search_radius);
        Setting::set('booking_prefix', $request->booking_prefix);

        Setting::set('day_start', $request->day_start);
        Setting::set('day_end', $request->day_end);
        Setting::set('night_start', $request->night_start);
        Setting::set('night_end', $request->night_end);

        Setting::set('driver_break_time', $request->driver_break_time);
        Setting::set('drivenow_start', $request->drivenow_start);
        Setting::set('drivenow_end', $request->drivenow_end);
        Setting::set('global_engine', $request->global_engine);
        Setting::set('drivenow_due_engine_control', $request->drivenow_due_engine_control);

        

        Setting::save();

        return back()->with('flash_success','Ride Settings Updated Successfully');
    }

    public function settings_referral_store(Request $request)
    {

        Setting::set('ambassadors_to_user_referral', $request->ambassadors_to_user_referral);
        Setting::set('ambassadors_to_driver_referral', $request->ambassadors_to_driver_referral);

        Setting::set('user_to_user_referral', $request->user_to_user_referral);
        Setting::set('user_to_driver_referral', $request->user_to_driver_referral);

        Setting::set('driver_to_user_referral', $request->driver_to_user_referral);
        Setting::set('driver_to_driver_referral', $request->driver_to_driver_referral);

        Setting::set('marketer_to_user_referral', $request->marketer_to_user_referral);
        Setting::set('marketer_to_driver_referral', $request->marketer_to_driver_referral);

        Setting::set('work_pay_to_user_referral', $request->work_pay_to_user_referral);
        Setting::set('work_pay_to_driver_referral', $request->work_pay_to_driver_referral);
        
        Setting::save();

        return back()->with('flash_success','Referral Settings Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        

        $pre_balance = array('7886' => '5400','6764' => '8950','7409' => '9850','8435' => '2350','8349' => '3150','8144' => '4150','4558' => '9900','7752' => '7200','8129' => '4250','1095' => '9450','5987' => '1800','7774' => '7200','8439' => '2500','7457' => '7200','1628' => '9000','7496' => '8450','8088' => '5850','7502' => '10350','6898' => '8500','7394' => '10000','8369' => '2700');
        foreach ($pre_balance as $key => $value) {
            $official_driver = OfficialDriver::where('driver_id', $key)->first();
            if($official_driver->pre_balance == ''){
                $official_driver->pre_balance = $value;
                $official_driver->save();
            }
        }
        $official_drivers = OfficialDriver::where('status', '!=', 1)->get();
        foreach ($official_drivers as $off_driver) {
            $transaction = DriveNowRaveTransaction::where('driver_id', $off_driver->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->where('slp_ref_id', 'not Like', '%DriveNow_IA%')->sum('amount');
            Log::info($off_driver->driver_id." - ".$transaction ."(".$off_driver->amount_paid.")");

            $amount_paid = ($transaction + $off_driver->pre_balance);
            // if($off_driver->initial_amount > 0){
            //     $amount_paid = $amount_paid - $off_driver->initial_amount;
            // }
            $off_driver->amount_paid = $amount_paid;
            $off_driver->save();
            Log::info($off_driver->driver_id ." - ". $off_driver->amount_paid);
        }
        return view('admin.account.profile');
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
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@Eganow.com');
        }

        $this->validate($request,[
            'name' => 'required|max:255',
            'mobile' => 'required|between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        ]);

        try{
            $admin = Auth::guard('admin')->user();
            $admin->name = $request->name;
            $admin->email = $request->email;
            $admin->mobile = $request->mobile;
            if($request->hasFile('picture')){
                $admin->picture = Helper::upload_picture($request->file('picture'));
            }
            $admin->save();

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
        return view('admin.account.change-password');
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
            return back()->with('flash_error','Disabled for demo purposes! Please contact us at info@Eganow.com');
        }

        // $this->validate($request,[
        //     'old_password' => 'required',
        //     'password' => 'required|min:6|confirmed',
        // ]);

        try {

           $Admin = Admin::find(Auth::guard('admin')->user()->id);

            if(password_verify($request->old_password, $Admin->password))
            {
                $Admin->password = bcrypt($request->password);
                $Admin->save();

                return redirect()->back()->with('flash_success','Password Updated');
            }
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function payment(Request $request)
    {
        try {
            $data = "0";
            $transactions = RaveTransaction::with('request', 'user', 'provider')->whereNotNull('driver_id')->orderBy('created_at', 'desc')->get();

            if($request->has('filter')){
                if($request->filter ==1){
                     $data = "1";
                    $transactions = RaveTransaction::with('request', 'user', 'provider')->whereNotNull('user_id')->orderBy('created_at', 'desc')->get();
                }
             
            }
            return view('admin.payment.payment-history', compact('transactions', 'data'));
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

     public function request_payment()
    {
        try {
             $payments = UserRequests::where('paid', 1)
                    ->has('user')
                    ->has('provider')
                    ->has('payment')
                    ->orderBy('user_requests.created_at','desc')
                    ->paginate(300);
            
            return view('admin.payment.request_payment', compact('payments'));
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function help()
    {
        try {
            $str = file_get_contents('http://Eganow.com/help.json');
            $Data = json_decode($str, true);
            return view('admin.help', compact('Data'));
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * User Rating.
     *
     * @return \Illuminate\Http\Response
     */
    public function user_review()
    {
        try {
            $Reviews = UserRequestRating::where('user_id', '!=', 0)->with('user', 'provider','request')->orderBy('created_at', 'desc')->get();
            return view('admin.review.user_review',compact('Reviews'));
        } catch(Exception $e) {
            return redirect()->route('admin.setting')->with('flash_error','Something Went Wrong!');
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
            $Reviews = UserRequestRating::where('provider_id','!=',0)->with('user','provider','request')->orderBy('created_at', 'desc')->get();
            return view('admin.review.provider_review',compact('Reviews'));
        } catch(Exception $e) {
            return redirect()->route('admin.setting')->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ProviderService
     * @return \Illuminate\Http\Response
     */
    public function destroy_provider_service($id){
        try {
            ProviderService::find($id)->delete();
            return back()->with('flash_success', 'Service deleted successfully');
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Testing page for push notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function push_index()
    {
        $data = PushNotification::app('IOSUser')
            ->to('163e4c0ca9fe084aabeb89372cf3f664790ffc660c8b97260004478aec61212c')
            ->send('Hello World, i`m a push message');
        dd($data);

        $data = PushNotification::app('IOSProvider')
            ->to('a9b9a16c5984afc0ea5b681cc51ada13fc5ce9a8c895d14751de1a2dba7994e7')
            ->send('Hello World, i`m a push message');
        dd($data);
    }

    public function custom_push(){
        $mes = '';
        $custom_pushes = CustomPushes::orderBy('created_at','desc')->get();

        // $client1 = new \GuzzleHttp\Client();

        //     $url1 = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&getBalance=true";


        //     $headers1 = ['Content-Type' => 'application/json'];
            
        //     $res1 = $client1->get($url1, ['headers' => $headers1]);

        //     $data = $res1->getBody();

        //     $balance = round(str_replace("Messaging balance for API User: f3En@x is","", $data));
        $balance = 0;
            return view('admin.push.custom_push', compact('mes', 'custom_pushes', 'balance'));
    }

    public function send_individual_push(Request $request){

        try {
            $type = $request->push_type;
            $message = $request->custom_message;
            $group = $request->group;
            $title = $request->title;

            $sender = Auth::guard('admin')->user();
            $custom_push = New IndividualPush;
            $custom_push->sender_id = $sender->id;
            $custom_push->message = $message;
            $custom_push->type = $type;

            //Send Push / SMS
            switch ($group) {
                case 'user':
                    $custom_push->user_id = $request->user_id;
                    $user = User::findOrFail($request->user_id);
                    if($type == "push"){
                       (new SendPushNotification)->CustomPushUser($user->id, $message, $title);            
                    }else if($type == "sms"){
                        pushSMS($user->country_code, $user->mobile, $message);
                    }else if($type == 'both'){
                        (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                        pushSMS($user->country_code, $user->mobile, $message);
                    }
                break;

                case 'driver':
                    $custom_push->driver_id = $request->driver_id;
                    $driver = Provider::findOrFail($request->driver_id);
                    if($type == "push"){
                       (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);            
                    }else if($type == "sms"){
                        pushSMS($driver->country_code, $driver->mobile, $message);
                    }else if($type == 'both'){
                        (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        pushSMS($driver->country_code, $driver->mobile, $message);
                    }
                break;
            }

            $custom_push->save();
            
            return back()->with('flash_success', 'Custom Push / SMS has been sent successfully');
        } catch (Exception $e) {
            Log::info($e);
            $mes = 'error';
          
            return back()->with('flash_error', 'Something Went Wrong! Custom Push / SMS failed');

        }
    }

    

    public function send_custom_push(Request $request){

            $client1 = new \GuzzleHttp\Client();

            $url1 = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&getBalance=true";


            $headers1 = ['Content-Type' => 'application/json'];
            
            $res1 = $client1->get($url1, ['headers' => $headers1]);

            $data = $res1->getBody();

            $balance = round(str_replace("Messaging balance for API User: f3En@x is","", $data));
        try {
            $group = $request->audience;
            $type = $request->push_type;
            $title = $request->title;
            $device_type = $request->device_type;
            $message = $request->custom_message;
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));

            $sender = Auth::guard('admin')->user();
            $custom_push = New CustomPushes;
            $custom_push->sender_id = $sender->id;
            $custom_push->message = $message;
            $custom_push->group = $group;
            $custom_push->type = $type;
            $custom_push->range = $request->filter_date;

            switch ($group) {
                case 'all_users':

                    if($device_type == "android"){
                        $users = User::where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "ios"){
                        $users = User::where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "both"){
                        $users = User::where('archive','0')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                        
                        $custom_push->receiver_count = count($users);
                    if($type == "push"){
                        foreach ($users as $user) {
                           (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($users as $user) {
                            pushSMS($user->country_code, $user->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($users as $user) {
                                (new SendPushNotification)->CustomPushUser($user->id, $message, $title);

                                pushSMS($user->country_code, $user->mobile, $message);
                                
                        }
                    }
                    break;

                    case 'not_verified_users':

                        if($device_type == "android"){
                            $users = User::where('first_name','=', '')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('device_type', $device_type)->get();
                        }
                        if($device_type == "ios"){
                           $users = User::where('first_name','=', '')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('device_type', $device_type)->get();
                        }
                        if($device_type == "both"){
                            $users = User::where('first_name','=', '')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                        
                        $custom_push->receiver_count = count($users);

                        if($type == "push"){
                            
                            foreach ($users as $user) {
                               (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                            }
                            
                        }else if($type == "sms"){
                            foreach ($users as $user) {
                                    pushSMS($user->country_code, $user->mobile, $message);
                            }
                            
                        }else if($type == 'both'){
                            foreach ($users as $user) {
                                    (new SendPushNotification)->CustomPushUser($user->id, $message, $title);

                                    pushSMS($user->country_code, $user->mobile, $message);
                                    
                        }
                    }
                    break;

                    case 'verified_users':
                        if($device_type == "android"){
                            $users = User::where('first_name','!=', '')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('device_type', $device_type)->get();
                        }
                        if($device_type == "ios"){
                           $users = User::where('first_name','!=', '')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('device_type', $device_type)->get();
                        }
                        if($device_type == "both"){
                            $users = User::where('first_name','!=', '')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                        $custom_push->receiver_count = count($users);

                        if($type == "push"){
                            
                            foreach ($users as $user) {
                               (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                            }
                            
                        }else if($type == "sms"){
                            
                            foreach ($users as $user) {
                                    pushSMS($user->country_code, $user->mobile, $message);
                            }
                            
                        }else if($type == 'both'){
                            
                            foreach ($users as $user) {
                                    (new SendPushNotification)->CustomPushUser($user->id, $message, $title);

                                    pushSMS($user->country_code, $user->mobile, $message);
                                    
                            }
                        }
                    break;

                    case 'all_drivers':

                        if($device_type == "android"){
                            $drivers = Provider::where('archive','!=', 1)->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('archive','!=', 1)->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('archive','!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                        
                        $custom_push->receiver_count = count($drivers);
                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                                
                        }
                    }
                    break;
                case 'online_drivers':

                    if($device_type == "android"){
                            $drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                    
                    $custom_push->receiver_count = count($drivers);
                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                    }
                    break;
                case 'offline_drivers':
                    if($device_type == "android"){
                            $drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                    $custom_push->receiver_count = count($drivers);
                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                                
                        }
                    }
                    break;
                case 'approved_drivers':

                    if($device_type == "android"){
                            $drivers = Provider::where('status','approved')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('status','approved')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('status','approved')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                    
                    $custom_push->receiver_count = count($drivers);
                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                    }
                    break;
                case 'ambassador':
                    
                    if($device_type == "android"){
                            $drivers = Provider::where('ambassador','1')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('ambassador','1')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('ambassador','1')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }
                    
                    $custom_push->receiver_count = count($drivers);
                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                    }
                    break;
                 case 'promo_driver':

                        if($device_type == "android"){
                            $drivers = Provider::where('promo_driver','1')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('promo_driver','1')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('promo_driver','1')->where('archive', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        }

                    $custom_push->receiver_count = count($drivers);
                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                    }
                    break;

                     case 'official_drivers':

                    if($device_type == "android"){
                            $drivers = Provider::where('official_drivers','1')->where('archive', '!=', 1)->whereBetween('agreed_on',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "ios"){
                           $drivers = Provider::where('official_drivers','1')->where('archive', '!=', 1)->whereBetween('agreed_on',[date($dates[0]), date($dates[1])])->whereHas('device', function($query) use ($device_type) {
                        $query->where('type','=', $device_type);})->get();
                        }
                        if($device_type == "both"){
                            $drivers = Provider::where('official_drivers','1')->where('archive', '!=', 1)->whereBetween('agreed_on',[date($dates[0]), date($dates[1])])->get();
                        }
                    // dd(count($drivers));
                    $custom_push->receiver_count = count($drivers);

                    if($type == "push"){
                        foreach ($drivers as $driver) {
                           (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);
                        }
                        
                    }else if($type == "sms"){
                        foreach ($drivers as $driver) {
                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                        
                    }else if($type == 'both'){
                        foreach ($drivers as $driver) {
                                (new SendPushNotification)->CustomPushDriver($driver->id, $message, $title);

                                pushSMS($driver->country_code, $driver->mobile, $message);
                        }
                    }
                    break;

                case 'booked_users':

                    if($device_type == "android"){
                        $users = User::whereHas('trips')->where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "ios"){
                        $users = User::whereHas('trips')->where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "both"){
                        $users = User::whereHas('trips')->where('archive','0')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }

                        $custom_push->receiver_count = count($users);

                        if($type == "push"){
                            
                            foreach ($users as $user) {
                               (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                            }
                            
                        }else if($type == "sms"){
                            
                            foreach ($users as $user) {
                                    pushSMS($user->country_code, $user->mobile, $message);
                            }
                            
                        }else if($type == 'both'){
                            
                            foreach ($users as $user) {
                                    (new SendPushNotification)->CustomPushUser($user->id, $message, $title);

                                    pushSMS($user->country_code, $user->mobile, $message);
                                    
                            }
                        }
                    break;
                case 'cancelled_request_users':

                    if($device_type == "android"){
                        $users = User::whereHas('trips', function($query) {
                        $query->where('status','=', 'CANCELLED');})->where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "ios"){
                        $users = User::whereHas('trips', function($query) {
                        $query->where('status','=', 'CANCELLED');})->where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "both"){
                        $users = User::whereHas('trips', function($query) {
                        $query->where('status','=', 'CANCELLED');})->where('archive','0')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }

                        $custom_push->receiver_count = count($users);

                        if($type == "push"){
                            
                            foreach ($users as $user) {
                               (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                            }
                            
                        }else if($type == "sms"){
                            
                            foreach ($users as $user) {
                                    pushSMS($user->country_code, $user->mobile, $message);
                            }
                            
                        }else if($type == 'both'){
                            
                            foreach ($users as $user) {
                                    (new SendPushNotification)->CustomPushUser($user->id, $message, $title);

                                    pushSMS($user->country_code, $user->mobile, $message);
                                    
                            }
                        }
                    break;

                case 'completed_request_users':

                    if($device_type == "android"){
                        $users = User::whereHas('trips', function($query) {
                        $query->where('status', 'COMPLETED');})->where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "ios"){
                        $users = User::whereHas('trips', function($query) {
                        $query->where('status', 'COMPLETED');})->where('archive','0')->where('device_type', $device_type)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }
                    if($device_type == "both"){
                        $users = User::whereHas('trips', function($query) {
                        $query->where('status', 'COMPLETED');})->where('archive','0')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    }

                        $custom_push->receiver_count = count($users);

                        if($type == "push"){
                            
                            foreach ($users as $user) {
                               (new SendPushNotification)->CustomPushUser($user->id, $message, $title);
                            }
                            
                        }else if($type == "sms"){
                            
                            foreach ($users as $user) {
                                    pushSMS($user->country_code, $user->mobile, $message);
                            }
                            
                        }else if($type == 'both'){
                            
                            foreach ($users as $user) {
                                    (new SendPushNotification)->CustomPushUser($user->id, $message, $title);

                                    pushSMS($user->country_code, $user->mobile, $message);
                                    
                            }
                        }
                    break;
            }
            $custom_push->save();
            $mes = 'success';
            $custom_pushes = CustomPushes::all();

            return view('admin.push.custom_push', compact('mes', 'custom_pushes', 'balance'));
        } catch (Exception $e) {
            Log::info($e);
            $mes = 'error';
            $custom_pushes = CustomPushes::all();
            return view('admin.push.custom_push', compact('mes', 'custom_pushes', 'balance'));       
        }
    }

    /**
     * Testing page for push notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function push_store(Request $request)
    {
        try {
            ProviderService::find($id)->delete();
            return back()->with('flash_success', 'Service deleted successfully');
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * Used to Get Provider
     */
    public function getProvider(Request $request){        
        $user = User::find($request->id);
        if($user->fleet != '')
            $fleets = Fleet::where('id', $user->fleet)->get(); 
        else
            $fleets = Fleet::all();                                                                                   
        $html = '';
        $html .= '<option value="">Choose Fleet</option>';
        if(!empty($fleets)){
            foreach($fleets as $fleet){
                if($fleet->id != '' && $fleet->name != ''){
                    $html .= '<option value="'.@$fleet->id.'">'.@$fleet->name.'</option>';
                }
            }
        }    
        return $html;        
    }

    /**
     * Used to Get Driver
     */
    public function getDriver(Request $request){
        $drivers = Provider::where('fleet', $request->id)->get();
        $fleetservices = FleetPrice::with('service')->where('fleet_id', $request->id)->get();
        if(empty($drivers))
            $drivers = Provider::all();
        $html = '';
        $html .= '<option value="">Select Driver</option>';
        if(!empty($drivers)){
            foreach($drivers as $driver){
                $html .= '<option value="'.$driver->id.'">'.$driver->first_name.' '.$driver->last_name.'</option>';
            }
        }
        $service = '';
        $service .= '<option value="">Select Service</option>';
        if(!empty($fleetservices)){
            foreach($fleetservices as $fleetservice){
                if(!empty($fleetservice->service)){
                    $service .= '<option value="'.$fleetservice->service->id.'">'.$fleetservice->service->name.'</option>';
                }
            }
        }else{
            $fleetservices = ServiceType::all();
            foreach($fleetservices as $service){
                $service .= '<option value="'.$service->id.'">'.$service->name.'</option>';
            } 
        }
        return response()->json(['driver' => $html, 'service' => $service]);
    }

    /**
     * privacy.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */

    public function privacy(){
        return view('admin.pages.static')
            ->with('title',"Privacy Page")
            ->with('page', "privacy");
    }

    /**
     * pages.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function pages(Request $request){
        $this->validate($request, [
                'page' => 'required|in:page_privacy',
                'content' => 'required',
            ]);

        Setting::set($request->page, $request->content);
        Setting::save();

        return back()->with('flash_success', 'Content Updated!');
    }

    /**
     * account statements.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function statement($type = 'individual'){
        try{

            $page = 'Ride Statement';

            if($type == 'individual'){
                $page = 'Provider Ride Statement';
            }elseif($type == 'today'){
                $page = 'Today Statement - '. date('d M Y');
            }elseif($type == 'monthly'){
                $page = 'This Month Statement - '. date('F');
            }elseif($type == 'yearly'){
                $page = 'This Year Statement - '. date('Y');
            }

            $rides = UserRequests::with('payment')->orderBy('id','desc');
            $cancel_rides = UserRequests::where('status','CANCELLED');
            $revenue = UserRequestPayment::select(\DB::raw(
                           'SUM(ROUND(fixed) + ROUND(distance)) as overall, SUM(ROUND(commision)) as commission' 
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

            return view('admin.providers.statement', compact('rides','cancel_rides','revenue'))
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
        dd('hello');
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
            if($fleet != ' '){
                $fleets = Fleet::find($fleet);
                $page = $fleets->name.'\'s Drivers Statement';
                $Providers = Provider::where('fleet', $fleet)->get();
            }
            else{
                $Providers = Provider::all();
                $page = 'Drivers Statement';
            }

            foreach($Providers as $index => $Provider){

                $Rides = UserRequests::where('provider_id',$Provider->id)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

                $Providers[$index]->rides_count = $Rides->count();

                $Providers[$index]->payment = UserRequestPayment::whereIn('request_id', $Rides)
                                ->select(\DB::raw(
                                   'SUM(ROUND(driver_earnings)) as overall, SUM(ROUND(drivercommision)) as commission' 
                                ))->get();
            }

            return view('admin.providers.provider-statement', compact('Providers'))->with('page', $page);

        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    public function statement_service(){

        try{
            $services = ServiceType::all();

            $page = 'Statement by Services';
            foreach($services as $index => $service){

                $Rides = UserRequests::where('service_type_id',$service->id)
                            ->where('status','<>','CANCELLED')
                            ->get()->pluck('id');

                $services[$index]->rides_count = $Rides->count();

                $services[$index]->payment = UserRequestPayment::whereIn('request_id', $Rides)
                                ->select(\DB::raw(
                                   'SUM(ROUND(fixed) + ROUND(distance)) as overall' 
                                ))->get();
            }

            return view('admin.providers.service-statement', compact('services'))->with('page', $page);

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

            return view('admin.providers.fleet-statement', compact('Providers'))->with('page','Fleet Statement');

        } catch (Exception $e) {
            
            $Providers = Fleet::all();
            return view('admin.providers.fleet-statement', compact('Providers'))->with('page','Fleet Statement');
        }
    }

    /**
     * Used to show owner payout details
     */
    public function ownerPayouts()
    {
        try {
            $requests = UserRequests::with('payment','service_type','user','provider')->where(['status' => 'COMPLETED', 'payment_mode' => 'CARD', 'owner_payout' => 0])->get();
            return view('admin.payouts.ownerPayouts', compact('requests'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }
    } 

    /**
     * Used to show driver payout details
     */
    public function driverPayouts()
    {
        try {
            $requests = UserRequests::with('payment','service_type','user','provider')->where(['status' => 'COMPLETED', 'payment_mode' => 'CARD', 'driver_payout' => 0])->get();
            return view('admin.payouts.driverPayouts', compact('requests'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }
    }

    /**
     * Used to Pay Owner Payouts
     */
    public function PayOwnerPayouts(Request $request, $type)
    {
        try {
            $requests = UserRequests::with('provider_profiles','payment')->whereIn("id", $request->id)->get();                  
            $bulkdata = [];
            if(!empty($requests))
            {
                foreach($requests as $index => $bank)
                {
                    $commission = ($type == 'owner') ? $bank->payment->commision : $bank->payment->drivercommision;
                    $data = [
                        'Bank'              =>  $bank->provider_profiles->bank_code,
                        'Account Number'    =>  $bank->provider_profiles->acc_no,
                        'Amount'            =>  $commission,
                        'Currency'          =>  'GHS',
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
                $sql = UserRequests::whereIn("id",$request->id);
                if($type == 'owner')
                    $sql->update(['owner_payout' => 1, 'owner_payment_id' => $id]);                    
                else
                    $sql->update(['driver_payout' => 1, 'driver_payment_id' => $id]);  
                return back()->with('flash_success', 'Payment sent to '.$type.' successfully');                                           
            }else{ 
                return back()->with('flash_error', 'Unable to made payment. Please try again');
            }    
        } catch (\Throwable $th) {
            return back()->with('flash_error','Something went wrong');
        }
    }

        public function updateDocument(Request $request)
    {
        $this->validate($request, [
                'document' => 'mimes:jpg,jpeg,png,pdf',
            ]);

        try {
            
            $Document = ProviderDocument::where('provider_id', $request->provider_id)
                ->where('document_id', $request->document_id)
                ->firstOrFail();

            $url = Helper::upload_picture($request->document);
            $Document->url = $url;
            $Document->status = 'ASSESSING';
            $Document->save();

        } catch (Exception $e) {
            
            $url = Helper::upload_picture($request->document);
            
            $Document = new ProviderDocument;
            $Document->url = $url;
            $Document->provider_id = $request->provider_id;
            $Document->document_id = $request->document_id;
            $Document->status = 'ASSESSING';
            $Document->save();
            
        }
        
        return back()->with('flash_success','Document Uploaded successfully');
        
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

     public function sendEstd(Request $request){
        try{
            $otp = rand(100000, 999999);
            $to = $request->to;
            $from = "Eganow";
            $content = urlencode("Hi ".$request->name.",
Please see your Eganow request details below:

Pickup: ".$request->pickup."
Destination: ".$request->drop."
Service: ".$request->service_type."
Estd Price: ".$request->estd_price."
Estd Time: ".$request->estd_time."

Try our apps:
Android: https://bit.ly/364cAoL
iOS: https://apple.co/362diCT");
            $clientId = env("HUBTEL_API_KEY");
            $clientSecret = env("HUBTEL_API_SECRET");

            
            $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);

            if($sendSms == FALSE){
                return back()->with('flash_error','SMS Configuration Error');
            }else{
                return back()->with('flash_success','Esitmation details sent to Customer');
            }
        }
        catch (Exception $e) {
            
             return back()->with('flash_error','SMS Configuration Error');
        }
    }

    public function post_driver_comment(Request $request){
        
        $moderator = Auth::guard('admin')->user();
        $driver = Provider::where('id',$request->driver_id)->first();
        $DriverComment = New DriverComments;
        if ($request->hasFile('attachment')) {
            $name = $driver->first_name."-profile-".str_replace(' ','_',Carbon::now());
            $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/driver_profile';                    
            $contents = file_get_contents($request->attachment);
            $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
            $s3_url = $baseurl.'/'.$name;
            $DriverComment->attachment = $s3_url;
        }

        $DriverComment->comments = $request->comment;
        $DriverComment->marketer_id = $moderator->id;
        $DriverComment->driver_id = $request->driver_id;
        $DriverComment->save();

        return back()->with('flash_success','Comments Posted');
    }

    public function edit_driver_comment(Request $request){
        
        try{
            $moderator = Auth::guard('admin')->user();
            $driver = Provider::where('id',$request->driver_id)->first();
            $DriverComment = DriverComments::find($request->comment_id);

            $DriverComment->comments = $request->comment;
            $DriverComment->marketer_id = $moderator->id;
            $DriverComment->driver_id = $request->driver_id;
            $DriverComment->save();
            $response = array();
            $response['comments'] = $DriverComment->comments;
            $response['commentor'] = $moderator->name;
            $response['updated_time'] = date('F d, Y H:i', strtotime($DriverComment->updated_at));
            Log::info($response);

            return $response;
        }catch(Exception $e){
            Log::info($e);
            return back()->with('flash_error','Something Went Wrong! Try Again.');
        }
        
    }

    public function delete_driver_comment(Request $request){
        
        $moderator = Auth::guard('admin')->user();
        $DriverComment = DriverComments::find($request->comment_id);
        $DriverComment->marketer_id = $moderator->id;
        $DriverComment->delete();

        return back()->with('flash_success','Comment deleted');
    }

    public function post_user_comment(Request $request){
        
        $moderator = Auth::guard('admin')->user();
        $UserComment = New UserComments;
        $UserComment->comments = $request->comment;
        $UserComment->marketer_id = $moderator->id;
        $UserComment->user_id = $request->user_id;
        $UserComment->save();

        return back()->with('flash_success','Comments Posted');
    }

        public function edit_user_comment(Request $request){
        
        try{
            $moderator = Auth::guard('admin')->user();
            $user = User::where('id',$request->user_id)->first();
            $UserComment = UserComments::find($request->comment_id);

            $UserComment->comments = $request->comment;
            $UserComment->marketer_id = $moderator->id;
            $UserComment->user_id = $request->user_id;
            $UserComment->save();
            $response = array();
            $response['comments'] = $UserComment->comments;
            $response['commentor'] = $moderator->name;
            $response['updated_time'] = date('F d, Y H:i', strtotime($UserComment->updated_at));
            Log::info($response);

            return $response;
        }catch(Exception $e){
            Log::info($e);
            return back()->with('flash_error','Something Went Wrong! Try Again.');
        }
        
    }

    public function delete_user_comment(Request $request){
        
        $moderator = Auth::guard('admin')->user();
        $UserComment = UserComments::find($request->comment_id);
        $UserComment->marketer_id = $moderator->id;
        $UserComment->delete();

        return back()->with('flash_success','Comment deleted');
    }

    public function upload_driver_profile_s3(){

        $driver_profiles = Provider::whereRaw('avatar is not null')->get();
        foreach ($driver_profiles as $driver) {
           if($driver->avatar != 'null' && $driver->avatar != '' && !strpos($driver->avatar, 'Eganowuploads') ){
                    $name = $driver->id."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/driver_profile';                    
                    $contents = file_get_contents($driver->avatar);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $picture = str_replace("https://Eganow.online/uploads/", "", $driver->avatar);
                    File::delete( public_path() . "/uploads/" . $picture);
                    $driver->avatar = $s3_url;
                    $driver->save(); 
            }      
        }

        return back()->with('flash_success','Uploaded to S3 Bucket');
    }

    public function upload_driver_docs_s3(){

        $driver_documents = ProviderDocument::whereRaw('url is not null')->get();
        foreach ($driver_documents as $driver) {
           if($driver->url != 'null' && $driver->url != '' && !strpos($driver->url, 'Eganowuploads') ){
                    $name = $driver->provider_id."-doc-".$driver->id."-".str_replace(' ','_',Carbon::now());
                    $picture = str_replace("https://Eganow.online/uploads/", "", $driver->url);
                    $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/driver_documents';                    
                    $contents = file_get_contents($driver->url);
                    $path = Storage::disk('s3')->put('driver_documents/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    File::delete( public_path() . "/uploads/" . $picture);
                    $driver->url = $s3_url;
                    $driver->save();             
            }      
        }

        return back()->with('flash_success','Uploaded to S3 Bucket');
    }

    public function upload_driver_cars_s3(){

        $driver_cars = ProviderProfile::whereRaw('car_picture is not null')->get();
        foreach ($driver_cars as $driver) {
           if($driver->car_picture != 'null' && $driver->car_picture != '' && !strpos($driver->car_picture, 'Eganowuploads') ){
                    $name = $driver->provider_id."-car-".$driver->id."-".str_replace(' ','_',Carbon::now());
                    $picture = str_replace("https://Eganow.online/uploads/", "", $driver->car_picture);
                    $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/driver_cars';                    
                    $contents = file_get_contents($driver->car_picture);
                    $path = Storage::disk('s3')->put('driver_cars/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    File::delete( public_path() . "/uploads/" . $picture);
                    $driver->car_picture = $s3_url;
                    $driver->save(); 
            }      
        }

        return back()->with('flash_success','Uploaded to S3 Bucket');
    }

    public function upload_user_profile_s3(){

        $user_profile = User::whereRaw('picture is not null')->get();
        foreach ($user_profile as $driver) {
           if($driver->picture != 'null' && $driver->picture != '' && !strpos($driver->picture, 'Eganowuploads') ){
                    $name = $driver->id."-user-".str_replace(' ','_',Carbon::now());
                    $picture = str_replace("https://Eganow.online/uploads/", "", $driver->picture);
                    $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/user_profile';                    
                    $contents = file_get_contents($driver->picture);
                    $path = Storage::disk('s3')->put('user_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    File::delete( public_path() . "/uploads/" . $picture);
                    $driver->picture = $s3_url;
                    $driver->save();             
            }      
        }

        return back()->with('flash_success','Uploaded to S3 Bucket');
    }


        public function manager()
    {
        $fleets = Fleet::where('roles', '1')->orderBy('created_at' , 'desc')->get();
        return view('admin.manager.index', compact('fleets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create_driver_manager()
    {        
        try {
            
            return view('admin.manager.create');
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
    public function store_driver_manager(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255',
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
                
                
                $fleet->latitude = $request->latitude;
                $fleet->longitude = $request->longitude;
                $fleet->address = $request->address;
                $fleet->roles = 1;

                $fleet->save();  
                
                return back()->with('flash_success','Driver Manager Details Saved Successfully');
                
        } 

        catch (Exception $e) {
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
    public function edit_driver_manager($id)
    {
        try {
            $fleet = Fleet::with('fleet_subaccount')->findOrFail($id);
                                 
            return view('admin.manager.edit',compact('fleet'));
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
    public function update_driver_manager(Request $request)
    {
        
        $this->validate($request, [
            'name' => 'required|max:255',
            'mobile' => 'between:6,13',
            'logo' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            
        ]);

        try {
            $id = $request->id;
            $fleet = Fleet::with('fleet_subaccount')->findOrFail($id);

                
            $fleet->name = $request->name;
            $fleet->email = $request->email;
            $fleet->company = $request->company;
            $fleet->mobile = $request->mobile;
            
           if($request->has('latitude')){
                $fleet->latitude = $request->latitude;
            }
            if($request->has('longitude')){
                $fleet->longitude = $request->longitude;
            }
            if($request->has('address')){
                $fleet->address = $request->address;
            }
            $fleet->save();
            

            return back()->with('flash_success', 'Driver Manager Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Driver Manager Not Found');
        }
    }

    public function delete_driver_manager($id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@Eganow.com');
        }
        
        try {
            Fleet::find($id)->delete();
            return back()->with('flash_success', 'Driver Manager deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Fleet Not Found');
        }
    }

    public function PayReward(Request $request, $id)
    {
        try {

            $UserRequest = UserRequests::where("id", $request->id)->where('status', 'COMPLETED')->where('reward_status', '==', 1)->first();
            if($UserRequest){
                return back()->with('flash_error', 'Reward Paid Already!');
            }else{
            
            $UserRequest = UserRequests::where("id", $request->id)->where('status', 'COMPLETED')->first();

            $driver = Provider::where('id', $UserRequest->provider_id)->first();

            $code = rand(1000, 9999);
            $reference = "TRWD".$code;
            $rave_transactions = new RaveTransaction;
            $rave_transactions->driver_id = $driver->id;
            $rave_transactions->last_balance = $driver->wallet_balance;
            $rave_transactions->last_availbale_balance = $driver->available_balance;
            $rave_transactions->reference_id = $reference;
            $rave_transactions->narration = "Eganow Bonus for the trip: " . $UserRequest->booking_id;
            $rave_transactions->amount = Setting::get('trip_completed_bonus', 0);
            $rave_transactions->status = 1;
            $rave_transactions->type = "credit";
            $rave_transactions->credit = 0;
            $rave_transactions->save();

            $driver->wallet_balance = $driver->wallet_balance + Setting::get('trip_completed_bonus', 0);

            $driver->save();

            $UserRequest->reward_status = 1;
            $UserRequest->reward = Setting::get('trip_completed_bonus', 0);
            $UserRequest->save();

            

            return back()->with('flash_success', 'Reward credited to driver wallet successfully!');
            }

        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function wallet_balances(){
        try{

            $available_balance_duration = Setting::get('available_balance_time', '24');
            $available_transactions = RaveTransaction::where('driver_id', '!=', '')->where('status','=', 1)->where('type', 'credit')->where('credit', 0)->where('created_at', '<=', Carbon::now()->subHours($available_balance_duration))->orderBy('created_at', 'desc')->get();
            foreach ($available_transactions as $available_transaction) {
                $driver = Provider::where('id', $available_transaction->driver_id)->first();
            
                $available_transaction->last_availbale_balance = $driver->available_balance;
                if($available_transaction->type == 'credit' && $available_transaction->credit == 0){
                    $available_transaction->credit = 1;
                    $available_transaction->save(); 
                
                    $driver->available_balance += $available_transaction->amount;
                    $driver->save(); 
                }
                 
                   
            }
            $users = User::where('wallet_balance', '>', 0)->whereHas('transaction')->orderBy('updated_at', 'desc')->get();
            $drivers = Provider::where('wallet_balance', '>', 0)->whereHas('transaction')->orderBy('updated_at', 'desc');

            $total_wallet_balance = $total_available_balance = $highest_wallet_balance = $highest_available_balance = $last_withdrawal_amount = $last_withdrawal_date = 0;

            $user_balance = User::where('wallet_balance', '>', 0)->whereHas('transaction')->sum('wallet_balance');
            $driver_balance = Provider::where('wallet_balance', '>', 0)->whereHas('transaction')->sum('wallet_balance');

            $total_wallet_balance = $driver_balance;
            $total_available_balance = Provider::where('available_balance', '>', 0)->whereHas('transaction')->sum('available_balance');
            $highest_wallet_balance = $drivers->max('wallet_balance');
            $highest_available_balance = $drivers->max('available_balance');
            $drivers = $drivers->get();
            $transaction = RaveTransaction::where('type', 'debit')->where('status', 1)->orderBy('updated_at', 'desc')->first();
            $last_withdrawal_date = $transaction->updated_at;
            $last_withdrawal_amount = $transaction->amount;
            $total_withdrawal = RaveTransaction::where('type', 'debit')->where('status', 1)->orderBy('updated_at', 'desc')->sum('amount');

            for ($i=0; $i < count($drivers) ; $i++) { 
                $drivers[$i]->last_transaction_date = '';
                $drivers[$i]->last_transaction_amount = '';
                $transaction = RaveTransaction::where('driver_id', $drivers[$i]->id)->where('type', 'debit')->where('status', 1)->orderBy('updated_at', 'desc')->first();
                if($transaction){
                    $drivers[$i]->last_transaction_date = $transaction->updated_at;
                    $drivers[$i]->last_transaction_amount = $transaction->amount;
                }
                

            }

            return view('admin.payment.wallet_balance',compact('users', 'drivers', 'total_wallet_balance','total_available_balance','highest_wallet_balance','highest_available_balance','last_withdrawal_amount','last_withdrawal_date','total_withdrawal'));

        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function push_drivers_online(){
        $Providers = Provider::where('availability', '0')
                            ->whereHas('request_activity', function($query)  {
                            $query->where('updated_at', '>=', Carbon::now()->subDays(15)); })
                            // ->where('updated_at', '>=', Carbon::now()->subDays(15))
                             ->where('location_updated', '>=', Carbon::now()->subDays(15))
                            ->where('available_on', '>=', Carbon::now()->subDays(15))
                            ->where('status', 'approved')
                            ->where('archive', '0')
                            ->orderBy('available_on', 'desc')
                            ->get();
                            $driver_count = count($Providers);
        foreach($Providers as $pro){
            $Provider = Provider::where('id',$pro->id)->first();

            $service_status = "active";
            $Driveractivity = new DriverActivity;
            $Driveractivity->is_active = 1;
            $Driveractivity->driver_id = $pro->id;
            $Driveractivity->start = Carbon::now();
            $Driveractivity->save();

            $Provider->available_on = Carbon::now();
            $Provider->availability = 1;
            $Provider->online_by = Auth::guard('admin')->user()->id;
            $Provider->save();
            
            (new SendPushNotification)->DriverOnline($Provider->id);

            $activeHours = DriverActivity::where('driver_id', $Provider->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');

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
                    $service->status = $service_status;
                    $service->save();

                }
            }

        }
            
        
        return back()->with('flash_success', $driver_count." Drivers availability status updated to online");
    }

    public function refresh_location(){

        try{

            $online_drivers = Provider::where('availability','1')->where('archive','0')->where('status', 'approved')->get();
            $driver_count = 0;

            if(!empty($online_drivers)){
                foreach ($online_drivers as $key => $online_driver) {

                    $update = Provider::find($online_driver->id);

                    $now = Carbon::now();

                    // $profile = ProviderProfile::where('provider_id',$online_driver->id)->first();

                    $last_update = $now->diffInMinutes($update->updated_at, true);

                    if($last_update > 5){
                        $driver_count = $driver_count + 1;
                        Log::info($driver_count);
                        (new SendPushNotification)->DriverInActivity($online_driver->id);

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

                    //         if($profile->notified != 4 || $profile->notified == '' || $profile->notified == 0 ){

                    //             (new SendPushNotification)->DriverInActivity($online_driver->id);
                            
                    //         }
                    //     }
                    // }
                }
            }
            return back()->with('flash_success', $driver_count." Drivers location refreshed");
        }catch(Exception $e){
            return back()->with('flash_error', "Server Error, Try again later!");
        }
    }

    public function agreement($id)
    {
       
        
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


    public function mmn(Request $request){

        $networks = User::where('first_name', '!=', '')->orderBy('first_name', 'asc')->paginate(100);
        $data = 0;
        
        //User Network
        for ($i=0; $i < count($networks) ; $i++) { 

            $l1 = $l2 = $l3 = $l4 = $l5 = array();
            $ul1 = $ul2 = $ul3 = $ul4 = $ul5 = "";
                        //Level 1
                        if($networks[$i]->user_referred != ''){
                            $l1 = User::where('referal',$networks[$i]->user_referred)->first();
                            $networks[$i]->l1 = $l1;
                            if($l1){
                                $ul1 = "u_".$l1->id;
                            }
                            
                        }else if($networks[$i]->driver_referred != ''){
                            $l1 = Provider::where('referal',$networks[$i]->driver_referred)->first();
                            $networks[$i]->l1 = $l1;
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
                            $network = MLMUserNetwork::where('user_id',$networks[$i]->id)->first();
                            if(!$network){
                                $network = new MLMUserNetwork;
                            }
                            $network->user_id = $networks[$i]->id;
                            $network->l1 = $ul1;
                            $network->l2 = $ul2;
                            $network->l3 = $ul3;
                            $network->l4 = $ul4;
                            $network->l5 = $ul5;
                            $network->save();
                        }

                $user_cashback = $driver_cashback = 0;

                $mlm_id = "u_".$networks[$i]->id;
                $user_network = MLMUserNetwork::where('l1',$mlm_id)->orwhere('l2',$mlm_id)->orwhere('l3',$mlm_id)->orwhere('l4',$mlm_id)->orwhere('l5',$mlm_id)->get();
                $driver_network = MLMDriverNetwork::where('l1',$mlm_id)->orwhere('l2',$mlm_id)->orwhere('l3',$mlm_id)->orwhere('l4',$mlm_id)->orwhere('l5',$mlm_id)->get();


                $user_trips = MLMUserCommission::where('l1_id',$mlm_id)->orwhere('l2_id',$mlm_id)->orwhere('l3_id',$mlm_id)->orwhere('l4_id',$mlm_id)->orwhere('l5_id',$mlm_id)->get();
                $driver_trips = MLMDriverCommission::where('l1_id',$mlm_id)->orwhere('l2_id',$mlm_id)->orwhere('l3_id',$mlm_id)->orwhere('l4_id',$mlm_id)->orwhere('l5_id',$mlm_id)->get();

                $amount = 0;

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

                $networks[$i]->user_network = count($user_network);
                $networks[$i]->driver_network = count($driver_network);
                $networks[$i]->total_network = $networks[$i]->user_network + $networks[$i]->driver_network;
                $networks[$i]->trips = count($user_trips) + count($driver_trips);
                $networks[$i]->cashback = $user_cashback + $driver_cashback;
        }
        if($request->has('filter')){
            $networks = Provider::where('first_name', '!=', '')->orderBy('first_name', 'asc')->paginate(100);
            $data = 1;
            //Driver Network
            for ($i=0; $i < count($networks) ; $i++) { 
                $user_cashback = $driver_cashback = 0;
                $mlm_id = "d_".$networks[$i]->id;
                $user_network = MLMUserNetwork::where('l1',$mlm_id)->orwhere('l2',$mlm_id)->orwhere('l3',$mlm_id)->orwhere('l4',$mlm_id)->orwhere('l5',$mlm_id)->get();
                $driver_network = MLMDriverNetwork::where('l1',$mlm_id)->orwhere('l2',$mlm_id)->orwhere('l3',$mlm_id)->orwhere('l4',$mlm_id)->orwhere('l5',$mlm_id)->get();
                
                //Trips and cashback from Network
                $user_trips = MLMUserCommission::where('l1_id',$mlm_id)->orwhere('l2_id',$mlm_id)->orwhere('l3_id',$mlm_id)->orwhere('l4_id',$mlm_id)->orwhere('l5_id',$mlm_id)->get();
                $driver_trips = MLMDriverCommission::where('l1_id',$mlm_id)->orwhere('l2_id',$mlm_id)->orwhere('l3_id',$mlm_id)->orwhere('l4_id',$mlm_id)->orwhere('l5_id',$mlm_id)->get();
                 $amount = 0;

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

                $networks[$i]->user_network = count($user_network);
                $networks[$i]->driver_network = count($driver_network);
                $networks[$i]->total_network = $networks[$i]->user_network + $networks[$i]->driver_network;
                $networks[$i]->trips = count($user_trips) + count($driver_trips);
                $networks[$i]->cashback = $user_cashback + $driver_cashback;


                $l1 = $l2 = $l3 = $l4 = $l5 = array();
                        $ul1 = $ul2 = $ul3 = $ul4 = $ul5 = "";

                        //Level 1
                        if($networks[$i]->user_referred != ''){
                            $l1 = User::where('referal',$networks[$i]->user_referred)->first();
                            $networks[$i]->l1 = $l1;
                            if($l1){
                                $ul1 = "u_".$l1->id;
                            }
                            
                        }else{
                            $l1 = Provider::where('referal',$networks[$i]->driver_referred)->first();
                            $networks[$i]->l1 = $l1;
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
                            $network = MLMDriverNetwork::where('driver_id',$networks[$i]->id)->first();
                            if(!$network){
                                $network = new MLMDriverNetwork;
                            }
                        
                            $network->driver_id = $networks[$i]->id;
                            $network->l1 = $ul1;
                            $network->l2 = $ul2;
                            $network->l3 = $ul3;
                            $network->l4 = $ul4;
                            $network->l5 = $ul5;
                            $network->save();
                        }
                        
            }
        }
        return view('admin.mlm.index', compact('networks','data'));
    }

    public function mmn_cashback(Request $request){


        $trips = MLMUserCommission::with('user')->with('request')->orderBy('created_at', 'desc')->get();
        $data = 0;
        
            //User Network

            for ($i=0; $i < count($trips) ; $i++) { 
                if($trips[$i]->l1_id !=''){
                    if (strpos($trips[$i]->l1_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l1_id, 2);
                        $trips[$i]->l1 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l1_id, 2);
                        $trips[$i]->l1 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l2_id !=''){
                    
                    if (strpos($trips[$i]->l2_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l2_id, 2);
                        $trips[$i]->l2 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l2_id, 2);
                        $trips[$i]->l2 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l3_id !=''){
                    if (strpos($trips[$i]->l3_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l3_id, 2);
                        $trips[$i]->l3 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l3_id, 2);
                        $trips[$i]->l3 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l4_id !=''){
                    if (strpos($trips[$i]->l4_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l4_id, 2);
                        $trips[$i]->l4 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l4_id, 2);
                        $trips[$i]->l4 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l5_id !=''){
                    if (strpos($trips[$i]->l5_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l5_id, 2);
                        $trips[$i]->l5 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l5_id, 2);
                        $trips[$i]->l5 = Provider::where('id', $id)->first();
                    }
                }
            }
        if($request->has('filter')){
            $trips = MLMDriverCommission::with('driver')->with('request')->orderBy('created_at', 'desc')->get();
            $data = 1;
        
            //User Network
            for ($i=0; $i < count($trips) ; $i++) { 
                if($trips[$i]->l1_id !=''){
                    if (strpos($trips[$i]->l1_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l1_id, 2);
                        $trips[$i]->l1 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l1_id, 2);
                        $trips[$i]->l1 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l2_id !=''){
                    
                    if (strpos($trips[$i]->l2_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l2_id, 2);
                        $trips[$i]->l2 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l2_id, 2);
                        $trips[$i]->l2 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l3_id !=''){
                    if (strpos($trips[$i]->l3_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l3_id, 2);
                        $trips[$i]->l3 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l3_id, 2);
                        $trips[$i]->l3 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l4_id !=''){
                    if (strpos($trips[$i]->l4_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l4_id, 2);
                        $trips[$i]->l4 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l4_id, 2);
                        $trips[$i]->l4 = Provider::where('id', $id)->first();
                    }
                }

                if($trips[$i]->l5_id !=''){
                    if (strpos($trips[$i]->l5_id, 'u_') === 0) {
                        $id = substr($trips[$i]->l5_id, 2);
                        $trips[$i]->l5 = User::where('id', $id)->first();
                    }else{
                        $id = substr($trips[$i]->l5_id, 2);
                        $trips[$i]->l5 = Provider::where('id', $id)->first();
                    }
                }
            }   
        }
        // dd($trips[0]->l1_com);
        return view('admin.mlm.cashback', compact('trips','data'));
    }


    public function expense_categories()
    {
        
        try {
            $exp_categories = ExpenseCategory::get();

            for ($i=0; $i < count($exp_categories) ; $i++) { 
                if($exp_categories[$i]->type ==1){
                   $exp_categories[$i]->type_name = 'Vehicle'; 
                }
                if($exp_categories[$i]->type ==2){
                   $exp_categories[$i]->type_name = 'Office'; 
                }
                if($exp_categories[$i]->type ==3){
                   $exp_categories[$i]->type_name = 'Employee'; 
                } 
            }
            
            return view('admin.expenses.expenses_category',compact('exp_categories'));
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }
    public function add_expense_category(Request $request)
    {
        
        try {
            $exp_category = new ExpenseCategory;
            $exp_category->type = $request->type;
            $exp_category->name = $request->name;
            $exp_category->save();

           return redirect()->route('admin.expenses.category.index')->with('flash_success', 'Expense Category added');

        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function edit_expense_category($id)
    {
        
        try {
            $exp_category = ExpenseCategory::where('id', $request->id)->first();

           return view('admin.expenses.edit_category', compact('exp_category'));

        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function update_expense_category(Request $request)
    {
        
        try {
            $exp_category = ExpenseCategory::where('id', $request->id)->first();
            $exp_category->type = $request->type;
            $exp_category->name = $request->name;
            $exp_category->save();

           return redirect()->route('admin.expenses.category.index')->with('flash_success', 'Expense Category updated');

        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function delete_expense_category($id)
    {
        
        try {
            $exp_category = ExpenseCategory::where('id', $request->id)->first();
            $exp_category->status = 1;            
            $exp_category->save();

           return redirect()->route('admin.expenses.category.index')->with('flash_success', 'Expense Category deleted');

        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }
    public function expenses(Request $request)
    {
        
        try {
            $banks = Bank::all();
            $categories = ExpenseCategory::where('status','!=', 1)->where('type', '!=', 1)->get();
            $vehicles = DriveNowVehicle::where('status','!=',6)->get();
            $users = Admin::where('role', '!=', 'admin')->get();

            if(request()->has('filter_date')){
                $filter = [];
                $role = array('admin','senior','accounts');
                if(in_array(Auth::guard('admin')->user()->role, $role)){
                    
                }else if(Auth::guard('admin')->user()->role == 'manager'){
                    $filter[] = ['amount','>',Setting::get('manager_approval_limit')];
                }else{
                    $filter[] = ['added_by', Auth::guard('admin')->user()->id];
                }
                $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                $expenses = OfficeExpense::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('added','approved','vehicle','expense','paid')->orderBy('created_at','desc')->where($filter)->get();
            
                $total_exp = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->sum('amount');
                $exp_requested = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->sum('amount');
                $exp_approved = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');
                $exp_declined = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->sum('amount');
                $exp_paid = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',3)->sum('amount');
                $exp_cat = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->selectRaw('count(*) as cate, sum(amount) as total, category')->with('expense')->groupby('category')
                                    ->orderBy('total', 'desc')
                                    ->first();
                $exp_person = OfficeExpense::where($filter)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->selectRaw('count(*) as per, sum(amount) as total, paid_to')->with('paid')->groupby('paid_to')
                                    ->orderBy('total', 'desc')
                                    ->first();
                $page = "Expense History from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));

            }else{
                $filter = $filter2 = [];
                $role = array('admin','senior','accounts');
                if(in_array(Auth::guard('admin')->user()->role, $role)){
                    
                }else if(Auth::guard('admin')->user()->role == 'manager'){
                    $filter[] = ['amount','<',Setting::get('manager_approval_limit')];
                    $filter2[] = ['added_by', Auth::guard('admin')->user()->id];
                }else{
                    $filter[] = ['added_by', Auth::guard('admin')->user()->id];
                }
                
                $expenses = OfficeExpense::with('added','approved','vehicle','expense','paid')->orderBy('created_at','desc')->where($filter)->orwhere($filter2)->get();
                // dd($expenses[5]);

                            
                $total_exp = OfficeExpense::where($filter)->sum('amount');
                $exp_requested = OfficeExpense::where($filter)->where('status',0)->sum('amount');
                $exp_approved = OfficeExpense::where($filter)->where('status',1)->sum('amount');
                $exp_declined = OfficeExpense::where($filter)->where('status',2)->sum('amount');
                $exp_paid = OfficeExpense::where($filter)->where('status',3)->sum('amount');
                $exp_cat = OfficeExpense::where($filter)->selectRaw('count(*) as cate, sum(amount) as total, category')->with('expense')->groupby('category')
                                    ->orderBy('total', 'desc')
                                    
                                    ->first();
                $exp_person = OfficeExpense::where($filter)->selectRaw('count(*) as per, sum(amount) as total, paid_to')->with('paid')->groupby('paid_to')
                                    ->orderBy('total', 'desc')
                                    
                                    ->first();
                $page = "Expense History";
            }

            return view('admin.expenses.expenses',compact('expenses','categories','users','vehicles','banks','total_exp','exp_requested','exp_approved','exp_paid','exp_cat','exp_person','exp_declined', 'page'));
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong!');
        }
    }

    public function create_expense()
    {
        
        try {
            $banks = Bank::all();
            
            $categories = ExpenseCategory::where('status','!=', 1)->where('type', '!=', 1)->get();
            $vehicles = DriveNowVehicle::where('status','!=',6)->get();
            $users = Admin::where('status','<>',1)->orWhereNull('status')->where('role', '!=', 'admin')->orderBy('name','asc')->get();
            // dd($users);
            return view('admin.expenses.create',compact('categories','users','vehicles','banks'));
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong!');
        }
    }

    public function edit_expense($id)
    {
        
        try {
            $banks = Bank::all();
            $expense = OfficeExpense::with('added','approved','vehicle','expense','paid')->where('id',$id)->first();
            $categories = ExpenseCategory::where('status','!=', 1)->where('type', '!=', 1)->get();
            $vehicles = DriveNowVehicle::where('status','!=',6)->get();
            $users = Admin::whereNull('status')->where('role', '!=', 'admin')->orderBy('name','asc')->get();
            return view('admin.expenses.edit',compact('expense','categories','users','vehicles','banks'));
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong!');
        }
    }

    public function add_expenses(Request $request)
    {
        
        try {
            $expense = new OfficeExpense;
            // String of all alphanumeric character
            $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
 
            $code = substr(str_shuffle($str_result),0, 4);
            $exp_id = "FE".$code.date('H');
            $expense->exp_id = $exp_id;
            $cat = explode('_',$request->category);
            $expense->category = $cat[1];
            $expense->car_id = $request->car_id;
            $expense->paid_to = $request->paid_to;
            $expense->amount = $request->amount;
            $expense->date = $request->date;
            $expense->description = $request->description;
            $expense->added_by = Auth::guard('admin')->user()->id;
            $expense->status = 0;
            if($request->has('acc_no')) 
                $expense->acc_no = $request->acc_no;

            if ($request->has('bank_name'))
                $expense->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $expense->bank_name_id = $request->bank_name_id;

            if ($request->has('bank_code'))
                $expense->bank_code = $request->bank_code;

            if($request->hasFile('receipt')){

                $name = "exp-".$expense->exp_id."-".str_replace(' ','_',Carbon::now());
                
                $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/drivenow_receipts'; 
                
                $contents = file_get_contents($request->receipt);

                $path = Storage::disk('s3')->put('drivenow_receipts/'.$name, $contents);
                $url = $baseurl.'/'.$name;
                       
            $expense->receipt = $url;

            }

            $expense->save();
                       
            return redirect()->route('admin.expenses.index')->with('flash_success', 'Expense Created');
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function update_expenses(Request $request)
    {
        
        try {
            $expense = OfficeExpense::where('id',$request->id)->first();
            $cat = explode('_',$request->category);
            $expense->category = $cat[1];
            $expense->car_id = $request->car_id;
            $expense->paid_to = $request->paid_to;
            $expense->amount = $request->amount;
            $expense->date = $request->date;
            $expense->description = $request->description;
            $expense->added_by = Auth::guard('admin')->user()->id;
            $expense->status = 0;
            if($request->has('acc_no')) 
                $expense->acc_no = $request->acc_no;

            if ($request->has('bank_name'))
                $expense->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $expense->bank_name_id = $request->bank_name_id;

            if ($request->has('bank_code'))
                $expense->bank_code = $request->bank_code;
            if($request->hasFile('receipt')){

                $name = "Exp-".$expense->exp_id."-".str_replace(' ','_',Carbon::now());
                $baseurl = 'https://Eganowuploads.s3.eu-west-2.amazonaws.com/drivenow_receipts'; 
                                $contents = file_get_contents($request->receipt);

                $path = Storage::disk('s3')->put('drivenow_receipts/'.$name, $contents);
                $url = $baseurl.'/'.$name;
                       
            $expense->receipt = $url;
            }
            
            $expense->save();


            return redirect()->route('admin.expenses.index')->with('flash_success', 'Expense Updated');
        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function approve_expense($id)
    {
        
        try {
            $exp_category = OfficeExpense::where('id', $id)->first();

            $exp_category->approved_by = Auth::guard('admin')->user()->id;
            $exp_category->status = 1;   
            $exp_category->save();

           return redirect()->route('admin.expenses.index')->with('flash_success', 'Expense Approved');

        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function decline_expense($id)
    {
        
        try {
            $exp_category = OfficeExpense::where('id', $id)->first();
            $exp_category->approved_by = Auth::guard('admin')->user()->id;
            $exp_category->status = 2;   
            $exp_category->save();

           return redirect()->route('admin.expenses.index')->with('flash_success', 'Expense Declined');

        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }
    public function pay_expense($id)
    {
        
        try {
            $exp_category = OfficeExpense::where('id', $id)->first();
            $exp_category->approved_by = Auth::guard('admin')->user()->id;
            $exp_category->status = 3;   
            $exp_category->save();

           return redirect()->route('admin.expenses.index')->with('flash_success', 'Expense Paid');

        } 
        catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something Went Wrong');
        }
    }

    public function drivenow_contracts(){
        $contracts = DriveNowContracts::where('status', '!=', 2)->get();
        return view('admin.drivenow_contract.index',compact('contracts'));

    }
    public function drivenow_contract_create(){

        return view('admin.drivenow_contract.create');
    }

    public function drivenow_contract_approve($id){
        $contract = DriveNowContracts::where('id', $id)->first();
        $contract->status = 1;
        $contract->save();
        return redirect()->route('admin.driveNow.contract.index')->with('flash_success', 'Contract approved successfully');
    }

    public function drivenow_contract_default($id){
            $contract = DriveNowContracts::where('id', $id)->first();
            $contract->is_default = 1;
            $contract->save();
            $make_def_cont = DriveNowContracts::where('id', '!=', $id)->update(['is_default' => 0]);
            return redirect()->route('admin.driveNow.contract.index')->with('flash_success', 'Contract set as Default');
    }

    public function drivenow_contract_disable($id){
        $contract = DriveNowContracts::where('id', $id)->first();
        $contract->status = 2;
        $contract->save();
        return redirect()->route('admin.driveNow.contract.index')->with('flash_success', 'Contract disabled successfully');
    }

    public function drivenow_contract_add(Request $request){
        $file = $request->contract;
        $file_name = $file->getClientOriginalName();
        $contract = DriveNowContracts::where('content', $file_name)->first();

        if(!$contract){
            $contract = new DriveNowContracts;
        }
        
        $contract->name = $request->name;
        
        //upload Contract file to Provider folder with original name
        $ext = $file->getClientOriginalExtension();
        $destination = resource_path()."/views/provider/";
        $file->move($destination,$file_name);

        $contract->content = $file_name;
        $contract->save();

        return redirect()->route('admin.driveNow.contract.index')->with('flash_success', 'New contract created successfully');
    }
    public function drivenow_contract_update(){
        return redirect()->route('admin.driveNow.contracts')->with('flash_success', 'Contract updated successfully');
    }


}
