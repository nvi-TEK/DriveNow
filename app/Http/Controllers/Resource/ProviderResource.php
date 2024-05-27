<?php

namespace App\Http\Controllers\Resource;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\SendPushNotification;
use DB;
use Exception;
use Setting;
use Auth;
use App\Provider;
use App\Fleet;
use App\Marketers;
use App\Document;
use App\ServiceType;
use App\ProviderProfile;
use App\ProviderService;
use App\ProviderDocument;
use App\UserRequestPayment;
use App\UserRequests;
use App\Helpers\Helper;
use GuzzleHttp\Client;
use App\DriverSubaccount;
use Log;
use App\DriverComments;
use Carbon\Carbon;
use App\Admin;
use App\User;
use App\DriverRequestReceived;
use App\RaveTransaction;
use App\OnlineCredit;
use App\DriverActivity;
use Storage;
use App\IndividualPush;
use App\Bank;
use App\DriverCars;
use App\DriverAccounts;
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

class ProviderResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $vehicles = DriveNowVehicle::whereNotIn('status',[5,6,0])->get();
            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved','device')
                        ->where('archive', '!=', 1)
                        ->orderBy('created_at', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
                

            if(request()->has('fleet')){
                $providers = $AllProviders->where('archive', '!=', '1')->where('fleet',$request->fleet)->paginate(300);
                $fleet_name = Fleet::find($request->fleet);
                for ($i=0; $i < count($providers); $i++) { 
                    if($providers[$i]->marketer != 0){
                        $marketer = Marketers::find($providers[$i]->marketer);
                        $providers[$i]->marketer_name = $marketer->first_name .' '.$marketer->last_name;
                    }
                    if($providers[$i]->fleet != 0){
                        $fleet = Fleet::find($request->fleet);
                        $providers[$i]->fleet_name = $fleet->name;
                    }
                    $providers[$i]->total_requests = UserRequests::where('provider_id',$providers[$i]->id)->where('status','COMPLETED')->count();
                
                }

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->where('fleet',$request->fleet)->count();
            $online_drivers = Provider::where('availability', 1)->where('archive', '!=', 1)->where('fleet',$request->fleet)->count();
            $total_drivers = Provider::where('fleet',$request->fleet)->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('availability', 1)->where('archive', '!=', 0)->where('fleet',$request->fleet)->count();
            $drivers = Provider::where('fleet',$request->fleet)->where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();

                
                $page = 'Drivers under Fleet owner '. $fleet_name->name;
            }
            if(request()->has('marketer')){
                $providers = $AllProviders->where('marketer',$request->marketer)->paginate(300);                
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
            else if(request()->has('service')){
                $providers = ProviderService::where('service_type_id',$request->service)->get();
                foreach ($providers as $index => $provider) {
                $providers[$index] =  Provider::where('id', $provider->provider_id)
                                            ->with('service','accepted','cancelled','fleetowner')
                                            ->orderBy('id', 'DESC')->first();
                    $providers[$index]->total_requests = UserRequests::where('provider_id',$provider->provider_id)->where('status','COMPLETED')->count();
                }
                $service = ServiceType::find($request->service);
                    if($service->is_delivery == 1){

                        $flow = 'Delivery Flow';
                    }
                    else{
                        $flow = 'Taxi Flow';
                    } 
               $page = 'Drivers under Service '. $service->name .' ( '.$flow.' )';
            }else if(request()->has('filter')){

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

                $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
                for ($i=0; $i < count($providers) ; $i++) { 
                    $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                    if($activeHour > 0){ 

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

                return view('admin.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','vehicles','suppliers'));

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
            $suppliers = DriveNowVehicleSupplier::orderBy('created_at' , 'desc')->where('status', '!=',1)->get();
            
            $document = Document::all()->count();
            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('admin.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','vehicles','kyc','suppliers'));
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

    public function online_drivers(Request $request)
    {
        try {

            // $Providers = Provider::where('availability', '0')
            //                 ->whereHas('request_activity', function($query)  {
            //                 $query->where('updated_at', '>=', Carbon::now()->subDays(30)); })
            //                 // ->where('updated_at', '>=', Carbon::now()->subDays(15))
            //                 ->where('location_updated', '>=', Carbon::now()->subDays(15))
            //                 ->where('available_on', '>=', Carbon::now()->subDays(15))
            //                 ->where('status', 'approved')
            //                 ->where('archive', '0')
            //                 ->orderBy('available_on', 'desc')
            //                 ->get();
            // $active_driver_count = count($Providers);
            $active_driver_count = 0;

            $offline = $online = $riding = array();
            $location_updated = Setting::get('location_update_interval');
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('availability', 1)
                        ->orderBy('updated_at', 'DESC');
                        // dd($AllProviders);

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

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();

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
            
            return view('admin.providers.online', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','active_driver_count'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function offline_drivers(Request $request)
    {
        try {
            // $Providers = Provider::where('availability', '0')
            //                  ->whereHas('request_activity', function($query)  {
            //                 $query->where('updated_at', '>=', Carbon::now()->subDays(15)); })
            //                 // ->where('updated_at', '>=', Carbon::now()->subDays(15))
            //                 ->where('location_updated', '>=', Carbon::now()->subDays(15))
            //                 ->where('available_on', '>=', Carbon::now()->subDays(15))
            //                 ->where('status', 'approved')
            //                 ->where('archive', '0')
            //                 ->orderBy('available_on', 'desc')
            //                 ->get();
            // $active_driver_count = count($Providers);
            $active_driver_count = 0;

            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('availability', 0)
                        ->orderBy('updated_at', 'DESC');

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

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();

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
            
            return view('admin.providers.offline', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','active_driver_count'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function search_drivers(Request $request)
    {
        try {
            $vehicles = DriveNowVehicle::whereNotIn('status',[5,6,0])->get();

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
                $providers = $AllProviders->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%')->paginate(300);
            }

            $document = Document::all()->count();

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
             $suppliers = DriveNowVehicleSupplier::orderBy('created_at' , 'desc')->where('status', '!=',1)->get();

            return view('admin.providers.index', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','vehicles','suppliers'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function recent_uploaded(){
        try {
            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)
                        ->orderBy('upload_notify', 'DESC');

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();

            return view('admin.providers.document_uploaded', compact('recent_documents','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers'));
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }        

    }

    // public function ambassadors(Request $request)
    // {
    //     try {

    //         $offline = $online = $riding = array();
    //         $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
    //                     ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)
    //                     ->orderBy('updated_at', 'DESC');
    //         $ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
    //                     ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)
    //                     ->orderBy('updated_at', 'DESC')->count();
    //         $online_ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
    //                     ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)->where('availability',1)
    //                     ->orderBy('updated_at', 'DESC')->count();
    //         $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
    //         $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
    //         $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
    //         $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
    //         $drivers = Provider::where('archive', '!=', 1)->pluck('id');
    //         $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
            
    //         if($request->has('search')){
    //             $page = 'Search result for "'.$request->search .'"';
    //             $providers = $AllProviders->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%')->get();
    //         }else{
    //             if($request->has('filter')){
    //                 if($request->filter == 1){
    //                     $page = 'List of Ambassadors to pay';
    //                     $providers = $AllProviders->where('bonus',0)->paginate(300);
    //                 }else if($request->filter == 2){
    //                     $page = 'List of Paid Ambassadors';
    //                     $providers = $AllProviders->where('bonus',1)->paginate(300);
    //                 }
    //             }else{
    //                 $page = 'List of Ambassadors';
    //                 $providers = $AllProviders->paginate(300);
    //             }   
                
    //         }

    //         for ($i=0; $i < count($providers); $i++) {
    //             $providers[$i]->user_referral = User::where('driver_referred', $providers[$i]->referal)->count();
    //             $providers[$i]->driver_referral = Provider::where('driver_referred', $providers[$i]->referal)->count();
    //             $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->count();

    //             $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
    //                 if($activeHours[0] > 0){ 

    //                     if($activeHours[0] >= 60){
    //                         $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
    //                     }else{
    //                         $activeHour = number_format($activeHours[0], 2) . " mins";
    //                     }

    //                     $providers[$i]->activeHoursFormat = $activeHour;
    //                     $providers[$i]->activeHours = $activeHours[0] / 60;
    //                 }else{
    //                     $providers[$i]->activeHoursFormat = "N / A";
    //                     $providers[$i]->activeHours = 0;
    //                 }

    //         }

    //         $document = Document::all()->count();

    //         $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
    //         return view('admin.providers.ambassadors', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','ambassadors', 'online_ambassadors'));
                
    //         } catch (Exception $e) {
    //         Log::info($e);
    //         return back()->with('flash_error', 'Something went wrong');
    //     }  
    // }

    public function ambassadors(Request $request)
    {
        try {

            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)
                        ->orderBy('updated_at', 'DESC');

            $ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)
                        ->orderBy('updated_at', 'DESC')->count();

            $online_ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)->where('availability',1)
                        ->orderBy('updated_at', 'DESC')->count();

            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();

            //User Referral Analytics
            $total_user_referred = User::where('driver_referred', '!=', '')->count();
            $total_user_referred_booked = User::where('driver_referred', '!=', '')->has('trips')->count();
            $total_user_referred_completed = User::where('driver_referred', '!=', '')->whereHas('trips', function($query) {
                            $query->where('status', 'COMPLETED');})->count();

            //
            $total_driver_referred = Provider::where('driver_referred', '!=', '')->count();
            $total_driver_referred_received = Provider::where('driver_referred', '!=', '')->has('trips')->count();
            $total_driver_referred_completed = Provider::where('driver_referred', '!=', '')->whereHas('trips', function($query) {
                            $query->where('status', 'COMPLETED');
                        })->count();
            
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $search = $request->search;
                $providers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)
                        ->where(function($q) use ($search) { $q->where('first_name','like', '%'.$search.'%')->orwhere('email','like', '%'.$search.'%')->orwhere('mobile','like', '%'.$search.'%');
                                    })->get();
            }else{
                if($request->has('filter')){
                    if($request->filter == 1){
                        $page = 'List of Promo Drivers to pay';
                        $providers = $AllProviders->where('bonus',0)->paginate(300);
                    }else if($request->filter == 2){
                        $page = 'List of Paid Promo Drivers';
                        $providers = $AllProviders->where('bonus',1)->paginate(300);
                    }
                }else{
                    $page = 'List of Promo Drivers';
                    $providers = $AllProviders->paginate(300);
                }   
                
            }
            
            
                if($request->has('filter_date')){

                    $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));

                    $page = "Ambassadors from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
                    $search ="";
                    $data = "4";
                    for ($i=0; $i < count($providers); $i++) {
                        $user_requests = $last_user_requests = $driver_requests = $user_booked  = $driver_rec = 0;
                            $providers[$i]->user_requests = 0;
                            $providers[$i]->driver_requests = 0;
                            $providers[$i]->last_user_requests = 0;
                            $providers[$i]->user_booked = 0;
                            $providers[$i]->driver_req_rec = 0;
                        $user_referred = User::where('driver_referred', $providers[$i]->referal)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                            
                            //Users Referred by Driver with Completed Requests
                            // foreach ($user_referred as $user) {
                            //     $requests = UserRequests::where('user_id', $user->id)->where('status', 'COMPLETED')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            //     if($requests > 0){
                            //        $user_requests =  $user_requests + 1;
                            //     }

                            //     $booked = UserRequests::where('user_id', $user->id)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            //     if($booked > 0){
                            //        $user_booked =  $user_booked + 1;
                            //     }
                                 
                            // }
                            // $providers[$i]->user_requests =  $user_requests;
                            // $providers[$i]->user_booked =  $user_booked;

                            $providers[$i]->user_referral = count($user_referred);
                            
                            $driver_referred = Provider::where('driver_referred', $providers[$i]->referal)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                            
                            // foreach ($driver_referred as $driver) {
                            //     $requests = UserRequests::where('provider_id', $driver->id)->where('status', 'COMPLETED')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            //     if($requests > 0){
                            //        $driver_requests =  $driver_requests + 1;
                            //     } 
                            //     $driver_booked = UserRequests::where('provider_id', $driver->id)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            //     if($driver_booked > 0){
                            //        $driver_rec =  $driver_rec + 1;
                            //     }
                            // }
                            
                            // $providers[$i]->driver_requests = $driver_requests;
                            // $providers[$i]->driver_req_rec = $driver_rec;
                            $providers[$i]->driver_referral = count($driver_referred);

                           
                            $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                           
                            //Active Working Hours for the month by Driver
                            $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->whereBetween('created_at',[date($dates[0]), date($dates[1])])->pluck('activeHours');
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

                            //Calculate Driver Earning for the month 
                            $requests = UserRequests::where('provider_id', $providers[$i]->id)->with('payment')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                            $trip_earning = 0;
                            if(count($requests) > 0) {
                                for($j=0; $j < count($requests); $j++) {
                                    if($requests[$j]->payment){
                                        $trip_earning += ($requests[$j]['payment']['driver_earning']);
                                    }
                                }
                            }
                            $providers[$i]->earnings = $trip_earning;

                            //Checking no Incoming Request for last 12 hours
                                $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();

                                //No. of Request completed in last 24 hours
                                $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                                if($request > 0){
                                    $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                                }else{
                                    $providers[$i]->completed_ratio = 0;
                                }
                                
                                $providers[$i]->received = $request;
                                $providers[$i]->completed = $completed_request;
                            }
                }else{  
                        
                        for ($i=0; $i < count($providers); $i++) {
                            $user_requests = $last_user_requests = $driver_requests = $user_booked  = $driver_rec = 0;
                            $providers[$i]->user_requests = 0;
                            $providers[$i]->driver_requests = 0;
                            $providers[$i]->last_user_requests = 0;
                            $providers[$i]->user_booked = 0;
                            $providers[$i]->driver_req_rec = 0;
                            $user_referred = User::where('driver_referred', $providers[$i]->referal)->get();
                            
                            //Users Referred by Driver with Completed Requests
                            // foreach ($user_referred as $user) {
                            //     $requests = UserRequests::where('user_id', $user->id)->where('status', 'COMPLETED')->count();
                            //     if($requests > 0){
                            //         $user_requests =  $user_requests + 1;
                            //     } 
                            //     $booked = UserRequests::where('user_id', $user->id)->count();
                            //     if($booked > 0){
                            //        $user_booked =  $user_booked + 1;
                            //     }

                            // }

                            
                            // $providers[$i]->user_requests =  $user_requests;
                            // $providers[$i]->user_booked =  $user_booked;
                            $providers[$i]->user_referral = count($user_referred);
                            
                            $driver_referred = Provider::where('driver_referred', $providers[$i]->referal)->get();

                            // foreach ($driver_referred as $driver) {
                            //     $requests = UserRequests::where('provider_id', $driver->id)->where('status', 'COMPLETED')->count();
                            //     if($requests > 0){
                            //         $driver_requests  =  $driver_requests + 1;
                            //     } 
                            //     $driver_booked = UserRequests::where('provider_id', $driver->id)->count();
                            //     if($driver_booked > 0){
                            //        $driver_rec =  $driver_rec + 1;
                            //     }
                            // }
                            
                            // $providers[$i]->driver_requests = $driver_requests;
                            // $providers[$i]->driver_req_rec = $driver_rec;
                            $providers[$i]->driver_referral = count($driver_referred);

                           
                            $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->count();

                           
                            //Active Working Hours for the month by Driver
                            $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
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

                            //Calculate Driver Earning for the month 
                            $requests = UserRequests::where('provider_id', $providers[$i]->id)->with('payment')->get();
                            $trip_earning = 0;
                            if(count($requests) > 0) {
                                for($j=0; $j < count($requests); $j++) {
                                    if($requests[$j]->payment){
                                        $trip_earning += ($requests[$j]['payment']['driver_earning']);
                                    }
                                }
                            }
                            $providers[$i]->earnings = $trip_earning;

                            //Checking no Incoming Request for last 12 hours
                                $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->count();

                                //No. of Request completed in last 24 hours
                                $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->count();
                                if($request > 0){
                                    $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                                }else{
                                    $providers[$i]->completed_ratio = 0;
                                }
                                
                                $providers[$i]->received = $request;
                                $providers[$i]->completed = $completed_request;
                        }

                    }
                    $top_completed_ratio = $top_referrer =  array();
                    $amb_user_referrals = $amb_driver_referrals = $amb_user_ref_booked = $amb_user_ref_completed = $amb_driver_ref_rec = $amb_driver_ref_completed = $ratio = $ref = 0;

                    for ($j=0; $j < count($providers); $j++) { 

                        if($providers[$j]->user_referral > 0){
                             $amb_user_referrals = $amb_user_referrals + $providers[$j]->user_referral;
                        }

                        // if($providers[$j]->user_requests > 0){
                        //      $amb_user_ref_completed = $amb_user_ref_completed + $providers[$j]->user_requests;
                        // }

                        // if($providers[$j]->user_booked > 0){
                        //      $amb_user_ref_booked = $amb_user_ref_booked + $providers[$j]->user_booked;
                        // }

                        if($providers[$j]->driver_referral > 0){
                             $amb_driver_referrals = $amb_driver_referrals +$providers[$j]->driver_referral;
                        }
                        

                        // if($providers[$j]->driver_req_rec > 0){
                        //     $amb_driver_ref_rec = $amb_driver_ref_rec + $providers[$j]->driver_req_rec;
                        // }
                        // if($providers[$j]->driver_requests > 0){
                        //     $amb_driver_ref_completed = $amb_driver_ref_completed + $providers[$j]->driver_requests;
                        // }

                        if($providers[$j]->completed_ratio > $ratio){
                            $ratio = $providers[$j]->completed_ratio;
                            $top_completed_ratio = $providers[$j];
                        }
                        $total_referrals = $providers[$j]->user_referral + $providers[$j]->driver_referral;
                        if($total_referrals > $ref){
                            $ref = $total_referrals;
                            $top_referrer = $providers[$j];
                        }
                        
                    }

            $document = Document::all()->count();

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('admin.providers.ambassadors', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','ambassadors', 'online_ambassadors', 'user_requests', 'total_user_referred','total_user_referred_booked','total_user_referred_completed','total_driver_referred','total_driver_referred_completed','total_driver_referred_received','amb_user_referrals', 'amb_driver_referrals', 'amb_user_ref_booked', 'amb_user_ref_completed', 'amb_driver_ref_rec', 'amb_driver_ref_completed','top_completed_ratio','top_referrer'));
                
            } catch (Exception $e) {
            return back()->with('flash_error', 'Something went wrong');
        }  
    }


    public function pay_ambassadors(){
        $drivers = DB::table('providers')->where('archive','0')
                        ->where('ambassador',1)
                        ->where('credits', '>', 0)
                        ->get();
        if(!empty($drivers)){
            foreach ($drivers as $key => $driver) {
                $update = Provider::find($driver->id);
                $update->wallet_balance += $update->credits;
                $update->credits = 0;
                $update->save();

                $code = rand(1000, 9999);
                $name = substr($update->first_name, 0, 2);
                $reference = "BWT".$code.$name;

                $rave_transactions = new RaveTransaction;
                $rave_transactions->driver_id = $update->id;
                $rave_transactions->reference_id = $reference;
                $rave_transactions->narration = $update->wallet_balance." added to your wallet by Eganow";
                $rave_transactions->amount = $update->wallet_balance;
                $rave_transactions->status = 1;
                $rave_transactions->type = 'credit';
                $rave_transactions->save();
            }
        }
        return back()->with('flash_success', 'Add the bonus to Ambassadors Wallet');
    }

    public function credit_ambassadors(Request $request, $id){
        if($request->has('filter')){
            $online_bonus = Setting::get('ambassadors_online_bonus', '0');
            $driver_referral_bonus = Setting::get('ambassadors_driver_referral_bonus', '0');
            $user_referral_bonus = Setting::get('ambassadors_user_referral_bonus', '0');
            $reward_hours_limit = Setting::get('reward_hours_limit', '0');
            $driver = Provider::find($id);
                if($request->filter == 1){
                    $OnlineCredit = OnlineCredit::where('driver_id', $driver->id)->where('status', 0)->get();
                    
                    if(count($OnlineCredit) >= $reward_hours_limit){
                        $driver->online_paid = $OnlineCredit->count();
                        $driver->credits += $online_bonus;
                        $driver->save();

                        foreach ($OnlineCredit as $credit) {
                            $OnlineCredits = OnlineCredit::findOrFail($credit->id);
                            $OnlineCredits->status = 1;
                            $OnlineCredits->save();
                        } 
                    }else{
                        return back()->with('flash_error', 'Driver Should have atleast 20 points to receive Bonus');
                    }
                    

                    
                    
                }else if($request->filter == 2){
                    $driver->driver_referral_credits += Provider::where('driver_referred', $driver->referal)->count() - $driver->driver_referral_credits;
                    $driver->credits += ($driver->driver_referral_credits * $driver_referral_bonus);
                    $driver->save();
                }else if($request->filter == 3){
                    $driver->user_referral_credits += User::where('driver_referred', $driver->referal)->count() - $driver->user_referral_credits;
                    $driver->credits += ($driver->user_referral_credits * $user_referral_bonus);
                    $driver->save();
                }

            return back()->with('flash_success', 'Add the bonus to Ambassadors Credits');
        }
    }

    public function credit_all_ambassadors(Request $request){
        if($request->has('filter')){
            $online_bonus = Setting::get('ambassadors_online_bonus', '0');
            $driver_referral_bonus = Setting::get('ambassadors_driver_referral_bonus', '0');
            $user_referral_bonus = Setting::get('ambassadors_user_referral_bonus', '0');
            $reward_hours_limit = Setting::get('reward_hours_limit', '0');
            $ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('ambassador', 1)
                        ->orderBy('upload_notify', 'DESC')->get();
            if($ambassadors){
                if($request->filter == 1){
                    foreach ($ambassadors as $driver) {
                        $OnlineCredit = OnlineCredit::where('driver_id', $driver->id)->where('status', 0)->get();
                         if(count($OnlineCredit) >= $reward_hours_limit){
                            $driver->online_paid = $OnlineCredit->count();
                            $driver->credits += $online_bonus;
                            $driver->save();

                            foreach ($OnlineCredit as $credit) {
                                $OnlineCredits = OnlineCredit::findOrFail($credit->id);
                                $OnlineCredits->status = 1;
                                $OnlineCredits->save();
                            } 
                        }
                       
                    }
                }else if($request->filter == 2){
                    foreach ($ambassadors as $driver) {
                        $driver->driver_referral_credits += Provider::where('driver_referred', $driver->referal)->count() - $driver->driver_referral_credits;
                        $driver->credits += ($driver->driver_referral_credits * $driver_referral_bonus);
                        $driver->save();
                    }
                }else if($request->filter == 3){
                    foreach ($ambassadors as $driver) {
                        $driver->user_referral_credits += User::where('driver_referred', $driver->referal)->count() - $driver->user_referral_credits;
                        $driver->credits += ($driver->user_referral_credits * $user_referral_bonus);
                        $driver->save();
                    }
                }

                return back()->with('flash_success', 'Add the bonus to Ambassadors Credits');
            }else{
                return back()->with('flash_success', 'No Elligible Ambassadors to credit');
            }
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
            // $banks = [];            
            $fleet = Fleet::all();
            
            // $client = new Client(['http_errors' => false]);
            // $url = 'https://api.ravepay.co/v2/banks/NG?public_key='.env('RAVE_PUBLIC_KEY');
            // $res = $client->get($url);
            // $banks = json_decode($res->getBody(), true);
            $banks = Bank::all();                        
            return view('admin.providers.create', compact('fleet','banks'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }        
    }

    public function add_ambassador($id)
    {
        try {
            $Provider = Provider::findOrFail($id);
            if($Provider) {
                $Provider->update(['ambassador' => 1]);
                return back()->with('flash_success', "Driver Added to Ambassadors");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver has not been assigned as Ambassador!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function remove_ambassador($id)
    {
        try {
            $Provider = Provider::findOrFail($id);
            if($Provider) {
                $Provider->update(['ambassador' => 0]);
                return back()->with('flash_success', "Driver Removed from Ambassadors");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver can not be removed from Ambassador!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
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
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@drivetry.com');
        }

        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|unique:providers,email|email|max:255',
            'mobile' => 'between:6,13',
            'avatar' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password' => 'required|min:6|confirmed',
            
        ]);

        try{
                if($request->mobile[0] == "0"){
                    $request->mobile = ltrim($request->mobile, 0);
                }

                $provider = $request->all();
                $provider['password'] = bcrypt($request->password);
                if ($request->has('fleet')){
                    $provider['fleet'] = $request->fleet;
                }
                
                if ($request->hasFile('avatar')) {
                    $name = $request->first_name."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual asset url';                    
                    $contents = file_get_contents($request->avatar);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $provider['avatar'] = $s3_url;
                }
                if($request->has('wallet_balance')) {
                    $provider['wallet_balance'] = $request->wallet_balance;
                }
                else{
                    $provider['wallet_balance'] = 0.00;
                }

                $provider = Provider::create($provider);

                // if($request->acc_no != '' && $request->bank_code !='')
                // {
                //     $client = new Client(['http_errors' => false]);
                //     $url ="https://api.ravepay.co/v2/gpx/subaccounts/create";
                //     $headers = [
                //         'Content-Type' => 'application/json',
                //     ];
                //     $body = [
                //                 "account_bank"              => $request->bank_code,
                //                 "account_number"            => $request->acc_no, 
                //                 "business_name"             => $request->first_name,
                //                 "business_email"            => $request->email,
                //                 "business_contact"          => $request->first_name,
                //                 "business_contact_mobile"   =>  $request->mobile,
                //                 "business_mobile"           =>  $request->mobile,
                //                 "country"                   =>  $request->bankcountry,
                //                 "meta"                      =>  ["metaname" => "MarketplaceID", "metavalue" => "ggs-920900"],
                //                 "seckey"                    =>  env("RAVE_SECRET_KEY")
                //             ];            
                //     $res = $client->post($url, [
                //         'headers' => $headers,
                //         'body' => json_encode($body),
                //     ]);
                //     $subaccount = json_decode($res->getBody(),true);
                //     \Log::info($subaccount);
                //     if($subaccount['status'] == 'success')
                //     {
                //         $split                  = new DriverSubaccount;
                //         $split->driver_id       = $Provider->id;
                //         $split->split_id        = $subaccount['data']['id'];
                //         $split->account_number  = $subaccount['data']['account_number'];
                //         $split->bank_code       = $subaccount['data']['account_bank'];
                //         $split->business_name   = $subaccount['data']['business_name'];
                //         $split->fullname        = $subaccount['data']['fullname'];
                //         $split->date_created    = $subaccount['data']['date_created'];
                //         $split->account_id      = $subaccount['data']['account_id'];
                //         $split->split_ratio     = $subaccount['data']['split_ratio'];
                //         $split->split_type      = $subaccount['data']['split_type'];
                //         $split->split_value     = $subaccount['data']['split_value'];
                //         $split->subaccount_id   = $subaccount['data']['subaccount_id'];
                //         $split->bank_name       = $subaccount['data']['bank_name'];
                //         $split->country         = $subaccount['data']['country'];
                //         $split->save();
                //     }
                //     else{
                //         $client = new Client(['http_errors' => false]);
                //         $url ="https://api.ravepay.co/v2/gpx/subaccounts/delete";
                //         $headers = [
                //             'Content-Type' => 'application/json',
                //         ];
                //         $body = ['id' => $subaccount['data']['id'], 'seckey' => env("RAVE_SECRET_KEY")];
                //         $res = $client->post($url, [
                //             'headers' => $headers,
                //             'body' => json_encode($body),
                //         ]);
                //         $delete = json_decode($res->getBody(),true);
                //         \Log::info($subaccount['message']);
                //         return back()->with('flash_error', $subaccount['message']);
                //     }     
                // }

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

                    $code = rand(1000, 9999);
                    $name = substr($provider->first_name, 0, 2);
                    $reference = "RWT".$code.$name;

                    $rave_transactions = new RaveTransaction;
                    $rave_transactions->driver_id = $provider->id;
                    $rave_transactions->reference_id = $reference;
                    $rave_transactions->narration = $request->wallet_balance." added to your wallet by Eganow";
                    $rave_transactions->amount = $request->wallet_balance;
                    $rave_transactions->status = 1;
                    $rave_transactions->type = "credit";
                    $rave_transactions->credit = 0;
                    $rave_transactions->save();
            }
                
                $Provider->save();

                

                return back()->with('flash_success','Provider Details Saved Successfully');
                   
        } 

        catch (Exception $e) {
            
            return back()->with('flash_error', 'Provider Not Found');
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

            $kyc = DriveNowDriverKYC::where('driver_id', $id)->with('driver','official')->first();

            //Adding Cars from driver profile to Driver Cars
            $driver_car = DriverCars::where('driver_id', $id)->get();
            if(count($driver_car) ==0){
                $savedCar = ProviderProfile::where('provider_id',$id)->where('car_registration' , '!=', '')->first();
                $insurance_file = ProviderDocument::where('provider_id',$id)->where('document_id', 6)->first();
                $road_worthy_file = ProviderDocument::where('provider_id',$id)->where('document_id', 3)->first();

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
                    $DriverCar->driver_id = $id;

                    if($insurance_file){
                        $DriverCar->insurance_file = $insurance_file->url;
                    }
                    if($road_worthy_file){
                        $DriverCar->road_worthy_file = $road_worthy_file->url;
                    }
                    $DriverCar->status = 1;
                    $DriverCar->is_active = 1;
                    $DriverCar->save();
                    $savedCar->car_saved = 1;
                    $savedCar->save();
                }
            }
            

            $driver_car = DriverCars::where('driver_id', $id)->where('is_active',1)->first();

            $DriverAccount = DriverAccounts::where('driver_id', $id)->get();
            if(count($DriverAccount) == 0){
                $savedAccount = ProviderProfile::where('provider_id',$id)->where('acc_no', '!=', '')->first();

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

            
            $DriverAccount = DriverAccounts::where('driver_id', $id)->where('is_active',1)->first();


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
            $provider = Provider::where('id',$id)->first();
            $request = DriverRequestReceived::where('provider_id', $id)->count();

                    //No. of Request completed in last 24 hours
                    
                    if($request > 0){
                        $provider->completed_ratio = round((( $completed_request / $request ) * 100));
                    }else{
                        $provider->completed_ratio = 0;
                    }
                    
                    $provider->received = $request;
                    $provider->completed = $completed_request;

            $missed_rides = DriverRequestReceived::where('provider_id', $provider->id)->where('status',0)->count();
            $rejected_rides = DriverRequestReceived::where('provider_id', $provider->id)->where('status',2)->count();
            $user_referral = User::where('driver_referred', $provider->referal)->count();
            $driver_referral = Provider::where('driver_referred', $provider->referal)->count();
            $document = Document::all()->count();
            $driverComments = DriverComments::where('driver_id', $id)->with('provider','moderator')->orderBy('created_at', 'desc')->get();
            for($i = 0; $i < count($driverComments); $i++){
                $driverComments[$i]->posts = DriverComments::where('marketer_id',$driverComments[$i]->moderator->id)->count();
            }
            $moderator_posts = DriverComments::where('marketer_id', Auth::guard('admin')->user()->id)->count();

            $driver = Provider::find($id);
            
            $user = Provider::find($provider->id);
            $credit_pending_transactions = RaveTransaction::where('driver_id', $provider->id)->where('status', 2)->where('type', 'credit')->where('credit', 0)->orderBy('created_at', 'desc')->get();
            if($credit_pending_transactions){
                foreach ($credit_pending_transactions as $credit_pending_transaction) {
                $payToken = $credit_pending_transaction->rave_ref_id;

                $client1 = new \GuzzleHttp\Client();
                $headers = ['Content-Type' => 'application/json'];

                $status_url = "https://app.slydepay.com/api/merchant/invoice/checkstatus";
                $status = $client1->post($status_url, [ 
                    'headers' => $headers,
                    'json' => ["emailOrMobileNumber"=>"replace with payments email",
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
            }
            
        $user = Provider::find($provider->id);

        $transactions = RaveTransaction::where('driver_id', $provider->id)->orderBy('created_at', 'desc')->get();


        $available_balance_duration = Setting::get('available_balance_time', '24');
        $credit = $debit = 0;
        $driver = Provider::find($provider->id);

        //Transactions for available balance calculation
        $available_transactions = RaveTransaction::where('driver_id', $driver->id)->where('status', 1)->where('type', 'credit')->where('credit', 0)->where('created_at', '<=', Carbon::now()->subHours($available_balance_duration))->orderBy('created_at', 'desc')->get();
         foreach ($available_transactions as $available_transaction) {
                $available_transaction->last_availbale_balance = $driver->available_balance;
                if($available_transaction->type == 'credit' && $available_transaction->credit == 0){
                    $driver->available_balance += $available_transaction->amount;
                    $driver->save();  
                }
                $available_transaction->credit = 1;
                $available_transaction->save();      
        }


        $transactions = RaveTransaction::where('driver_id', $provider->id)->orderBy('created_at', 'desc')->paginate(300);
        $requests = UserRequests::where('provider_id', $id)->with('payment')->orderBy('created_at', 'desc')->paginate(300);

            $custom_pushes = IndividualPush::where('driver_id', $provider->id)->with('moderator')->orderBy('created_at', 'desc')->get();

            $activeHours = DriverActivity::where('driver_id', $provider->id)->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                    if($activeHours[0] > 0){ 

                        if($activeHours[0] >= 60){
                            $activeHour = number_format(($activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $activeHour = number_format($activeHours[0], 2) . " mins";
                        }

                        $provider->activeHoursFormat = $activeHour;
                        $provider->activeHours = $activeHours[0] / 60;
                    }else{
                        $provider->activeHoursFormat = "N / A";
                        $provider->activeHours = 0;
                    }

            $user_referred = User::where('driver_referred', $provider->referal)->get();
            $user_requests = $provider->user_requests = 0 ;
            foreach ($user_referred as $user) {
                    $requestss = UserRequests::where('user_id', $user->id)->where('status', 'COMPLETED')->count();
                    if($requestss > 0){
                       $provider->user_requests =  $user_requests + 1;
                    }
                     
                }

            $driver_referred = Provider::where('driver_referred', $provider->referal)->get();
            $driver_requests = $provider->driver_requests = 0 ;
            foreach ($driver_referred as $user) {
                    $requestsss = UserRequests::where('provider_id', $user->id)->where('status', 'COMPLETED')->count();
                    if($requestsss > 0){
                       $provider->driver_requests =  $driver_requests + 1;
                    }
                     
                }

            $missed_requests = DriverRequestReceived::where('provider_id',$id)->where('status',0)->whereHas('request')->with('request')->orderBy('updated_at','desc')->get();
            return view('admin.providers.provider-details', compact('provider', 'earnings', 'document', 'requests', 'total_request', 'completed_request', 'cancelled_request','driverComments','moderator_posts','user_referral', 'driver_referral', 'missed_rides','rejected_rides','custom_pushes', 'transactions','missed_requests','driver_car', 'DriverAccount','kyc'));
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
            $fleet = Fleet::all();
                                  
            return view('admin.providers.edit',compact('provider', 'fleet'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function bank($id)
    {
        try {
            $banks = Bank::all();  

            $account = DriverAccounts::where('id', $id)->first();
            $provider = Provider::where('id', $account->driver_id)->first();
            $driver_accounts = DriverAccounts::where('driver_id', $account->driver_id)->where('id', '!=', $account->id)->get();

            return view('admin.providers.bank-details',compact('account', 'driver_accounts','banks', 'provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

        public function add_bank($id)
    {
        try {
            $banks = Bank::all();  
            $provider = Provider::where('id', $id)->first();

            return view('admin.providers.add-bank',compact('provider','banks'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function bank_list($id)
    {
        try {
            $provider = Provider::where('id', $id)->first();
            $driver_accounts = DriverAccounts::where('driver_id', $id)->get();

            return view('admin.providers.list-bank',compact( 'driver_accounts', 'provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

        public function store_bank(Request $request){
        try{
            $account = DriverAccounts::where('acc_no', $request->acc_no)->first();
            if(count($account) > 0)
            {
                return back()->with('flash_error', 'Accout Already Exist');
            }

            $account = new DriverAccounts;

            if($request->has('acc_no')) 
                $account->acc_no = $request->acc_no;

            if($request->has('acc_name')) 
                $account->acc_name = $request->acc_name;

            if ($request->has('bank_name'))
                $account->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $account->bank_name_id = $request->bank_name_id;

            if ($request->has('bank_code'))
                $account->bank_code = $request->bank_code;

            if($request->has('is_active')){
                $DriverAccounts = DriverAccounts::where('driver_id',$request->driver_id)->count();
                if($DriverAccounts > 1){
                    if($request->is_active == 1){
                        $account->is_active = 1;
                        $defaultCar = DriverAccounts::where('id', '!=', $DriverAccount->id)->where('driver_id',$request->driver_id)->update(['is_active' => 0]);
                    }else{
                        $account->is_active = 0;
                    }
                }else{
                    $account->is_active = 1;
                }
                    
                    $account->save();
                    
            }
            $account->status = 1;
            $account->driver_id = $request->driver_id;
            $account->save();
                
            return back()->with('flash_success', "Account added successfully");    

        }catch (Exception $e){
            return back()->with('flash_error', trans('api.something_went_wrong'));
        }
    }

    public function update_bank(Request $request){
        try{
            $account = DriverAccounts::where('id', $request->account_id)->first();

            if($request->has('acc_no')) 
                $account->acc_no = $request->acc_no;

            if($request->has('acc_name')) 
                $account->acc_name = $request->acc_name;

            if ($request->has('bank_name'))
                $account->bank_name = $request->bank_name;

            if ($request->has('bank_name_id'))
                $account->bank_name_id = $request->bank_name_id;

            if ($request->has('bank_code'))
                $account->bank_code = $request->bank_code;

            if($request->has('is_active')){
                $DriverAccounts = DriverAccounts::where('driver_id',$account->driver_id)->count();
                $Provider = ProviderProfile::where('provider_id', $account->driver_id)->first();
                if($DriverAccounts > 1){
                    if($request->is_active == 1){
                        $account->is_active = 1;
                       
                            $Provider->acc_no = $account->acc_no;
                        
                            $Provider->acc_name = $account->acc_name;
                       
                            $Provider->bank_name = $account->bank_name;
                        
                            $Provider->bank_name_id = $account->bank_name_id;
                        
                            $Provider->bank_code = $account->bank_code;

                            $Provider->save();

                        $defaultCar = DriverAccounts::where('id', '!=', $account->id)->where('driver_id',$account->driver_id)->update(['is_active' => 0]);
                    }else{
                        $account->is_active = 0;
                    }
                }else{
                    $account->is_active = 1;
                }
                    
                    $account->save();
                    
            }

            $account->save();

            $provider = Provider::where('id', $request->driver_id)->first();

            if ($request->has('wallet_balance')){

                $code = rand(1000, 9999);
                $name = substr($provider->first_name, 0, 2);
                $reference = "RWT".$code.$name;

                $rave_transactions = new RaveTransaction;
                $rave_transactions->driver_id = $provider->id;
                $rave_transactions->reference_id = $reference;
                $rave_transactions->amount = $request->wallet_balance;
                $rave_transactions->status = 1;
                if($request->wallet_balance > 0){
                    $rave_transactions->narration = "Credit from Eganow";
                    $rave_transactions->type = "credit";
                    $rave_transactions->credit = 1;
                }else{
                    $rave_transactions->narration = "Deduction from Eganow";
                    $rave_transactions->type = "debit";
                    $rave_transactions->credit = 0;
                }
                
                $rave_transactions->last_balance = $provider->wallet_balance;
                $rave_transactions->last_availbale_balance = $provider->available_balance;
                $rave_transactions->save();

                $provider->wallet_balance += $request->wallet_balance;
                $provider->available_balance += $request->wallet_balance;
                $provider->save();
            }

                
            return back()->with('flash_success', "Bank details updated");    

        }catch (Exception $e){
            Log::info($e);
            return back()->with('flash_error', trans('api.something_went_wrong'));
        }
    }

    public function license($id)
    {
        try {
            $provider = Provider::with('provider_subaccount')->findOrFail($id);
                                  
            return view('admin.providers.license-details',compact('provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function vehicle($id)
    {
        try {
            $car = DriverCars::where('id', $id)->first();
            $driver_cars = DriverCars::where('driver_id', $car->driver_id)->where('id', '!=', $car->id)->get();
            return view('admin.providers.vehicle-details',compact('car','driver_cars'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function add_car($id)
    {
        try {  
            $provider = Provider::where('id', $id)->first();

            return view('admin.providers.add-vehicle',compact('provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function vehicle_list($id)
    {
        try {
            $provider = Provider::where('id', $id)->first();
            $driver_cars = DriverCars::where('driver_id', $id)->get();
            return view('admin.providers.list-vehicle',compact('driver_cars','provider'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    public function store_car(Request $request){
        try{
            $DriverCar = new DriverCars;
                    
                  
                $DriverCar->driver_id = $request->driver_id;

                if ($request->has('car_registration'))
                    $DriverCar->car_registration = $request->car_registration;

                if ($request->has('car_make'))
                    $DriverCar->car_make = $request->car_make;

                if ($request->has('car_model'))
                    $DriverCar->car_model = $request->car_model;

                if ($request->hasFile('car_picture')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with actual asset url';                    
                        $contents = file_get_contents($request->car_picture);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->car_picture = $s3_url;
                }

                 if ($request->hasFile('insurance_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with actual asset url';                    
                        $contents = file_get_contents($request->insurance_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->insurance_file = $s3_url;
                }

                 if ($request->hasFile('road_worthy_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with actual asset url';                    
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

                  if($request->has('is_active')){
                        $DriverCars = DriverCars::where('driver_id',$request->driver_id)->count();
                        if($DriverCars > 1){
                            if($request->is_active == 1){
                                $DriverCar->is_active = 1;
                                $defaultCar = DriverCars::where('driver_id',$DriverCar->driver_id)->update(['is_active' => 0]);
                            }else{
                                $DriverCar->is_active = 0;
                            }
                        }else{
                            $DriverCar->is_active = 1;
                        }
                            
                            $DriverCar->save();
                    
                    }
                $DriverCar->status = 1;
                $DriverCar->save();

                return back()->with('flash_success', "Car details updated");
                

        }catch (Exception $e){
            Log::info($e);
            return back()->with('flash_error', trans('api.something_went_wrong'));
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
                        $baseurl = 'replace with actual asset url';                    
                        $contents = file_get_contents($request->car_picture);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->car_picture = $s3_url;
                }

                 if ($request->hasFile('insurance_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with actual asset url';                    
                        $contents = file_get_contents($request->insurance_file);
                        $path = Storage::disk('s3')->put('providers/'.$name, $contents);
                        $s3_url = $baseurl.'/'.$name;
                    $DriverCar->insurance_file = $s3_url;
                }

                 if ($request->hasFile('road_worthy_file')){
                    $name = $DriverCar->driver_id."-car-".$DriverCar->id."-".str_replace(' ','_',Carbon::now());
                        $baseurl = 'replace with actual asset url';                    
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
                    $DriverCar = DriverCars::where('id', $request->car_id)->first();
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

                return back()->with('flash_success', "Car details updated");
            }else{
                return back()->with('flash_error', "No Car info found");
            }
                

        }catch (Exception $e){
            Log::info($e);
            return back()->with('flash_error', trans('api.something_went_wrong'));
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
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@drivetry.com');
        }

        // $this->validate($request, [
        //     'first_name' => 'required|max:255',
        //     'last_name' => 'required|max:255',
        //     'mobile' => 'between:6,13',
        //     'avatar' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        // ]);

        try {
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }

            $provider = Provider::with('provider_subaccount')->findOrFail($id); 
            // if($request->acc_no != '' && $request->bank_code !=''){         
            //     if($provider->bank_code != $request->bank_code && $provider->acc_no != $request->acc_no)
            //     {
            //         if(count($provider->provider_subaccount) !=0)
            //         {
            //             $client = new Client(['http_errors' => false]);
            //             $url ="https://api.ravepay.co/v2/gpx/subaccounts/edit";
            //             $headers = [
            //                 'Content-Type' => 'application/json',
            //             ];
            //             $body = [
            //                         "id"                        =>  $provider->provider_subaccount->split_id,
            //                         "account_bank"              =>  $request->bank_code,
            //                         "account_number"            =>  $request->acc_no, 
            //                         "business_name"             =>  $provider->first_name,
            //                         "business_email"            =>  $provider->email,                            
            //                         "seckey"                    =>  env("RAVE_SECRET_KEY")
            //                     ];            
            //             $res = $client->post($url, [
            //                 'headers' => $headers,
            //                 'body' => json_encode($body),
            //             ]);
            //             $subaccount = json_decode($res->getBody(),true);
                        
            //             $subaccount = DriverSubaccount::where('driver_id',$provider->id)->first();
            //             $subaccount->account_number = $request->acc_no;
            //             $subaccount->bank_code = $request->bank_code;
            //             $subaccount->save(); 
            //         } 
            //         else{
            //                 $client = new Client(['http_errors' => false]);
            //                 $url ="https://api.ravepay.co/v2/gpx/subaccounts/create";
            //                 $headers = [
            //                     'Content-Type' => 'application/json',
            //                 ];
            //                 $body = [
            //                             "account_bank"              => $request->bank_code,
            //                             "account_number"            => $request->acc_no, 
            //                             "business_name"             => $provider->first_name,
            //                             "business_email"            => $provider->email,
            //                             "business_contact"          => $provider->first_name,
            //                             "business_contact_mobile"   => $provider->mobile,
            //                             "business_mobile"           => $provider->mobile,
            //                             "country"                   => $request->bankcountry,
            //                             "meta"                      =>  ["metaname" => "MarketplaceID", "metavalue" => "ggs-920900"],
            //                             "seckey"                    =>  env("RAVE_SECRET_KEY")
            //                         ];            
            //                 $res = $client->post($url, [
            //                     'headers' => $headers,
            //                     'body' => json_encode($body),
            //                 ]);
            //                 $subaccount = json_decode($res->getBody(),true);
            //                 \Log::info($subaccount);
            //                 if($subaccount['status'] == 'success')
            //                 {
            //                     $split                  = new DriverSubaccount;
            //                     $split->driver_id       = $provider->id;
            //                     $split->split_id        = $subaccount['data']['id'];
            //                     $split->account_number  = $subaccount['data']['account_number'];
            //                     $split->bank_code       = $subaccount['data']['account_bank'];
            //                     $split->business_name   = $subaccount['data']['business_name'];
            //                     $split->fullname        = $subaccount['data']['fullname'];
            //                     $split->date_created    = $subaccount['data']['date_created'];
            //                     $split->account_id      = $subaccount['data']['account_id'];
            //                     $split->split_ratio     = $subaccount['data']['split_ratio'];
            //                     $split->split_type      = $subaccount['data']['split_type'];
            //                     $split->split_value     = $subaccount['data']['split_value'];
            //                     $split->subaccount_id   = $subaccount['data']['subaccount_id'];
            //                     $split->bank_name       = $subaccount['data']['bank_name'];
            //                     $split->country         = $subaccount['data']['country'];
            //                     $split->save();
            //                 }
            //                 else{
            //                     $client = new Client(['http_errors' => false]);
            //                     $url ="https://api.ravepay.co/v2/gpx/subaccounts/delete";
            //                     $headers = [
            //                         'Content-Type' => 'application/json',
            //                     ];
            //                     $body = ['id' => $subaccount['data']['id'], 'seckey' => env("RAVE_SECRET_KEY")];
            //                     $res = $client->post($url, [
            //                         'headers' => $headers,
            //                         'body' => json_encode($body),
            //                     ]);
            //                     $delete = json_decode($res->getBody(),true);
            //                     \Log::info($subaccount['message']);
            //                     return back()->with('flash_error', $subaccount['message']);
            //                 }   
            //             }         
            //     }
            // }

             if($request->hasFile('avatar')) {
                    $rand = rand(1000, 9999);
                    $name = $rand."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual asset url';                    
                    $contents = file_get_contents($request->avatar);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $provider->avatar = $s3_url;
            }
            
            if ($request->has('wallet_balance')){

                

                $code = rand(1000, 9999);
                $name = substr($provider->first_name, 0, 2);
                $reference = "RWT".$code.$name;

                $rave_transactions = new RaveTransaction;
                $rave_transactions->driver_id = $provider->id;
                $rave_transactions->reference_id = $reference;
                $rave_transactions->amount = $request->wallet_balance;
                $rave_transactions->status = 1;
                if($request->wallet_balance > 0){
                    $rave_transactions->narration = "Credit from Eganow";
                    $rave_transactions->type = "credit";
                    $rave_transactions->credit = 1;
                }else{
                    $rave_transactions->narration = "Deduction from Eganow";
                    $rave_transactions->type = "debit";
                    $rave_transactions->credit = 0;
                }
                
                $rave_transactions->last_balance = $provider->wallet_balance;
                $rave_transactions->last_availbale_balance = $provider->available_balance;
                $rave_transactions->save();

                $provider->wallet_balance += $request->wallet_balance;
                $provider->available_balance += $request->wallet_balance;

                
            }

            if($request->has('first_name')) 
            $provider->first_name = $request->first_name;

            if($request->has('last_name')) 
            $provider->last_name = $request->last_name;

            if ($request->has('fleet')){
                $provider->fleet = $request->fleet;
            }
            else{
                $provider->fleet = 0;
            }
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

            if ($request->hasFile('car_picture')){
                    $name = $Provider->provider_id."-car-".$Provider->id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual asset url';                    
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

            

            return redirect()->back()->with('flash_success', 'Provider Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Provider Not Found');
        }
    }

    public function credit(Request $request){
        try{

            $provider = Provider::where('id',$request->driver_id)->first();

            $code = rand(1000, 9999);
            $name = substr($provider->first_name, 0, 2);
            $reference = "RCR".$code.$name;

            $rave_transactions = new RaveTransaction;
            $rave_transactions->driver_id = $provider->id;
            $rave_transactions->reference_id = $reference;
            $rave_transactions->amount = $request->amount;
            $rave_transactions->status = 1;
            $rave_transactions->narration = "Credit from Eganow";
            $rave_transactions->type = "credit";
            $rave_transactions->credit = 1;
            $rave_transactions->last_balance = $provider->wallet_balance;
            $rave_transactions->last_availbale_balance = $provider->available_balance;
            $rave_transactions->save();

            $provider->wallet_balance += $request->amount;
            $provider->available_balance += $request->amount;
            $provider->save();

            return back()->with('flash_success', 'Amount credited to wallet');

        }catch (Exception $e){
            Log::info($e);
            return back()->with('flash_error', trans('api.something_went_wrong'));
        }
    }


    public function debit(Request $request){

            try{
                $provider = Provider::where('id',$request->driver_id)->first();

                $code = rand(1000, 9999);
                $name = substr($provider->first_name, 0, 2);
                $reference = "RDB".$code.$name;

                $rave_transactions = new RaveTransaction;
                $rave_transactions->driver_id = $provider->id;
                $rave_transactions->reference_id = $reference;
                $rave_transactions->amount = $request->amount;
                $rave_transactions->status = 1;
                $rave_transactions->narration = "Deduction from Eganow";
                $rave_transactions->type = "debit";
                $rave_transactions->credit = 0;
                $rave_transactions->last_balance = $provider->wallet_balance;
                $rave_transactions->last_availbale_balance = $provider->available_balance;
                $rave_transactions->save();

                $provider->wallet_balance -= $request->amount;
                $provider->available_balance -= $request->amount;

                $provider->save();

                return back()->with('flash_success', 'Amount debited from wallet');
            }catch (Exception $e){
                Log::info($e);
                return back()->with('flash_error', trans('api.something_went_wrong'));
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
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@drivetry.com');
        }

        try {
            // Provider::find($id)->delete();
            // ProviderService::where('provider_id', $id)->delete();
            // ProviderProfile::where('provider_id', $id)->delete();
            // ProviderDocument::where('provider_id', $id)->delete();
            $user = Provider::find($id);
            $user->email = $user->email . $user->id;
            $user->archive = 1;
            $user->save();
            return back()->with('flash_success', 'Provider deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Provider Not Found');
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
                $Provider->document_uploaded = 1;
                if(is_null($Provider->wallet_balance)){
                    $Provider->wallet_balance = 0;
                }
                $Provider->approved_at = Carbon::now();
                $Provider->approved_by = Auth::guard('admin')->user()->id;
                $Provider->save();

                $to = $Provider->country_code.$Provider->mobile;
                $from = "Eganow Driver";            
                $content = urlencode("Congrats. Your verification is complete. You will now receive Eganow bookings");
                $clientId = env("HUBTEL_API_KEY");
                $clientSecret = env("HUBTEL_API_SECRET");            
                $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);

                (new SendPushNotification)->DriverApproved($Provider->id); 
                return back()->with('flash_success', "Provider Approved");
            } else {
                return redirect()->route('admin.provider.document.index', $id)->with('flash_error', "Provider has not been assigned a service type!");
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
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@drivetry.com');
        }
        
        Provider::where('id',$id)->update(['status' => 'banned']);
         (new SendPushNotification)->DriverDisapproved($id); 
        return back()->with('flash_success', "Provider Disapproved");
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

            return view('admin.request.index', compact('requests'));
        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    }

    /**
     * account statements.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function statement($id){

        try{
            $requests = UserRequests::where('provider_id',$id)
                        ->where('status','COMPLETED')
                        ->with('payment')
                        ->get();

            $rides = UserRequests::where('provider_id',$id)->with('payment')->orderBy('id','desc')->paginate(10);
            $cancel_rides = UserRequests::where('status','CANCELLED')->where('provider_id',$id)->count(); 
            $Provider = Provider::find($id);
            $revenue =  UserRequestPayment::whereHas('request', function($query) use($id) {
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

    public function Accountstatement($id){

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

            return view('account.providers.statement', compact('rides','cancel_rides','revenue'))
                        ->with('page',$Provider->first_name."'s Overall Statement ". $Joined);

        } catch (Exception $e) {
            return back()->with('flash_error','Something Went Wrong!');
        }
    } 

    /**
     * Used to Upload Driver Documents
     */
    public function uploaddocument($id){
        try {
            $VehicleDocuments = Document::vehicle()->get();
            $DriverDocuments = Document::driver()->get();
            $Provider = Provider::find($id);
            return view('admin.providers.document.driverdocuments', compact('VehicleDocuments', 'DriverDocuments','Provider'));
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Something went wrong');
        }
    }

    /**
     * Used to Update Driver Document
     */
    public function updatedocument(Request $request, $id, $document_id){
        $this->validate($request, [
            'document' => 'mimes:jpg,jpeg,png,pdf',
        ]);
        try {
            
            $Document = ProviderDocument::where('provider_id', $id)
                ->where('document_id', $document_id)
                ->firstOrFail();
                    $name = $Document->provider_id."-doc-".$Document->id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual aset url';                    
                    $contents = file_get_contents($request->document);
                    $path = Storage::disk('s3')->put('driver_documents/'.$name, $contents);
                    $url = $baseurl.'/'.$name;
            $Document->update([
                'url' => $url,
                'status' => 'ASSESSING',
            ]);

            return back()->with('flash_success', 'Document Uploaded Successfully');

        } catch (ModelNotFoundException $e) {
                    $name = $id."-doc-".$document_id."-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual aset url';                    
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
    }

    /**
     * Used to update fleet auto payout status
     */
    public function autopayout(Request $request){
         try {
             $fleet = Fleet::find($request->id);
            if($request->status == '1') {
                $fleet->driver_payout = 0;
            }
            else{
                $fleet->driver_payout = 1;
            }
             $fleet->save();
             return 1; 
         } catch (\Throwable $th) {
             return 0;
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
                        $html .= '<option value="'.$bank['Name'].'" data-code="'.$bank['Code'].'">'.$bank['Name'].'</option>';
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

    /**
     * Used to send missing documents notification
     */
    public function notifymissingdocuments(){
        try {
            $documents = Provider::all();
            if(!empty($documents)){
                foreach ($documents as $key => $value) {
                    if($value->pending_documents() > 0){                        
                        $to = $provider->country_code.$provider->mobile;
                        $from = "Eganow Team";            
                        $content = urlencode("This is the Eganow Team. You have not uploaded your documents for approval yet.  Please upload in driver app for approval  before you can drive on Eganow.");
                        $clientId = env("HUBTEL_API_KEY");
                        $clientSecret = env("HUBTEL_API_SECRET");            
                        $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                    }
                }
            }
            return back()->with('flass_success', 'Notification sent to driver');
        } catch (\Throwable $th) {
            return back()->with('flash_error', 'Unable to send message');
        }
    }

    public function approveTrialPeriod(){
        try {
                $drivers = DB::table('providers')->where('status','!=','approved')->where('archive','0')->get();
                $documents = Document::all()->count();
                    if(!empty($drivers)){
                        foreach ($drivers as $key => $driver) {
                            $update = Provider::find($driver->id);
                                if($documents != $update->accessed_documents()){
                                    $update->status = 'approved';
                                    $update->approved_at = Carbon::now();
                                    $update->save();
                                    $to = $driver->country_code.$driver->mobile;
                                    $from = "Eganow Team";            
                                    $content = urlencode("Great news! Eganow is live! Your account has been activated. You will need to upload all documents in 14 days to remain activated. Stay online to get requests!");
                                    $clientId = env("HUBTEL_API_KEY");
                                    $clientSecret = env("HUBTEL_API_SECRET");            
                                    $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
                                    (new SendPushNotification)-> DriverTrialStart($driver->id);
                                } 
                            }
                    }
                return back()->with('flass_success', 'Trial Activation Notification sent to '.count($drivers).' driver');
        } catch (\Throwable $th) {
            Log::info($th);
            return back()->with('flash_error', 'Unable to send message');
        }
    }

    public function bank_approve($id)
    {
        try {
            
            $account = DriverAccounts::where('id', $id)->first();
            if($account->acc_no != '') {
                $account->status = 1;
                $account->save();
                return back()->with('flash_success', "Bank details verfied");
            } else {
                return back()->with('flash_error', "Driver has not given Bank details!");
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
    public function bank_disapprove($id){
        
        try {
            
            $account = DriverAccounts::where('id', $id)->first();
            if($account->acc_no != '') {
                $account->status = 0;
                $account->save();
                return back()->with('flash_success', "Bank details verification declined");
            } else {
                return back()->with('flash_error', "Driver has not given Bank details!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function license_approve($id)
    {
        try {
            
            $Provider = ProviderProfile::where('provider_id', $id)->first();
            if($Provider->dl_no != '') {
                $Provider->license_status = 1;
                $Provider->save();
                return back()->with('flash_success', "Driving License details verfied");
            } else {
                return back()->with('flash_error', "Driver has not given Driving License details!");
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
    public function license_disapprove($id){
        
            $Provider = ProviderProfile::where('provider_id', $id)->first();
            $Provider->license_status = 0;
            $Provider->save();

        return back()->with('flash_success', "Driving License Details declined");
    }

    public function vehicle_approve($id)
    {
        try {
            
            $car = DriverCars::where('id', $id)->first();
            if($car->car_registration != '') {
                $car->status = 1;
                $car->save();
                return back()->with('flash_success', "Vehicle details verfied");
            } else {
                return back()->with('flash_error', "Driver has not given Vehicle details!");
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
   public function vehicle_disapprove($id)
    {
        try {
            
            $car = DriverCars::where('id', $id)->first();
            if($car->car_registration != '') {
                $car->status = 0;
                $car->save();
                return back()->with('flash_success', "Vehicle verification declined");
            } else {
                return back()->with('flash_error', "Driver has not given Vehicle details!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function makeOnline_drivers(){

    try{
        $drivers = DB::table('providers')->where('archive','0')->where('status', 'approved')->get();
            if(!empty($drivers)){
                foreach ($drivers as $key => $driver) {
                    $update = Provider::find($driver->id);
                    $update->availability = 1;
                    $update->available_on = Carbon::now();
                    $update->online_by = Auth::guard('admin')->user()->id;
                    $update->save();
                    // (new SendPushNotification)->DriverOnline($driver->id);
                }
                
            }
            return back()->with('flash_success', "Made ".count($drivers)."  drivers Online successfully!");
        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error: Not able to make driver online');
        }
    }


    public function makeOffline_drivers(){

    try{
        $drivers = DB::table('providers')->where('availability','1')->where('archive','0')
                        // ->where('available_on','<=',\Carbon\Carbon::now()->subHours(12))
                        ->get();
        if(!empty($drivers)){
            foreach ($drivers as $key => $driver) {
                $update = Provider::find($driver->id);
                $update->availability = 0;
                $update->save();
                // (new SendPushNotification)->DriverOffline($driver->id);
            }
        }
        return back()->with('flash_success', "Made ".count($drivers)." drivers offline successfully with notification");
        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error: Not able to make driver offline');
        }
    }

    public function promo_drivers(Request $request)
    {
        try {

            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('promo_driver', 1)
                        ->orderBy('updated_at', 'DESC');
            $promo_drivers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('promo_driver', 1)
                        ->orderBy('updated_at', 'DESC')->count();
            $online_promo_drivers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('promo_driver', 1)->where('availability',1)
                        ->orderBy('updated_at', 'DESC')->count();
            $approved_drivers = Provider::where('status','approved')->where('archive', '!=', 1)->count();
            $online_drivers = Provider::where('status','approved')->where('availability', 1)->where('archive', '!=', 1)->count();
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            $drivers = Provider::where('archive', '!=', 1)->pluck('id');
            $contacted_drivers = DriverComments::whereIn('driver_id',$drivers)->groupBy('driver_id')->distinct()->get();
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $search = $request->search;
                $providers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')->where('archive', '!=', 1)->where('status','approved')->where('promo_driver', 1)
                        ->where(function($q) use ($search) { $q->where('first_name','like', '%'.$search.'%')->orwhere('email','like', '%'.$search.'%')->orwhere('mobile','like', '%'.$search.'%');
                                    })->get();
            }else{
                if($request->has('filter')){
                    if($request->filter == 1){
                        $page = 'List of Promo Drivers to pay';
                        $providers = $AllProviders->where('bonus',0)->paginate(300);
                    }else if($request->filter == 2){
                        $page = 'List of Paid Promo Drivers';
                        $providers = $AllProviders->where('bonus',1)->paginate(300);
                    }
                }else{
                    $page = 'List of Promo Drivers';
                    $providers = $AllProviders->paginate(300);
                }   
                
            }
            $user_requests = $last_user_requests = 0;
            Carbon::setWeekStartsAt(Carbon::FRIDAY);
            Carbon::setWeekEndsAt(Carbon::THURSDAY);
            for ($i=0; $i < count($providers); $i++) {
                $providers[$i]->user_requests = 0;
                $providers[$i]->last_user_requests = 0;
                $user_referred = User::where('driver_referred', $providers[$i]->referal)->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->get();
                $last_user_referred = User::where('driver_referred', $providers[$i]->referal)->where('created_at', '>', Carbon::now()->startOfWeek()->subWeek())->where('created_at', '<', Carbon::now()->endOfWeek()->subWeek())->get();
                
                //Users Referred by Driver with Completed Requests
                foreach ($user_referred as $user) {
                    $requests = UserRequests::where('user_id', $user->id)->where('status', 'COMPLETED')->count();
                    if($requests > 0){
                       $providers[$i]->user_requests =  $user_requests + 1;
                    }
                     
                }

                foreach ($last_user_referred as $last_user) {
                    $last_requests = UserRequests::where('user_id', $last_user->id)->where('status', 'COMPLETED')->count();
                    if($last_requests > 0){
                       $providers[$i]->last_user_requests =  $last_user_requests + 1;
                    }
                     
                }

                $providers[$i]->user_referral = count($user_referred);
                $providers[$i]->last_user_referral = count($last_user_referred);
                $providers[$i]->driver_referral = Provider::where('driver_referred', $providers[$i]->referal)->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->count();

                $providers[$i]->last_driver_referral = Provider::where('driver_referred', $providers[$i]->referal)->where('created_at', '>', Carbon::now()->startOfWeek()->subWeek())->where('created_at', '<', Carbon::now()->endOfWeek()->subWeek())->count();

                $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->count();

                $providers[$i]->last_online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->where('created_at', '>', Carbon::now()->startOfWeek()->subWeek())->where('created_at', '<', Carbon::now()->endOfWeek()->subWeek())->count();

                //Active Working Hours for the month by Driver
                $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
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

                $last_activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->where('created_at', '>', Carbon::now()->startOfWeek()->subWeek())->where('created_at', '<', Carbon::now()->endOfWeek()->subWeek())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
                    if($last_activeHours[0] > 0){ 

                        if($last_activeHours[0] >= 60){
                            $last_activeHour = number_format(($last_activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $last_activeHour = number_format($last_activeHours[0], 2) . " mins";
                        }

                        $providers[$i]->last_activeHoursFormat = $last_activeHour;
                        $providers[$i]->last_activeHours = $last_activeHours[0] / 60;
                    }else{
                        $providers[$i]->last_activeHoursFormat = "N / A";
                        $providers[$i]->last_activeHours = 0;
                    }

                //Calculate Driver Earning for the month 
                $requests = UserRequests::where('provider_id', $providers[$i]->id)->with('payment')->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->get();
                $trip_earning = 0;
                if(count($requests) > 0) {
                    for($j=0; $j < count($requests); $j++) {
                        if($requests[$j]->payment){
                            $trip_earning += ($requests[$j]['payment']['driver_earning']);
                        }
                    }
                }
                $providers[$i]->earnings = $trip_earning;

                //Checking no Incoming Request for last 12 hours
                    $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->count();

                    //No. of Request completed in last 24 hours
                    $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('created_at', '>', Carbon::now()->startOfWeek())->where('created_at', '<', Carbon::now()->endOfWeek())->where('status', 'COMPLETED')->count();
                    if($request > 0){
                        $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                    }else{
                        $providers[$i]->completed_ratio = 0;
                    }
                    
                    $providers[$i]->received = $request;
                    $providers[$i]->completed = $completed_request;

                    // Last Week Data

                    $last_request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('created_at', '>', Carbon::now()->startOfWeek()->subWeek())->where('created_at', '<', Carbon::now()->endOfWeek()->subWeek())->count();

                    //No. of Request completed in last 24 hours
                    $last_completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('created_at', '>', Carbon::now()->startOfWeek()->subWeek())->where('created_at', '<', Carbon::now()->endOfWeek()->subWeek())->where('status', 'COMPLETED')->count();
                    if($last_request > 0){
                        $providers[$i]->last_completed_ratio = round((( $last_completed_request / $last_request ) * 100));
                    }else{
                        $providers[$i]->last_completed_ratio = 0;
                    }
                    
                    $providers[$i]->last_received = $last_request;
                    $providers[$i]->last_completed = $last_completed_request;


            }

            $document = Document::all()->count();

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('admin.providers.promo_drivers', compact('providers','page','document','approved_drivers','online_drivers', 'total_drivers','contacted_drivers','offline_drivers','recent_documents','promo_drivers', 'online_promo_drivers', 'user_requests'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function pay_promo_drivers(){
        $drivers = DB::table('providers')->where('archive','0')
                        ->where('promo_driver',1)
                        ->where('credits', '>', 0)
                        ->get();
        if(!empty($drivers)){
            foreach ($drivers as $key => $driver) {
                $update = Provider::find($driver->id);
                $update->wallet_balance += $update->credits;
                $update->credits = 0;
                $update->save();

                $code = rand(1000, 9999);
                $name = substr($update->first_name, 0, 2);
                $reference = "BWT".$code.$name;

                $rave_transactions = new RaveTransaction;
                $rave_transactions->driver_id = $update->id;
                $rave_transactions->reference_id = $reference;
                $rave_transactions->narration = $update->wallet_balance." added to your wallet by Eganow";
                $rave_transactions->amount = $update->wallet_balance;
                $rave_transactions->status = 1;
                $rave_transactions->type = 'credit';
                $rave_transactions->save();
            }
        }
        return back()->with('flash_success', 'Add the bonus to Promo Drivers Wallet');
    }

    public function credit_promo_drivers(Request $request, $id){
        if($request->has('filter')){
            $online_bonus = Setting::get('promo_drivers_online_bonus', '0');
            $driver_referral_bonus = Setting::get('promo_drivers_driver_referral_bonus', '0');
            $user_referral_bonus = Setting::get('promo_drivers_user_referral_bonus', '0');
            $reward_hours_limit = Setting::get('reward_hours_limit', '0');
            $driver = Provider::find($id);
                if($request->filter == 1){
                    $OnlineCredit = OnlineCredit::where('driver_id', $driver->id)->where('status', 0)->get();
                    
                    if(count($OnlineCredit) >= $reward_hours_limit){
                        $driver->online_paid = $OnlineCredit->count();
                        $driver->credits += $online_bonus;
                        $driver->save();

                        foreach ($OnlineCredit as $credit) {
                            $OnlineCredits = OnlineCredit::findOrFail($credit->id);
                            $OnlineCredits->status = 1;
                            $OnlineCredits->save();
                        } 
                    }else{
                        return back()->with('flash_error', 'Driver Should have atleast 20 points to receive Bonus');
                    }
                    

                    
                    
                }else if($request->filter == 2){
                    $driver->driver_referral_credits += Provider::where('driver_referred', $driver->referal)->count() - $driver->driver_referral_credits;
                    $driver->credits += ($driver->driver_referral_credits * $driver_referral_bonus);
                    $driver->save();
                }else if($request->filter == 3){
                    $driver->user_referral_credits += User::where('driver_referred', $driver->referal)->count() - $driver->user_referral_credits;
                    $driver->credits += ($driver->user_referral_credits * $user_referral_bonus);
                    $driver->save();
                }

            return back()->with('flash_success', 'Add the bonus to Promo Drivers Credits');
        }
    }

    public function credit_all_promo_drivers(Request $request){
        if($request->has('filter')){
            $online_bonus = Setting::get('promo_drivers_online_bonus', '0');
            $driver_referral_bonus = Setting::get('promo_drivers_driver_referral_bonus', '0');
            $user_referral_bonus = Setting::get('promo_drivers_user_referral_bonus', '0');
            $reward_hours_limit = Setting::get('reward_hours_limit', '0');
            $promo_drivers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('promo_driver', 1)
                        ->orderBy('upload_notify', 'DESC')->get();
            if($promo_drivers){
                if($request->filter == 1){
                    foreach ($promo_drivers as $driver) {
                        $OnlineCredit = OnlineCredit::where('driver_id', $driver->id)->where('status', 0)->get();
                         if(count($OnlineCredit) >= $reward_hours_limit){
                            $driver->online_paid = $OnlineCredit->count();
                            $driver->credits += $online_bonus;
                            $driver->save();

                            foreach ($OnlineCredit as $credit) {
                                $OnlineCredits = OnlineCredit::findOrFail($credit->id);
                                $OnlineCredits->status = 1;
                                $OnlineCredits->save();
                            } 
                        }
                       
                    }
                }else if($request->filter == 2){
                    foreach ($promo_drivers as $driver) {
                        $driver->driver_referral_credits += Provider::where('driver_referred', $driver->referal)->count() - $driver->driver_referral_credits;
                        $driver->credits += ($driver->driver_referral_credits * $driver_referral_bonus);
                        $driver->save();
                    }
                }else if($request->filter == 3){
                    foreach ($promo_drivers as $driver) {
                        $driver->user_referral_credits += User::where('driver_referred', $driver->referal)->count() - $driver->user_referral_credits;
                        $driver->credits += ($driver->user_referral_credits * $user_referral_bonus);
                        $driver->save();
                    }
                }

                return back()->with('flash_success', 'Add the bonus to Promo Drivers Credits');
            }else{
                return back()->with('flash_success', 'No Elligible Promo Drivers to credit');
            }
        }
    }

    public function add_promo_driver($id)
    {
        try {
            $Provider = Provider::findOrFail($id);
            if($Provider) {
                $Provider->promo_driver = 1;
                $Provider->promo_added_at = Carbon::now();
                $Provider->save();
                return back()->with('flash_success', "Driver Added to Promo Drivers");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver has not been assigned as Promo Driver!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function remove_promo_driver($id)
    {
        try {
            $Provider = Provider::findOrFail($id);
            if($Provider) {
                $Provider->promo_driver = 0;
                $Provider->save();
                return back()->with('flash_success', "Driver Removed from Promo Drivers");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver can not be removed from Promo Driver!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function status_update(Request $request, $id, $status)
    {        

            $Provider = Provider::where('id',$id)->first();

            if($status == "active"){
                    $service_status = "active";
                    $Driveractivity = new DriverActivity;
                    $Driveractivity->is_active = 1;
                    $Driveractivity->driver_id = $id;
                    $Driveractivity->start = Carbon::now();
                    $Driveractivity->save();

                    $Provider->available_on = Carbon::now();
                    $Provider->availability = 1;
                    $Provider->save();
                    
                    (new SendPushNotification)->DriverOnline($Provider->id);
                    

            }else{
                $service_status = "offline";
                $Driveractivity = DriverActivity::where('driver_id', $id)->where('is_active', 1)->first();
                if(count($Driveractivity) != 0){

                    $Driveractivity->is_active = 0;
                    $Driveractivity->driver_id = $id;
                    $Driveractivity->end = Carbon::now();
                    $min = $Driveractivity->end->diffInMinutes($Driveractivity->start, true);

                    $Driveractivity->working_time = $min;
                    $Driveractivity->save();

                    $Provider->available_on = Carbon::now();
                    $Provider->availability = 0;
                    $Provider->save();
                    (new SendPushNotification)->AdminDriverOffline($Provider->id);
                    

                }else{
                    
                    // $Driveractivity = new DriverActivity;
                    // $Driveractivity->is_active = 0;
                    // $Driveractivity->driver_id = $id;
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
            $activeHours = DriverActivity::where('driver_id', $id)->whereDate('created_at', Carbon::today())->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');

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
            // $Provider->service->update(['status' => $request->service_status]);
        } else {
            return back()->with('flash_error', 'You account has not been approved for driving');
        }
        
        return back()->with('flash_success', "Driver availability status updated");
    }

    public function withdraw_sp(Request $request){
        try{
            return back()->with('flash_error', "Temporarily, withdrawal feature is disabled!");

            $ProviderBanks = DriverAccounts::where('driver_id', $request->driver_id)->get();
            if(count($ProviderBanks) > 1){
                $ProviderBank = DriverAccounts::where('driver_id', $request->driver_id)->where('is_active')->first();
            }else{
                $ProviderBank = DriverAccounts::where('driver_id', $request->driver_id)->first();
            }
            
            if(count($ProviderBank) ==0){
                $ProviderBank = ProviderProfile::where('provider_id', $request->driver_id)->first();
            }
            
            if($ProviderBank->acc_no == ""){
                return back()->with('flash_error', 'Bank / Mobile Money account has not been configured');
            }
                $Provider = Provider::where('id',$request->driver_id)->first();
                $code = rand(1000, 9999);
                $name = substr($Provider->first_name, 0, 2);
                $reference = "AWD".$code.$name;
                $banks = array("SLYDEPAY", "MTN_MONEY", "AIRTEL_MONEY", "VODAFONE_CASH", "nib-account-fi-service", "prudential-account-fi-service", "gt-account-fi-service", "heritage-account-fi-service", "fnb-account-fi-service", "sovereign-account-fi-service", "umb-account-fi-service", "zenith-account-fi-service", "baroda-account-fi-service", "access-account-fi-service", "cal-account-fi-service", "energy-account-fi-service", "standardchartered-account-fi-service", "ecobank-account-fi-service", "barclays-account-fi-service", "gcb-account-fi-service", "stanbic-account-fi-service", "adb-account-fi-service", "uba-account-fi-service", "royal-account-fi-service", "fidelity-account-fi-service" );

                if(!in_array($ProviderBank->bank_code, $banks)){
                    return back()->with('flash_error', 'Bank / Mobile Money account has not been configured');
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
                return back()->with('flash_error', 'Withdrawal failed. No sufficient funds, Please try later');
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
                    $body['agencyUsername'] = "replace with actual email";
                    $body['agencyPassword'] = "replace with agency paassword";      
                    // dd($body);          

                    // $headers = ['Authorization' => 'Bearer '. $access_token, 'Content-Type' => 'application/vnd.identity-specs.v2+json', 'Accept' => 'application/vnd.identity-specs.v2+json'];
                    
Log::info("End Point: ". $url ."Body: ".json_encode($body));
                    $res = $client->post($url, ['json' => $body]);

                    // $code = $res->getStatusCode();
                    $transfer = array();
                    $transfer = json_decode($res->getBody(),'true');
                    Log::info("SlydePay Withdraw Response: ". json_encode($transfer));
                   
                    if($transfer['success'] == 'true'){
                            $Provider = Provider::where('id',$request->driver_id)->first();
                            $Provider->wallet_balance = $Provider->wallet_balance - $request->amount;
                            $Provider->available_balance = $Provider->available_balance - $request->amount;
                            $Provider->save();
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = $request->driver_id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->last_availbale_balance = $Provider->available_balance;
                            $rave_transactions->rave_ref_id = $transfer['transactionId'];
                            $rave_transactions->narration = "Wallet Withdrawal";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->status = 1;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                           
                            return back()->with('flash_success', 'Transaction Successful, Funds will be deposited shortly');
                    }else{
                            $rave_transactions = new RaveTransaction;
                            $rave_transactions->driver_id = $request->driver_id;
                            $rave_transactions->reference_id = $reference;
                            $rave_transactions->last_balance = $Provider->wallet_balance;
                            $rave_transactions->last_availbale_balance = $Provider->available_balance;
                            $rave_transactions->rave_ref_id = $transfer['transactionId'];
                            $rave_transactions->narration = "Wallet Withdrawal failed";
                            $rave_transactions->amount = number_format($request->amount,2);
                            $rave_transactions->status = 0;
                            $rave_transactions->type = "debit";
                            $rave_transactions->save();
                        return back()->with('flash_error', "Withdrawal failed. Please try later!");
                    }
                
            }
        }catch(Exception $e){
            Log::info($e);
            return back()->with('flash_error', trans('api.something_went_wrong'));
        }
    }

    public function official_drivers(Request $request)
    {
        try {

            $active = 0;
            $offline = $online = $riding = array();
            $top_completed_ratio = $top_referrer =  array();
            $amb_user_referrals = $amb_driver_referrals = $amb_user_ref_booked = $amb_user_ref_completed = $amb_driver_ref_rec = $amb_driver_ref_completed = $ratio = $ref = 0;
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved','official')->has('official')
                        ->where('archive', '!=', 1)
                        // ->where('status','approved')
                        ->where('official_drivers', 1)
                        ->orderBy('updated_at', 'DESC');
            $ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('official_drivers', 1)
                        ->orderBy('updated_at', 'DESC')->count();
            $online_ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('official_drivers', 1)->where('availability',1)
                        ->orderBy('updated_at', 'DESC')->count();
            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
                        
            $ut_drivers = OfficialDriver::where('status','!=',1)->whereIn('supplier_id', $fleets)->count();
           
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $search = $request->search;
                $providers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved','official')->has('official')->where('archive', '!=', 1)
                            // ->where('status','approved')
                            ->where('official_drivers', 1)
                        ->where(function($q) use ($search) { $q->where('first_name','like', '%'.$search.'%')->orwhere('email','like', '%'.$search.'%')->orwhere('mobile','like', '%'.$search.'%');
                                    })->get();
            }else{
                if($request->has('filter')){
                    if($request->filter == 1){
                        $page = 'List of DriveNow Drivers to pay';
                        $providers = $AllProviders->where('bonus',0)->paginate(300);
                    }else if($request->filter == 2){
                        $page = 'List of Paid DriveNow Drivers';
                        $providers = $AllProviders->where('bonus',1)->paginate(300);
                    }else if($request->filter == 3){
                        $page = 'List of Untapped DriveNow Drivers';
                        $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
                        
                        $providers = $AllProviders->whereHas('official', function($query) use($fleets) {
                            $query->whereIn('supplier_id', $fleets);})->get();
                    }
                }else{
                    $page = 'List of DriveNow Drivers';
                    $providers = $AllProviders->paginate(300);
                }   
                
            }

                    $day_start = Setting::get('day_start', '08:00').":00";
                    $day_end = Setting::get('day_end', '18:00').":00";
                    $night_start = Setting::get('night_start', '08:00').":00";
                    $night_end = Setting::get('night_end', '18:00').":00";

                    $drivenow_start = Setting::get('drivenow_start', '08:00').":00";
                    $drivenow_end = Setting::get('drivenow_end', '18:00').":00";
        
            if($request->has('filter_date')){

                $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));

                $page = "DriveNow Drivers from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
                $search ="";
                $data = "4";
                
                for ($i=0; $i < count($providers); $i++) {

                    $user_requests = $last_user_requests = $driver_requests = $user_booked  = $driver_rec = 0;
                   
                    $user_referred = User::where('driver_referred', $providers[$i]->referal)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                        
                    $providers[$i]->user_referral = count($user_referred);
                    
                    $driver_referred = Provider::where('driver_referred', $providers[$i]->referal)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                    
               
                    $providers[$i]->driver_referral = count($driver_referred);

                    $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                       
                    //Active Working Hours for the month by Driver
                    $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->whereBetween('created_at',[date($dates[0]), date($dates[1])])->pluck('activeHours');
                    

                    $day_activeHours = DriverActivity::where('driver_id', $providers[$i]->id)
                                                    ->select([DB::raw("SUM(working_time) as activeHours")])
                                                    ->whereTime('start','>=',$day_start)
                                                    ->whereTime('end','<=',$day_end)
                                                    ->whereBetween('created_at',[date($dates[0]), date($dates[1])])
                                                    ->pluck('activeHours');

                    $night_activeHours = DriverActivity::where('driver_id', $providers[$i]->id)
                                                    ->select([DB::raw("SUM(working_time) as activeHours")])
                                                    ->whereTime('start','>=',$night_start)
                                                    ->whereTime('end','<=',$night_end)
                                                    ->whereBetween('created_at',[date($dates[0]), date($dates[1])])
                                                    ->pluck('activeHours');
                                                    // ->get();

                    $breakHours = DriverActivity::where('driver_id',$providers[$i]->id)->whereTime('start','>=',$drivenow_start)
                                                ->whereTime('end','<=',$drivenow_end)->whereBetween('created_at',[date($dates[0]), date($dates[1])])
                                                ->select([DB::raw("SUM(break_time) as breakHours")])->pluck('breakHours');

                        if($breakHours[0] > 0){ 

                            if($breakHours[0] >= 60){
                                $breakHour = number_format(($breakHours[0] / 60), 2) ." Hrs";
                            }else{
                                $breakHour = number_format($breakHours[0], 2) . " mins";
                            }

                            $providers[$i]->breakHoursFormat = $breakHour;
                            $providers[$i]->breakHours = $breakHours[0] / 60;
                        }else{
                            $providers[$i]->breakHoursFormat = "N / A";
                            $providers[$i]->breakHours = 0;
                        }
                    

                    // dd($night_activeHours);
                    if($night_activeHours[0] > 0){ 

                        if($night_activeHours[0] >= 60){
                            $night_activeHour = number_format(($night_activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $night_activeHour = number_format($night_activeHours[0], 2) . " mins";
                        }

                        $providers[$i]->night_activeHoursFormat = $night_activeHour;
                        $providers[$i]->night_activeHours = $night_activeHours[0] / 60;
                    }else{
                        $providers[$i]->night_activeHoursFormat = "N / A";
                        $providers[$i]->night_activeHours = 0;
                    }

                    if($day_activeHours[0] > 0){ 

                        if($day_activeHours[0] >= 60){
                            $day_activeHour = number_format(($day_activeHours[0] / 60), 2) ." Hrs";
                        }else{
                            $day_activeHour = number_format($day_activeHours[0], 2) . " mins";
                        }

                        $providers[$i]->day_activeHoursFormat = $day_activeHour;
                        $providers[$i]->day_activeHours = $day_activeHours[0] / 60;
                    }else{
                        $providers[$i]->day_activeHoursFormat = "N / A";
                        $providers[$i]->day_activeHours = 0;
                    }

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
                    $active = $active + $providers[$i]->day_activeHours;

                    $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();

                        //No. of Request completed in last 24 hours
                        $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                        if($request > 0){
                            $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                        }else{
                            $providers[$i]->completed_ratio = 0;
                        }
                        
                        $providers[$i]->received = $request;
                        $providers[$i]->completed = $completed_request;
                }
        
                for ($i=0; $i < count($providers); $i++) {
                    
                   
                    
                    // $amount_paid = DriveNowRaveTransaction::where('status', 1)->where('driver_id', $providers[$i]->id)->sum('amount');
                    $due_weeks = DriveNowTransaction::where('status', 0)->where('driver_id', $providers[$i]->id)->count();

                    $official_driver = OfficialDriver::where('driver_id', $providers[$i]->id)->where('status', '!=',1)->first();
                    
                    // if(!$official_driver){
                    //     $official_driver = new OfficialDriver;
                    //     $official_driver->driver_name = $providers[$i]->first_name ." ". $providers[$i]->last_name;
                    //     $official_driver->driver_contact = $providers[$i]->mobile;
                    //     $official_driver->contract_length = $providers[$i]->contract_length;
                    //     $official_driver->deposit = $providers[$i]->deposit;
                    //     $official_driver->contract_address = $providers[$i]->contract_address;
                    //     $official_driver->weekly_payment = $providers[$i]->weekly_payment;
                    //     $official_driver->vehicle_cost = $providers[$i]->vehicle_cost;
                    //     $official_driver->agreement_start_date = $providers[$i]->agreement_start_date;
                    //     $official_driver->agreed_on = $providers[$i]->agreed_on;
                    //     $official_driver->driver_id = $providers[$i]->id;
                    //     $official_driver->save();
                    // }
                    $today = date('Y-m-d');
                    $present = new \DateTime($today);
                    $agreement_start_date = new \DateTime($official_driver->agreement_start_date);

                    
                   
                    $user_referred = User::where('driver_referred', $providers[$i]->referal)->get();
                    
                   
                    $providers[$i]->user_referral = count($user_referred);
                    
                    $driver_referred = Provider::where('driver_referred', $providers[$i]->referal)->get();

                    $providers[$i]->driver_referral = count($driver_referred);

                   
                    $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->count();

                   
                    //Active Working Hours for the month by Driver
                    $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
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

                        $day_activeHours = DriverActivity::where('driver_id', $providers[$i]->id)
                                                    ->select([DB::raw("SUM(working_time) as activeHours")])
                                                    ->whereTime('start','>=',$day_start)
                                                    ->whereTime('end','<=',$day_end)
                                                    ->pluck('activeHours');

                        $night_activeHours = DriverActivity::where('driver_id', $providers[$i]->id)
                                                    ->select([DB::raw("SUM(working_time) as activeHours")])
                                                    ->whereTime('start','>=',$night_start)
                                                    ->whereTime('end','<=',$night_end)
                                
                                                    ->pluck('activeHours');
                                                    // ->get();
                         $breakHours = DriverActivity::where('driver_id',$providers[$i]->id)->whereTime('start','>=',$drivenow_start)
                                                ->whereTime('end','<=',$drivenow_end)
                                                ->select([DB::raw("SUM(break_time) as breakHours")])->pluck('breakHours');

                        if($breakHours[0] > 0){ 

                            if($breakHours[0] >= 60){
                                $breakHour = number_format(($breakHours[0] / 60), 2) ." Hrs";
                            }else{
                                $breakHour = number_format($breakHours[0], 2) . " mins";
                            }

                            $providers[$i]->breakHoursFormat = $breakHour;
                            $providers[$i]->breakHours = $breakHours[0] / 60;
                        }else{
                            $providers[$i]->breakHoursFormat = "N / A";
                            $providers[$i]->breakHours = 0;
                        }
                    

                        // dd($night_activeHours);
                        if($night_activeHours[0] > 0){ 

                            if($night_activeHours[0] >= 60){
                                $night_activeHour = number_format(($night_activeHours[0] / 60), 2) ." Hrs";
                            }else{
                                $night_activeHour = number_format($night_activeHours[0], 2) . " mins";
                            }

                            $providers[$i]->night_activeHoursFormat = $night_activeHour;
                            $providers[$i]->night_activeHours = $night_activeHours[0] / 60;
                        }else{
                            $providers[$i]->night_activeHoursFormat = "N / A";
                            $providers[$i]->night_activeHours = 0;
                        }

                        if($day_activeHours[0] > 0){ 

                            if($day_activeHours[0] >= 60){
                                $day_activeHour = number_format(($day_activeHours[0] / 60), 2) ." Hrs";
                            }else{
                                $day_activeHour = number_format($day_activeHours[0], 2) . " mins";
                            }

                            $providers[$i]->day_activeHoursFormat = $day_activeHour;
                            $providers[$i]->day_activeHours = $day_activeHours[0] / 60;
                        }else{
                            $providers[$i]->day_activeHoursFormat = "N / A";
                            $providers[$i]->day_activeHours = 0;
                        }

                    //Checking no Incoming Request for last 12 hours
                        $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->count();

                        //No. of Request completed in last 24 hours
                        $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->count();
                        if($request > 0){
                            $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                        }else{
                            $providers[$i]->completed_ratio = 0;
                        }
                        
                        $providers[$i]->received = $request;
                        $providers[$i]->completed = $completed_request;

                        if($providers[$i]->user_referral > 0){
                            $amb_user_referrals = $amb_user_referrals + $providers[$i]->user_referral;
                        }

                        if($providers[$i]->driver_referral > 0){
                             $amb_driver_referrals = $amb_driver_referrals +$providers[$i]->driver_referral;
                        }
                        

                        if($providers[$i]->completed_ratio > $ratio){
                            $ratio = $providers[$i]->completed_ratio;
                            $top_completed_ratio = $providers[$i];
                        }
                        $total_referrals = $providers[$i]->user_referral + $providers[$i]->driver_referral;
                        if($total_referrals > $ref){
                            $ref = $total_referrals;
                            $top_referrer = $providers[$i];
                        }
                } 
            }
            $document = Document::all()->count();
            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('admin.providers.official_drivers', compact('providers','page','document','online_drivers', 'total_drivers','ambassadors', 'online_ambassadors', 'amb_user_referrals', 'amb_driver_referrals', 'amb_user_ref_booked', 'amb_user_ref_completed', 'amb_driver_ref_rec', 'amb_driver_ref_completed','top_completed_ratio','top_referrer','ut_drivers'));
                
            } catch (Exception $e) {
                Log::info($e);
                return back()->with('flash_error', 'Something went wrong');
            }  
    }
    public function completion_od(Request $request)
    {
        try {

            $offline = $online = $riding = array();
            $top_completed_ratio = $top_referrer = $first = $second = array();
            $total_accepted = $total_rejected = $total_completed = $max1 = $max2 =  $total_missed = $total_received = $ratio = $ref = 0;

            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)
                        ->orderBy('updated_at', 'DESC');

            $ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)
                        ->orderBy('updated_at', 'DESC')->count();

            $online_ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)->where('availability',1)
                        ->orderBy('updated_at', 'DESC')->count();
           
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();

            
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $search = $request->search;
                $providers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)
                        ->where(function($q) use ($search) { $q->where('first_name','like', '%'.$search.'%')->orwhere('email','like', '%'.$search.'%')->orwhere('mobile','like', '%'.$search.'%');
                                    })->get();
            }else{
                if($request->has('filter')){
                    if($request->filter == 1){
                        $page = 'List of DriveNow Drivers to pay';
                        $providers = $AllProviders->where('bonus',0)->paginate(300);
                    }else if($request->filter == 2){
                        $page = 'List of Paid DriveNow Drivers';
                        $providers = $AllProviders->where('bonus',1)->paginate(300);
                    }
                }else{
                    $page = 'List of Top Completion Ratio DriveNow Drivers';
                    $providers = $AllProviders->paginate(300);
                }   
                
            }
            
            
                if($request->has('filter_date')){

                    $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));

                    $page = "Top Completion Drivers from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
                    $search ="";
                    $data = "4";
                    for ($i=0; $i < count($providers); $i++) {
                           
                            $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                           
                            //Active Working Hours for the month by Driver
                            $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->whereBetween('created_at',[date($dates[0]), date($dates[1])])->pluck('activeHours');
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

                            // //Calculate Driver Earning for the month 
                            // $requests = UserRequests::where('provider_id', $providers[$i]->id)->where('status', COMPLETED)->with('payment')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                            // $trip_earning = 0;
                            // if(count($requests) > 0) {
                            //     for($j=0; $j < count($requests); $j++) {
                            //         if($requests[$j]->payment){
                            //             $trip_earning += ($requests[$j]['payment']['driver_earning']);
                            //         }
                            //     }
                            // }
                            // $providers[$i]->earnings = $trip_earning;

                            // //Checking no Incoming Request for last 12 hours
                                $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                                $accepted = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('status',1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                                $rejected = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('status',2)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                                $missed = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('status',0)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();

                                //No. of Request completed in last 24 hours
                                $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                                if($request > 0){
                                    $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                                }else{
                                    $providers[$i]->completed_ratio = 0;
                                }
                                
                                $providers[$i]->received = $request;
                                $providers[$i]->accepted = $accepted;
                                $providers[$i]->rejected = $rejected;
                                $providers[$i]->missed = $missed;
                                $providers[$i]->completed = $completed_request;

                                $total_accepted = $total_accepted + $providers[$i]->accepted;
                                $total_received = $total_received + $providers[$i]->received;
                                $total_rejected = $total_rejected + $providers[$i]->rejected;
                                $total_missed = $total_missed + $providers[$i]->missed;
                                $total_completed = $total_completed + $providers[$i]->completed;

                                 if($providers[$i]->completed_ratio > $max1){
                                    $max2 = $max1;
                                    $second = $first;
                                    $max1 = $providers[$i]->completed_ratio;
                                    $first = $providers[$i];

                                }else if(($providers[$i]->completed_ratio < $max1) && ($providers[$i]->completed_ratio > $max2)){
                                    
                                    $max2 = $providers[$i]->completed_ratio;
                                    $second = $providers[$i];
                                } 

                                
                                
                            }
                            
                }else{  
                        
                        for ($i=0; $i < count($providers); $i++) {
                           
                            $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->count();
                           
                            //Active Working Hours for the month by Driver
                            $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
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

                            // //Checking no Incoming Request for last 12 hours
                                $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->count();
                                $accepted = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('status',1)->count();
                                $rejected = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('status',2)->count();
                                $missed = DriverRequestReceived::where('provider_id', $providers[$i]->id)->where('status',0)->count();

                                //No. of Request completed in last 24 hours
                                $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->count();
                                if($request > 0){
                                    $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                                }else{
                                    $providers[$i]->completed_ratio = 0;
                                }
                                
                                $providers[$i]->received = $request;
                                $providers[$i]->accepted = $accepted;
                                $providers[$i]->rejected = $rejected;
                                $providers[$i]->missed = $missed;
                                $providers[$i]->completed = $completed_request;

                                $total_accepted = $total_accepted + $providers[$i]->accepted;
                                $total_received = $total_received + $providers[$i]->received;
                                $total_rejected = $total_rejected + $providers[$i]->rejected;
                                $total_missed = $total_missed + $providers[$i]->missed;
                                $total_completed = $total_completed + $providers[$i]->completed;

                                
                                if($providers[$i]->completed_ratio > $max1){
                                    $max2 = $max1;
                                    $second = $first;
                                    $max1 = $providers[$i]->completed_ratio;
                                    $first = $providers[$i];

                                }else if(($providers[$i]->completed_ratio < $max1) && ($providers[$i]->completed_ratio > $max2)){
                                    
                                    $max2 = $providers[$i]->completed_ratio;
                                    $second = $providers[$i];
                                } 
                               
                            }
                    }   
            $document = Document::all()->count();

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('admin.providers.completion_od', compact('providers','page','ambassadors','online_ambassadors','document','approved_drivers','online_drivers', 'total_drivers','total_accepted','total_received', 'total_rejected', 'total_completed', 'first','second','top_referrer'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

    public function referrals_od(Request $request)
    {
        try {
            $top_completed_ratio = $top_referrer =  array();
                    $amb_user_referrals = $amb_driver_referrals = $amb_user_ref_booked = $amb_user_ref_completed = $amb_driver_ref_rec = $amb_driver_ref_completed = $ratio = $max1 = $max2 = $first = $second = 0;
            $offline = $online = $riding = array();
            $AllProviders = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)
                        ->orderBy('updated_at', 'DESC');
            $ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)
                        ->orderBy('updated_at', 'DESC')->count();
            $online_ambassadors = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')
                        ->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)->where('availability',1)
                        ->orderBy('updated_at', 'DESC')->count();
           
            $total_drivers = Provider::all()->where('archive', '!=', 1)->count();
            $offline_drivers = Provider::where('status','approved')->where('availability', 0)->where('archive', '!=', 1)->count();

            //User Referral Analytics
            $total_user_referred = User::where('driver_referred', '!=', '')->count();
            $total_user_referred_booked = User::where('driver_referred', '!=', '')->has('trips')->count();
            $total_user_referred_completed = User::where('driver_referred', '!=', '')->whereHas('trips', function($query) {
                            $query->where('status', 'COMPLETED');})->count();

            //
            $total_driver_referred = Provider::where('driver_referred', '!=', '')->count();
            $total_driver_referred_received = Provider::where('driver_referred', '!=', '')->has('trips')->count();
            $total_driver_referred_completed = Provider::where('driver_referred', '!=', '')->whereHas('trips', function($query) {
                            $query->where('status', 'COMPLETED');
                        })->count();
            
            
            if($request->has('search')){
                $page = 'Search result for "'.$request->search .'"';
                $search = $request->search;
                $providers = Provider::with('service','accepted','cancelled','fleetowner', 'marketer','approved')->where('archive', '!=', 1)->where('status','approved')->where('official_drivers', 1)
                        ->where(function($q) use ($search) { $q->where('first_name','like', '%'.$search.'%')->orwhere('email','like', '%'.$search.'%')->orwhere('mobile','like', '%'.$search.'%');
                                    })->get();
            }else{
                if($request->has('filter')){
                    if($request->filter == 1){
                        $page = 'List of DriveNow Drivers to pay';
                        $providers = $AllProviders->where('bonus',0)->paginate(300);
                    }else if($request->filter == 2){
                        $page = 'List of Paid DriveNow Drivers';
                        $providers = $AllProviders->where('bonus',1)->paginate(300);
                    }
                }else{
                    $page = 'List of Top Referrer DriveNow Drivers';
                    $providers = $AllProviders->paginate(300);
                }   
                
            }
            
            
                if($request->has('filter_date')){

                    $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));

                    $page = "Top Referrer from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
                    $search ="";
                    $data = "4";

                    for ($i=0; $i < count($providers); $i++) {
                            $user_requests = $last_user_requests = $driver_requests = $user_booked  = $driver_rec = 0;
                            $providers[$i]->user_requests = 0;
                            $providers[$i]->driver_requests = 0;
                            $providers[$i]->last_user_requests = 0;
                            $providers[$i]->user_booked = 0;
                            $providers[$i]->driver_req_rec = 0;
                            $user_referred = User::where('driver_referred', $providers[$i]->referal)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            $user_referred_booked = User::where('driver_referred', $providers[$i]->referal)->has('trips')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            $user_referred_completed = User::where('driver_referred', $providers[$i]->referal)->has('completed_requests')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();

                            $providers[$i]->user_requests =  $user_referred_completed;
                            $providers[$i]->user_booked =  $user_referred_booked;
                            $providers[$i]->user_referral = $user_referred;
                            
                            $driver_referred = Provider::where('driver_referred', $providers[$i]->referal)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            $driver_referred_booked = Provider::where('driver_referred', $providers[$i]->referal)->has('trips')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            $driver_referred_completed = Provider::where('driver_referred', $providers[$i]->referal)->has('completed_requests')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                            
                            $providers[$i]->driver_requests = $driver_referred_completed;
                            $providers[$i]->driver_req_rec = $driver_referred_booked;
                            $providers[$i]->driver_referral = $driver_referred;

                           
                            $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();

                           
                            //Active Working Hours for the month by Driver
                            $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->whereBetween('created_at',[date($dates[0]), date($dates[1])])->pluck('activeHours');
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

                          
                            //Checking no Incoming Request for last 12 hours
                                $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();

                                //No. of Request completed in last 24 hours
                                $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                                if($request > 0){
                                    $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                                }else{
                                    $providers[$i]->completed_ratio = 0;
                                }
                                
                                $providers[$i]->received = $request;
                                $providers[$i]->completed = $completed_request;

                                $total_referrals = $providers[$i]->user_referral + $providers[$i]->driver_referral;
                                $providers[$i]->total_referrals = $total_referrals;

                                if($providers[$i]->total_referrals > $max1){
                                    $max2 = $max1;
                                    $second = $first;
                                    $max1 = $providers[$i]->total_referrals;
                                    $first = $providers[$i];

                                }else if(($providers[$i]->total_referrals < $max1) && ($providers[$i]->total_referrals > $max2)){
                                    
                                    $max2 = $providers[$i]->total_referrals;
                                    $second = $providers[$i];
                                } 

                                 if($providers[$i]->user_referral > 0){
                                    $amb_user_referrals = $amb_user_referrals + $providers[$i]->user_referral;
                                }

                                if($providers[$i]->user_requests > 0){
                                     $amb_user_ref_completed = $amb_user_ref_completed + $providers[$i]->user_requests;
                                }

                                if($providers[$i]->user_booked > 0){
                                     $amb_user_ref_booked = $amb_user_ref_booked + $providers[$i]->user_booked;
                                }

                                if($providers[$i]->driver_referral > 0){
                                     $amb_driver_referrals = $amb_driver_referrals +$providers[$i]->driver_referral;
                                }
                                

                                if($providers[$i]->driver_req_rec > 0){
                                    $amb_driver_ref_rec = $amb_driver_ref_rec + $providers[$i]->driver_req_rec;
                                }
                                if($providers[$i]->driver_requests > 0){
                                    $amb_driver_ref_completed = $amb_driver_ref_completed + $providers[$i]->driver_requests;
                                }
                                
                        }
                }else{  
                        
                        for ($i=0; $i < count($providers); $i++) {
                            $user_requests = $last_user_requests = $driver_requests = $user_booked  = $driver_rec = 0;
                            $providers[$i]->user_requests = 0;
                            $providers[$i]->driver_requests = 0;
                            $providers[$i]->last_user_requests = 0;
                            $providers[$i]->user_booked = 0;
                            $providers[$i]->driver_req_rec = 0;
                            $user_referred = User::where('driver_referred', $providers[$i]->referal)->count();
                            $user_referred_booked = User::where('driver_referred', $providers[$i]->referal)->has('trips')->count();
                            $user_referred_completed = User::where('driver_referred', $providers[$i]->referal)->has('completed_requests')->count();

                            $providers[$i]->user_requests =  $user_referred_completed;
                            $providers[$i]->user_booked =  $user_referred_booked;
                            $providers[$i]->user_referral = $user_referred;
                            
                            $driver_referred = Provider::where('driver_referred', $providers[$i]->referal)->count();
                            $driver_referred_booked = Provider::where('driver_referred', $providers[$i]->referal)->has('trips')->count();
                            $driver_referred_completed = Provider::where('driver_referred', $providers[$i]->referal)->has('completed_requests')->count();
                            
                            $providers[$i]->driver_requests = $driver_referred_completed;
                            $providers[$i]->driver_req_rec = $driver_referred_booked;
                            $providers[$i]->driver_referral = $driver_referred;

                           
                            $providers[$i]->online_credit = OnlineCredit::where('driver_id', $providers[$i]->id)->where('status', 0)->count();

                           
                            //Active Working Hours for the month by Driver
                            $activeHours = DriverActivity::where('driver_id', $providers[$i]->id)->select([DB::raw("SUM(working_time) as activeHours")])->pluck('activeHours');
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

                          
                            //Checking no Incoming Request for last 12 hours
                                $request = DriverRequestReceived::where('provider_id', $providers[$i]->id)->count();

                                //No. of Request completed in last 24 hours
                                $completed_request = UserRequests::where('provider_id', $providers[$i]->id)->where('status', 'COMPLETED')->count();
                                if($request > 0){
                                    $providers[$i]->completed_ratio = round((( $completed_request / $request ) * 100));
                                }else{
                                    $providers[$i]->completed_ratio = 0;
                                }
                                
                                $providers[$i]->received = $request;
                                $providers[$i]->completed = $completed_request;

                                $total_referrals = $providers[$i]->user_referral + $providers[$i]->driver_referral;
                                $providers[$i]->total_referrals = $total_referrals;

                                if($providers[$i]->total_referrals > $max1){
                                    $max2 = $max1;
                                    $second = $first;
                                    $max1 = $providers[$i]->total_referrals;
                                    $first = $providers[$i];

                                }else if(($providers[$i]->total_referrals < $max1) && ($providers[$i]->total_referrals > $max2)){
                                    
                                    $max2 = $providers[$i]->total_referrals;
                                    $second = $providers[$i];
                                } 

                                 if($providers[$i]->user_referral > 0){
                                    $amb_user_referrals = $amb_user_referrals + $providers[$i]->user_referral;
                                }

                                if($providers[$i]->user_requests > 0){
                                     $amb_user_ref_completed = $amb_user_ref_completed + $providers[$i]->user_requests;
                                }

                                if($providers[$i]->user_booked > 0){
                                     $amb_user_ref_booked = $amb_user_ref_booked + $providers[$i]->user_booked;
                                }

                                if($providers[$i]->driver_referral > 0){
                                     $amb_driver_referrals = $amb_driver_referrals +$providers[$i]->driver_referral;
                                }
                                

                                if($providers[$i]->driver_req_rec > 0){
                                    $amb_driver_ref_rec = $amb_driver_ref_rec + $providers[$i]->driver_req_rec;
                                }
                                if($providers[$i]->driver_requests > 0){
                                    $amb_driver_ref_completed = $amb_driver_ref_completed + $providers[$i]->driver_requests;
                                }
                                
                        }
                    }
                   
                   
            $document = Document::all()->count();

            $recent_documents = ProviderDocument::has('provider')->where('status', 'ASSESSING')->orderBy('updated_at','desc')->with('provider','document','provider.fleetowner')->groupby('provider_id')->get();
            
            return view('admin.providers.referrals_od', compact('providers','page','document','ambassadors','total_drivers', 'online_ambassadors', 'user_requests', 'total_user_referred','total_user_referred_booked','total_user_referred_completed','total_driver_referred','total_driver_referred_completed','total_driver_referred_received','amb_user_referrals', 'amb_driver_referrals', 'amb_user_ref_booked', 'amb_user_ref_completed', 'amb_driver_ref_rec', 'amb_driver_ref_completed','first', 'second'));
                
            } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Something went wrong');
        }  
    }

     public function add_official_driver(Request $request)
    {
        try {
                $driver = Provider::findOrFail($request->driver_id);
                $driver->official_drivers = 1;
                $driver->save();
                $official_driver = OfficialDriver::where('driver_id', $request->driver_id)->where('status','!=',1)->first();
                if($official_driver){
                    return back()->with('flash_success', "DriveNow Driver can't be added with active contract");
                }  
                    $official_driver = new OfficialDriver; 
                    $official_driver->driver_name = $driver->first_name ." ". $driver->last_name;
                    $official_driver->driver_contact = $driver->mobile;
                    $official_driver->imei_number = $request->imei_number;
                    $official_driver->vehicle_number = $request->vehicle_no;
                    $official_driver->contract_length = $request->contract_length;
                    $official_driver->deposit = $request->deposit;
                    $official_driver->contract_address = $request->contract_address;
                    $official_driver->weekly_payment = $request->weekly_payment;
                    $official_driver->vehicle_cost = $request->vehicle_cost;
                    if($request->initial_amount > 0){
                        $official_driver->amount_due = $official_driver->amount_due + $request->initial_amount;
                    }
                    $official_driver->initial_amount = $request->initial_amount;

                    //Create DriveNow Transaction for initial amount paid by Driver
                    // $code = rand(100000, 999999);
                    // $name = substr($driver->first_name, 0, 2);
                    // $req_id = $name.$code;
                    // $trans_id = "Drivenow_IA".$code;

                    // $rave_transactions = new DriveNowRaveTransaction;
                    // $rave_transactions->driver_id = $driver->id;
                    // $rave_transactions->official_id = $official_driver->id;
                    // $rave_transactions->reference_id = $req_id;
                    // $rave_transactions->slp_ref_id = $trans_id;
                    // $rave_transactions->slp_resp = $trans_id;
                    // $rave_transactions->network = "Eganow";
                    // $rave_transactions->amount = number_format($request->initial_amount,2);
                    // $rave_transactions->status = 1;
                    // $rave_transactions->save();
                    $today = date('Y-m-d');
                    $present = new \DateTime($today);
                    $next_due = date('Y-m-d', strtotime('next monday', strtotime($today)));
                    $official_driver->next_due = $next_due;

                    $official_driver->agreement_start_date = $request->agreement_start_date;
                    $official_driver->driver_id = $request->driver_id;
                    $official_driver->status = 0;
                    $official_driver->agreed = 0;
                    $official_driver->save();

                    //Update Driver Deposit Table status

                    $deposit = DriverDeposit::where('driver_id', $driver->id)->first();
                    if($deposit){
                        $deposit->status = 2;
                        $deposit->save();
                    }
                    

                if($request->has('vehicle_id')){
                    $official_driver->vehicle_id = $request->vehicle_id;
                    $vehicle = DriveNowVehicle::where('id', $request->vehicle_id)->first();
                    $vehicle->driver_id = $official_driver->driver_id;
                    $vehicle->official_id = $official_driver->id;
                    $vehicle->allocated_date = Carbon::now();
                    $vehicle->status = 5;
                    $vehicle->save();

                    $official_driver->imei_number = $vehicle->imei;
                    $official_driver->vehicle_number = $vehicle->reg_no;
                    $official_driver->vehicle_image = $vehicle->car_picture;
                    $official_driver->vehicle_make = $vehicle->make;
                    $official_driver->vehicle_model = $vehicle->model;
                    $official_driver->vehicle_year = $vehicle->year;
                    $official_driver->supplier_id = $vehicle->fleet_id;
                    $official_driver->save();
                }
                $contract = DriveNowContracts::where('id', $request->contract_id)->first();
                $driver_contract = new DriverContracts;

                $driver_contract->driver_id = $driver->id;
                $driver_contract->official_id = $official_driver->id;
                $driver_contract->contract_id = $contract->id;
                $driver_contract->agreement_start_date = $request->agreement_start_date;
                $driver_contract->save();

                //Update the new contract id to official_driver table
                $official_driver->contract_id = $driver_contract->id;

                                
                $official_driver->save();

                $driver->agreed = 0;
                $driver->agreement_start_date = $request->agreement_start_date;
                $driver->save();

                return back()->with('flash_success', "Driver added to DriveNow Drivers");
           
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_update(Request $request)
    {
        try {

            $official_driver = OfficialDriver::where('driver_id', $request->driver_id)->where('status', '!=', 1)->first();


            if($official_driver){
                $official_driver->driver_name = $request->driver_name;
                $official_driver->driver_contact = $request->driver_contact;

                if ($request->hasFile('vehicle_image')) {

                    $name = $official_driver->driver_id."-profile-".str_replace(' ','_',Carbon::now());
                    $baseurl = 'replace with actual asset url';                    
                    $contents = file_get_contents($request->vehicle_image);
                    $path = Storage::disk('s3')->put('driver_profile/'.$name, $contents);
                    $s3_url = $baseurl.'/'.$name;
                    $official_driver->vehicle_image = $s3_url;            
                }
                
                
                $official_driver->contract_length = $request->contract_length;
                // $official_driver->balance_weeks = $official_driver->balance_weeks + $request->balance_weeks;
                $official_driver->weekly_payment = $request->weekly_payment;
                // $amount_due = ($request->balance_weeks * $official_driver->weekly_payment) - $request->amount_paid;
                // $official_driver->amount_paid = $request->amount_paid;
                // $official_driver->amount_due = $official_driver->amount_due + $amount_due;
                $official_driver->deposit = $request->deposit;
                $official_driver->contract_address = $request->contract_address;
               
                $official_driver->vehicle_cost = $request->vehicle_cost;
                $official_driver->initial_amount = $request->initial_amount;
                $official_driver->agreement_start_date = $request->agreement_start_date;
                $official_driver->driver_id = $request->driver_id;
                $official_driver->updated_on = Carbon::now();;
                $official_driver->updated_by = Auth::guard('admin')->user()->id; 
                $official_driver->status = 0;
                $official_driver->save();

                return back()->with('flash_success', "Agreement details updated!");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver has not been assigned as DriveNow Driver!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_agreement($id){
        try {
            $driver = Provider::findOrFail($id);
            $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->with('admin','vehicle')->first();
            $vehicles = DriveNowVehicle::whereNotIn('status',[5,6,0])->get();
            $contracts = DriveNowContracts::where('status',1)->get();
                    
            return view('admin.providers.drive-own', compact('driver', 'official_driver','vehicles','contracts'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }

    }

    public function remove_official_driver($id)
    {
        try {
            $Provider = Provider::findOrFail($id);
            if($Provider) {
                $Provider->update(['official_drivers' => 0]);
                $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
                $official_driver->status = 1;
                $official_driver->terminated_on = Carbon::now();
                $official_driver->save();
                $vehicle = DriveNowVehicle::where('id',$official_driver->vehicle_id)->first();
                $vehicle->status = 4;
                $vehicle->save();
                return back()->with('flash_success', "Driver Removed from DriveNow Drivers");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver can not be removed from DriveNow Driver!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function engine_control(Request $request,  $status, $id){
        try {
            $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();
            $official_driver->engine_control = $status;
            $official_driver->save();
            return back()->with('flash_success', "Car Engine Control Updated!");
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }

    }

    public function engine_restore(Request $request, $id)
    {
        try{
            $driver = Provider::where('id', $id)->first();
                $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();

                // if($official_driver->engine_control == 1 && $official_driver->imei_number != ''){
                if($official_driver->vehicle->imei != ''){

                    $tro_access_token = Setting::get('tro_access_token','');
                    if($tro_access_token == ''){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with actual protract account name";
                        $password = "replac with protrack password";
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
                            $account = "replace with actual protract account name";
                            $password = "replac with protrack password";
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

                        (new SendPushNotification)->DriverEngineUpdate($id,$message);

                        $official_driver->engine_status = 0;
                        
                        if($request->for == 1){
                            $official_driver->engine_restore_reason = 'Payment Due';
                        }else{
                            $official_driver->engine_restore_reason = 'Offline';
                        }
                       
                        $official_driver->engine_restore_by = Auth::guard('admin')->user()->id;
                        $official_driver->engine_restore_on = Carbon::now();
                        $official_driver->save();


                        //Send SMS Notification
                            $content = "Your vehicle has been reactivated. Contact Eganow driver support team if you have any issues.";
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

                            // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                            // $headers = ['Content-Type' => 'application/json'];
                            
                            // $res = $client->get($url, ['headers' => $headers]);

                            // $code = (string)$res->getBody();
                            // $codeT = str_replace("\n","",$code);

                        return back()->with('flash_success', "Engine Control Restored.");
                        
                    }
                }

        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function turn_off_engine(Request $request, $id)
    {
        try{
                $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();
                $driver = Provider::where('id', $id)->first();

                if($official_driver->vehicle->imei != ''){
                    $tro_access_token = Setting::get('tro_access_token','');
                    if($tro_access_token == ''){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with actual protract account name";
                        $password = "replac with protrack password";
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
                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->vehicle->imei;

                        $status_json = curl($status_url);

                        $status_details = json_decode($status_json, TRUE);
                        if($status_details['code']== '10012'){
                            $time = Carbon::now()->timestamp;
                            $account = "replace with actual protract account name";
                            $password = "replac with protrack password";
                            $signature = md5(md5($password).$time);

                            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                            $token_json = curl($token_url);

                            $token_details = json_decode($token_json, TRUE);

                            $tro_access_token = $token_details['record']['access_token'];
                            Setting::set('tro_access_token', $tro_access_token);
                            Setting::save();
                            Log::info("Tro Access Token Expired Called");

                            $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->vehicle->imei;

                            $status_json = curl($status_url);

                            $status_details = json_decode($status_json, TRUE);
                            
                        }
                        $car_speed = $status_details['record'][0]['speed'];
                        $offline_status = $status_details['record'][0]['datastatus'];

                        if($car_speed > 3 ){
                             return back()->with('flash_error', "Car is speeding up, Unable turn off the engine.");
                        }
                        else if($offline_status != 2){
                            $vehicle = DriveNowVehicle::where('imei',$status_details['record'][0]['imei'])->first();
                            if($vehicle->sim !=''){
                                $mobile = $vehicle->sim;
                                // if($mobile[0] == 0){
                                //     $receiver = $mobile;
                                // }else{
                                //     $receiver = "0".$mobile; 
                                // }
                                $content = "*22*2#";

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
                                Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                            }
                            return back()->with('flash_error', "Tracker Device is offline, Block SMS Sent to ".$vehicle->sim);
                        }else{
                            Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $id ." )");
                            //Turn off the Engine
                            $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->vehicle->imei."&command=RELAY,1";

                            $json = curl($url);

                            $details = json_decode($json, TRUE);

                            $vehicle = DriveNowVehicle::where('imei',$status_details['record'][0]['imei'])->first();
                            if($vehicle->sim !=''){
                                $mobile = $vehicle->sim;
                                // if($mobile[0] == 0){
                                //     $receiver = $mobile;
                                // }else{
                                //     $receiver = "0".$mobile; 
                                // }
                                $content = "*22*2#";

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
                                Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                            }
                            
                            $blocked_history = new DriveNowBlockedHistory;
                            if($request->for = 1){
                                $official_driver->engine_off_reason = 'Payment Due';
                                $blocked_history->amount_due = $official_driver->amount_due;
                            }else{
                                $official_driver->engine_off_reason = 'Offline';
                            }
                           
                            $official_driver->engine_off_by = Auth::guard('admin')->user()->id;
                            $official_driver->engine_off_on = Carbon::now();
                            
                            $official_driver->engine_status = 1;
                            $official_driver->save();

                            $blocked_history->official_id = $official_driver->id;
                            $blocked_history->driver_id = $official_driver->driver_id;
                            $blocked_history->engine_off_by = Auth::guard('admin')->user()->id;
                            $blocked_history->engine_off_on = Carbon::now();

                            $blocked_history->engine_off_reason = $official_driver->engine_off_reason;
                            $blocked_history->save();

                            return back()->with('flash_success', "Car engine turned off.");
                            } 
                    }
                }

        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_transactions(Request $request){

        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            // $transactions = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver')->whereHas('official_driver')->where('status', 1)->orderBy('created_at', 'desc')->get();

            $transactions = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('created_at', '>', Carbon::now()->subDays(30)->endOfDay())
                        ->whereHas('official_driver')->where('status', 1)->where('add_charge','!=',0)->orderBy('updated_at', 'desc')->get();

            $drivenow_extra = DriveNowExtraPayment::whereBetween('created_at',[date($dates[0]), date($dates[1])])->distinct('reason')->select('reason')->get();

            $total_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Payments transactions from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else{

            $transactions = DriveNowRaveTransaction::with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('created_at', '>', Carbon::now()->subDays(30)->endOfDay())
                        ->whereHas('official_driver')->where('status', 1)->where('add_charge','!=',0)->orderBy('updated_at', 'desc')->get();
            $drivenow_extra = DriveNowExtraPayment::distinct('reason')->select('reason')->get();

            $total_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::where('status',1)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Due Payment transactions";
        }
        $total_driver = OfficialDriver::where('status', '!=', 1)->count();
        $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();
        // dd($transactions);
                // $driver_due = $total_driver - $driver_paid;
        return view('admin.providers.drivenow_transactions', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','total_add_charge','drivenow_extra'));
        // return view('admin.providers.drivenow_transactions', compact('transactions'));
    }

    public function drivenow_driver_transactions(Request $request, $id){
        $official_driver = OfficialDriver::where('id',$id)->first();
        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            // $transactions = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver')->whereHas('official_driver')->where('status', 1)->orderBy('created_at', 'desc')->get();

            $transactions = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('status', 1)->orderBy('updated_at', 'desc')->where('official_id',$id)->get();

            $drivenow_extra = DriveNowExtraPayment::whereBetween('created_at',[date($dates[0]), date($dates[1])])->distinct('reason')->select('reason')->where('official_id',$id)->get();

            $total_due = OfficialDriver::where('status', '!=', 1)->where('id',$id)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->where('official_id',$id)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->where('official_id',$id)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->where('official_id',$id)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->where('official_id',$id)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->where('official_id',$id)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('official_id',$id)->where('status','!=',3)->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereRaw('amount_due > weekly_payment')->where('id',$id)->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->where('id',$id)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->where('official_id',$id)->count('driver_id');
                

            $page = "DriveNow Payments transactions of ".$official_driver->driver_name." from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else{

            $transactions = DriveNowRaveTransaction::with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('status', 1)->orderBy('updated_at', 'desc')->where('official_id',$id)->get();

            $drivenow_extra = DriveNowExtraPayment::distinct('reason')->select('reason')->where('official_id',$id)->get();
            $official_driver = OfficialDriver::where('id',$id)->first();
            $total_due = OfficialDriver::where('status', '!=', 1)->where('id',$id)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->where('official_id',$id)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::where('status',1)->where('official_id',$id)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->where('official_id',$id)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->where('official_id',$id)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->where('official_id',$id)->count();
            $total_tran =  DriveNowRaveTransaction::where('official_id',$id)->where('status','!=',3)->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->where('id',$id)->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('amount_due', '>', 0)->where('id',$id)->count();
                }
                $driver_paid = DriveNowRaveTransaction::where('status',1)->where('official_id',$id)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Due Payment transactions for ".$official_driver->driver_name;
        }
        $total_driver = OfficialDriver::where('status', '!=', 1)->count();
        $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();
        // dd($transactions);
                // $driver_due = $total_driver - $driver_paid;
        return view('admin.providers.drivenow_driver_transaction', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','total_add_charge','drivenow_extra','official_driver'));
        // return view('admin.providers.drivenow_transactions', compact('transactions'));
    }

    public function drivenow_due(){

        $credit_pending_transactions = DriveNowRaveTransaction::where('status', 2)->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                 $CP = Helper::ConfirmPayment($credit_pending_transaction->id);
            }
        $transactions = DriveNowRaveTransaction::with('provider', 'drivenow_transaction')->whereIn('status', [0,2])->orderBy('updated_at', 'desc')->get();
        // dd($transactions);
        return view('admin.providers.drivenow_due', compact('transactions'));
    }

    public function drivenow_approve($id){

        $transaction = DriveNowRaveTransaction::where('id', $id)->first();
        $transaction->status = 3;
        $transaction->save();
        $official_driver = OfficialDriver::where('id', $transaction->official_id)->first();
        $official_driver->amount_due = $official_driver->amount_due + $transaction->amount;
        $official_driver->amount_paid = $official_driver->amount_paid - $transaction->amount;
        $official_driver->save();
        
        return back()->with('flash_success', 'Transaction Reversed');
    }

    public function drivenow_make_paid($id){

        $transaction = DriveNowRaveTransaction::where('id', $id)->first();
        $transaction->status = 1;
        $transaction->save();
        $official_driver = OfficialDriver::where('id', $transaction->official_id)->first();
        $official_driver->amount_due = $official_driver->amount_due - $transaction->amount;
        $official_driver->amount_paid = $official_driver->amount_paid + $transaction->amount;
        $official_driver->save();
        
        return back()->with('flash_success', 'Transaction marked as paid');
    }

     public function drivenow_due_approve($id){

        $transaction = DriveNowTransaction::where('id', $id)->first();
        $transaction->status = 1;
        $transaction->save();
        return back()->with('flash_success', 'Transaction Confirmed');
    }

    public function drivenow_due_payment(Request $request){
        try{
            $id = $request->route('id');

            Log::info(Carbon::now());
        $credit_pending_transactions = DriveNowRaveTransaction::where('status', 2)->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                                $CP = Helper::ConfirmPayment($credit_pending_transaction->id);    
            }

        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->orderBy('updated_at','desc')->with('transactions')->get();
            $total_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->sum('amount_due');

            $overall_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');

            $total_add_charge = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('add_charge');

            $total_fees= DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('fees');

            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Due Payments from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else if(request()->has('f')){
            if($request->f == "blocked"){
                $transactions = OfficialDriver::where('engine_status', '!=', 0)->where('daily_drivenow','!=', 1)->where('status', '!=', 1)->orderBy('updated_at','desc')->get();
                $total_due = OfficialDriver::where('engine_status', '!=', 0)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                $overall_due = OfficialDriver::where('engine_status', '!=', 0)->where('status', '!=', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
                $total_add_charge = DriveNowRaveTransaction::where('status',1)->sum('add_charge');
                $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
                $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::get()->count();
                    if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->where('amount_due', '>', 0)->count();
                    }
                    $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->where('amount_due', '>', 0)->count();
                    $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                    $page = "DriveNow Blocked Drivers";
            }else if($request->f == "due"){
                if(date('D') != 'Tue'){
                    $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->orderBy('updated_at','desc')->get();
                }else{
                    $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->orderBy('updated_at','desc')->get();
                }
                
                $total_due = OfficialDriver::where('amount_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                $overall_due = OfficialDriver::where('amount_due', '>', 0)->where('status', '!=', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
                $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
                $total_add_charge = DriveNowRaveTransaction::where('status',1)->sum('add_charge');
                $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::get()->count();
                    if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    }
                    $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                    $page = "DriveNow Drivers with Due";
            }
        }else{
                    
            $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->with('vehicle','transactions')->orderBy('updated_at','desc')->get();

            $official_drivers = OfficialDriver::where('status', '!=', 1)->with('vehicle')->orderBy('updated_at','desc')->get();
            foreach ($official_drivers as $driver) {
                    // Log::info($driver);
                   $driver->imei_number = $driver->vehicle->imei;
                   $driver->save();
                }
            for ($o=0; $o < count($transactions); $o++) {
            
                $OldDriverOff = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', '<', Carbon::today())->update(['status' => 1]);
                $cur_day_off = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', Carbon::today())->where('status',0)->first();


                if(!$cur_day_off){
                    $transactions[$o]->day_off = 0;
                    $transactions[$o]->save();
                }
                $transactions[$o]->txn = DriveNowTransaction::where('driver_id',$transactions[$o]->driver_id)->whereNotNull('due_date')->count();
                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('add_charge');
                $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);
                $contract_date = $transactions[$o]->agreement_start_date;
                $date = Carbon::now();

                $completed_weeks = $date->diffInWeeks($contract_date);
                if($completed_weeks > $transactions[$o]->contract_length){
                    $completed_weeks = $transactions[$o]->contract_length;
                }
                $transactions[$o]->completed_weeks = $completed_weeks;


            }
            $total_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
            $overall_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::where('status',1)->sum('add_charge');
            $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    $today = date('Y-m-d');
                    $present = new \DateTime($today);
                    $next_due = date('Y-m-d', strtotime('next monday', strtotime($today)));

                    $today = date('Y-m-d');
                    $present = new \DateTime($today);

                    $additional_charge = 0;
                    if(date('D') == 'Wed'){
                        for ($i=0; $i < count($official_drivers); $i++) { 
                            $official_driver = OfficialDriver::where('driver_id', $official_drivers[$i]->driver_id)->where('status', '!=', 1)->first();

                            $txn_amt = DriveNowRaveTransaction::where('driver_id',$official_driver->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
                            $txn_adc = DriveNowRaveTransaction::where('driver_id',$official_driver->driver_id)->where('status',1)->sum('add_charge');

                            $vehicle_paid = $official_driver->pre_balance + ($txn_amt - $txn_adc);

                            if($vehicle_paid < $official_driver->vehicle_cost){
                                $agreement_start_date = new \DateTime($official_driver->agreement_start_date);
                                $additional_charge = 0;
                                if($present > $agreement_start_date){
                                    $drivenow_transaction = DriveNowTransaction::where('due_date',$next_due)->where('driver_id', $official_drivers[$i]->driver_id)->first();
                                    $extras = 0;
                                    if($official_driver->extra_pay > 0){
                                        $extras = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('due');
                                    }else{
                                        $official_driver->extra_pay = 0;
                                        DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1]);
                                    }
                                    
                                    $tot_due = $official_driver->weekly_payment;
                                    
                                    if(!$drivenow_transaction){
                                        $drivenow_transaction = new DriveNowTransaction;
                                        $drivenow_transaction->due_before = $official_driver->amount_due;
                                        $drivenow_transaction->balance_before = $official_driver->amount_paid;
                                        $official_driver->next_due = $next_due;
                                        $official_driver->break = 0;
                                        $official_driver->amount_due = $official_driver->amount_due + $official_driver->weekly_payment;

                                        $official_driver->balance_weeks = $official_driver->balance_weeks + 1;      
                                        $official_driver->amount_due_add = $official_driver->amount_due_add + $extras;    
                                        $official_driver->save();
                                    }

                                    $drivenow_transaction->driver_id = $official_drivers[$i]->driver_id;
                                    $drivenow_transaction->contract_id = $official_driver->id;
                                    $drivenow_transaction->amount = $official_driver->weekly_payment + $extras;
                                    $drivenow_transaction->due = $official_driver->weekly_payment;
                                    $drivenow_transaction->add_charge = $extras;
                                    $drivenow_transaction->due_date = $next_due;
                                    $drivenow_transaction->status = 0;
                                    $drivenow_transaction->save();


                                    $drivenow_due_transaction = DriveNowTransaction::where('due_date', '<', $next_due)->whereNotNull('due_date')->where('driver_id', $official_driver->driver_id)->where('status',0)->update(['status' => 3]);
                                } 
                            }
                            
                            
                            // else if($present < $agreement_start_date){
                                
                            //     if($official_driver->initial_amount > 0){

                            //         $drivenow_transaction = DriveNowTransaction::where('due_date',$next_due)->where('driver_id', $official_drivers[$i]->driver_id)->first();
                                
                                
                            //         if(!$drivenow_transaction){
                            //             $drivenow_transaction = new DriveNowTransaction;

                            //             $official_driver->next_due = $next_due;
                            //             $official_driver->break = 0;
                            //             $official_driver->amount_due = $official_driver->amount_due + $official_driver->initial_amount;
                            //             $official_driver->save();
                            //             $official_driver->balance_weeks = $official_driver->balance_weeks + 1;          
                            //             $official_driver->save();

                            //         }
                            //             $drivenow_transaction->driver_id = $official_drivers[$i]->driver_id;
                            //             $drivenow_transaction->contract_id = $official_driver->id;
                            //             $drivenow_transaction->amount = $official_driver->initial_amount;
                            //             $drivenow_transaction->due_date = $official_driver->next_due;
                            //             $drivenow_transaction->status = 0;
                            //             $drivenow_transaction->save();
                                    
                            //     }

                            //     $drivenow_due_transaction = DriveNowTransaction::where('due_date', '<', $next_due)->where('driver_id', $official_driver->driver_id)->where('status',0)->update(['status' => 3]);
                                
                            // }
                        }
                    }
                    

                    
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Due Payments";
        }

            $vehicles = DriveNowVehicle::where('status', 4)->get();
            $total_driver = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->count();
            $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('daily_drivenow','!=', 1)->where('status', '!=', 1)->count();
            $total_blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();
            $contracts = DriveNowContracts::where('status', 1)->get();
            Log::info(Carbon::now());

            $provider = Provider::with('trips')->where('id', $id)->first();
            $provider = Provider::where('id',$id)->first();
            $driverComments = $driverComments = DriverComments::where('driver_id', $id)->with('provider','moderator')->orderBy('created_at', 'desc')->get();
            for($i = 0; $i < count($driverComments); $i++){
                $driverComments[$i]->posts = DriverComments::where('marketer_id',$driverComments[$i]->moderator->id)->count();
            }
            $moderator_posts = DriverComments::where('marketer_id', Auth::guard('admin')->user()->id)->count();

        return view('admin.providers.drivenow_due_payment', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','vehicles','driver_dues','contracts','total_fees','overall_due','total_blocked_drivers','total_driver','total_add_charge', 'provider', 'driverComments', 'moderator_posts'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function additional_charges($id){
        $additional_charges = DriveNowExtraPayment::where('official_id', $id)->where('status','!=',1)->get();
        return $additional_charges;
    }

    public function drivenow_make_payment(Request $request){
        try{
                $Provider = Provider::where('id',$request->driver_id)->first();
                
                $official_driver = OfficialDriver::where('driver_id', $request->driver_id)->with('vehicle')->where('status', '!=', 1)->first();
                $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');
                $vehicle_repayment_before = $total_repayment - $add_repayment;

                $code = rand(100000, 999999);
                $name = substr($Provider->first_name, 0, 2);
                $req_id = $name.$code;
                $trans_id = "DriveNow".$code;
                $rave_transactions = new DriveNowRaveTransaction;
                $rave_transactions->driver_id = $Provider->id;
                $rave_transactions->official_id = $official_driver->id;
                $rave_transactions->reference_id = $req_id;
                $rave_transactions->slp_ref_id = $trans_id;
                $rave_transactions->slp_resp = $trans_id;
                $rave_transactions->network = "Eganow";
                $rave_transactions->amount = $request->amount;
                $rave_transactions->status = 1;
                $rave_transactions->comments = $request->comments;
                $rave_transactions->due_before = $official_driver->amount_due;
                $rave_transactions->total_before = $vehicle_repayment_before;
                $rave_transactions->save();
                $vehicle_ded = $request->amount;
                if($request->type == 'vehicle_full'){

                    if($official_driver->vehicle->fleet->management_fee != ''){
                        $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                        $management_fee = round(($official_driver->vehicle->fleet->management_fee / $total_fee ) * $request->amount);
                        $weekly = round(($official_driver->vehicle->fleet->weekly / $total_fee ) * $request->amount);
                        $company_share = round(($official_driver->vehicle->fleet->company_share / $total_fee ) * $request->amount);
                        $road_worthy_fee = round(($official_driver->vehicle->fleet->road_worthy_fee / $total_fee ) * $request->amount);
                        $insurance_fee = round(($official_driver->vehicle->fleet->insurance_fee / $total_fee ) * $request->amount);
                        $maintenance_fee = round(($official_driver->vehicle->fleet->maintenance_fee / $total_fee ) * $request->amount);
                        
                        $rave_transactions->management_fee = $management_fee ;
                        $rave_transactions->weekly = $weekly;
                        $rave_transactions->company_share = $company_share;
                        $rave_transactions->road_worthy_fee = $road_worthy_fee;
                        $rave_transactions->insurance_fee = $insurance_fee;
                        $rave_transactions->maintenance_fee = $maintenance_fee;
                        
                        $rave_transactions->save();
                    }

                    if($official_driver->daily_DriveNow == 1){
                        $official_driver->daily_due = $official_driver->daily_due - $vehicle_ded;
                    }
                    $official_driver->amount_due = ($official_driver->amount_due - $vehicle_ded);
                    $official_driver->amount_paid = ($official_driver->amount_paid + $vehicle_ded);
                    
                    $official_driver->save();

                    $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                    $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');
                    $vehicle_repayment_after = $total_repayment - $add_repayment;

                    $rave_transactions->total_after = $vehicle_repayment_after;
                    $rave_transactions->due = $vehicle_ded;
                    $rave_transactions->due_after = $official_driver->amount_due;
                    $rave_transactions->save();
                }
                else{
                    if($request->type == "split"){
                        $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                        $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');
                        $vehicle_repayment_before = $total_repayment - $add_repayment;
                        $extra_due = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1);  

                        $daily_extra = $extra_due->sum('daily_due');
                        $weekly_extra = $extra_due->sum('due');
                    
                        $extra_ded = 0;
                        if($official_driver->extra_pay > 0){

                            //Calculating Percentage of payment to deduct from due
                            if($official_driver->daily_DriveNow == 1 && $official_driver->daily_due > 0){
                                $tot_due = $official_driver->daily_due + $official_driver->daily_due_add;
                                $vehicle_per = $official_driver->daily_due / $tot_due;
                                $extra_per = $official_driver->daily_due_add / $tot_due;
                            }
                            if($official_driver->daily_DriveNow == 1 && $official_driver->daily_due <= 0){
                                $tot_due = $official_driver->daily_payment + $daily_extra;
                                $vehicle_per = $official_driver->daily_payment / $tot_due;
                                $extra_per = $daily_extra / $tot_due;
                            }
                            if($official_driver->amount_due > 0){
                                $tot_due = $official_driver->amount_due + $official_driver->amount_due_add;
                                $vehicle_per = $official_driver->amount_due / $tot_due;
                                $extra_per = $official_driver->amount_due_add / $tot_due;
                            }
                            if($official_driver->amount_due <= 0){
                                $tot_due = $official_driver->weekly_payment + $weekly_extra;
                                $vehicle_per = $official_driver->weekly_payment / $tot_due;
                                $extra_per = $weekly_extra / $tot_due;
                            }

                            $extra_ded = round($request->amount * $extra_per);

                            if($official_driver->daily_DriveNow == 1){
                                $official_driver->daily_due_add = ($official_driver->daily_due_add - $extra_ded);
                            }
                            $official_driver->amount_due_add = ($official_driver->amount_due_add - $extra_ded);

                            $extra_dues = $extra_due->get();
                            foreach ($extra_dues as $key => $extras) {
                                if($official_driver->daily_DriveNow == 1){
                                    $add_due = round($extra_ded * ($extras->daily_due/$daily_extra));
                                }else{
                                    $add_due = round($extra_ded * ($extras->due/$weekly_extra));
                                }
                                    $drivenow_add_due = DriveNowAdditionalTransactions::where('tran_id', $rave_transactions->id)->where('type',$extras->id)->first();
                                    if(!$drivenow_add_due){
                                        $drivenow_add_due = New DriveNowAdditionalTransactions;
                                    }
                                    
                                    $drivenow_add_due->tran_id = $rave_transactions->id;
                                    $drivenow_add_due->driver_id = $official_driver->driver_id;
                                    $drivenow_add_due->official_id = $official_driver->id;
                                    $drivenow_add_due->paid_amount = number_format($extra_ded,2);
                                    $drivenow_add_due->type = $extras->id;
                                    $drivenow_add_due->amount = number_format($add_due,2);
                                    $drivenow_add_due->save();
                            }


                            if($extra_ded >= $official_driver->extra_pay){

                                $official_driver->extra_pay = 0; 
                                DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1, 'completed_at' => Carbon::now()]);

                            }else{
                                $official_driver->extra_pay = ($official_driver->extra_pay - $extra_ded);
                            }
                            $official_driver->save();
                            $vehicle_ded = round($request->amount * $vehicle_per);
                        }  

                        if($official_driver->daily_DriveNow == 1){
                            $official_driver->daily_due = $official_driver->daily_due - $vehicle_ded;
                        }
                        $official_driver->amount_due = ($official_driver->amount_due - $vehicle_ded);
                        $official_driver->amount_paid = ($official_driver->amount_paid + $vehicle_ded);
                        
                        $official_driver->save();

                        if($official_driver->vehicle->fleet->management_fee != ''){
                            $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                            $management_fee = round(($official_driver->vehicle->fleet->management_fee / $total_fee ) * $vehicle_ded);
                            $weekly = round(($official_driver->vehicle->fleet->weekly / $total_fee ) * $vehicle_ded);
                            $company_share = round(($official_driver->vehicle->fleet->company_share / $total_fee ) * $vehicle_ded);
                            $road_worthy_fee = round(($official_driver->vehicle->fleet->road_worthy_fee / $total_fee ) * $vehicle_ded);
                            $insurance_fee = round(($official_driver->vehicle->fleet->insurance_fee / $total_fee ) * $vehicle_ded);
                            $maintenance_fee = round(($official_driver->vehicle->fleet->maintenance_fee / $total_fee ) * $vehicle_ded);
                            
                            $rave_transactions->management_fee = $management_fee ;
                            $rave_transactions->weekly = $weekly;
                            $rave_transactions->company_share = $company_share;
                            $rave_transactions->road_worthy_fee = $road_worthy_fee;
                            $rave_transactions->insurance_fee = $insurance_fee;
                            $rave_transactions->maintenance_fee = $maintenance_fee;
                            
                            $rave_transactions->save();
                        }
                        $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                        $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');
                        $vehicle_repayment_after = $total_repayment - $add_repayment;

                        $rave_transactions->total_after = $vehicle_repayment_after;
                        $rave_transactions->due = $vehicle_ded;
                        $rave_transactions->add_charge = $extra_ded;
                        $rave_transactions->due_after = $official_driver->amount_due;
                        $rave_transactions->save(); 
         
                    }else if($request->type != 'vehicle_full'){
                        $xtra_due = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->where('reason', $request->type)->get();  
                        if(count($xtra_due) == 0){
                            return back()->with('flash_error', "Payment Failed! No payment reason found on his account. Select another payment reason");
                        }else{
                            $extra_due = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->where('reason', $request->type)->first();
                            


                            $extra_ded = $request->amount;
                            if($official_driver->daily_DriveNow == 1){
                                $official_driver->daily_due_add = ($official_driver->daily_due_add - $extra_ded);
                            }
                            $official_driver->amount_due_add = ($official_driver->amount_due_add - $extra_ded);

                            $drivenow_add_due = DriveNowAdditionalTransactions::where('tran_id', $rave_transactions->id)->where('type',$extra_due->id)->first();
                                if(!$drivenow_add_due){
                                    $drivenow_add_due = New DriveNowAdditionalTransactions;
                                }
                                
                                $drivenow_add_due->tran_id = $rave_transactions->id;
                                $drivenow_add_due->driver_id = $official_driver->driver_id;
                                $drivenow_add_due->official_id = $official_driver->id;
                                $drivenow_add_due->paid_amount = round($extra_ded);
                                $drivenow_add_due->type = $extra_due->id;
                                $drivenow_add_due->amount = round($extra_ded);
                                $drivenow_add_due->save();

                            if($extra_ded >= $official_driver->extra_pay){

                                $official_driver->extra_pay = 0; 
                                DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1, 'completed_at' => Carbon::now()]);
                            }else{
                                $official_driver->extra_pay = ($official_driver->extra_pay - $extra_ded);
                            }
                            $official_driver->save();
                        }  

                        
                        
                        $official_driver->save();
                        $rave_transactions->due = 0;
                        $rave_transactions->add_charge = $extra_ded;
                        $rave_transactions->due_after = $official_driver->amount_due;
                        $rave_transactions->save(); 
                    }
                }
                            

                return back()->with('flash_success', 'Payment of '.currency($request->amount) .' made successfully for '. $Provider->first_name ." ". $Provider->last_name);

        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_due_break(Request $request){
        $official_driver = OfficialDriver::where('id', $request->official_id)->first();
        $vehicle_cost = str_replace(' ', '',$official_driver->vehicle_cost);
        if($request->reason == 'Extention'){

            $official_driver->contract_length = $official_driver->contract_length + $request->count;
            $vehicle_cost = $vehicle_cost + ($official_driver->weekly_payment * $request->count);
            $official_driver->vehicle_cost = $vehicle_cost;

        }else if($request->reason == 'Reduction'){

            $official_driver->contract_length = $official_driver->contract_length - $request->count;
            $vehicle_cost = $vehicle_cost - ($official_driver->weekly_payment * $request->count);
            $official_driver->vehicle_cost = $vehicle_cost;

        }else{
            if($request->due == 'daily'){
                $official_driver->break = 1;
                $official_driver->daily_due = $official_driver->daily_due - ($request->count * $official_driver->daily_payment);
            }else{
                $official_driver->break = 1;
                $official_driver->amount_due = $official_driver->amount_due - ($request->count * $official_driver->weekly_payment);
                $official_driver->balance_weeks = $official_driver->balance_weeks - $request->count;
            }
              

        }
        $official_driver->save();

        $break = new DriveNowPaymentBreak;
        $break->driver_id = $official_driver->driver_id;
        $break->official_id = $official_driver->id;
        $break->approved_by = Auth::guard('admin')->user()->id;
        $break->reason = $request->reason;
        $break->count = $request->count;
        $break->type = $request->due;
        $break->comments = $request->comments;
        $break->save();

        return redirect()->route('admin.drivenow.drivenow_due_break')->with('flash_success', 'Payment break added for a week');
    }

    public function drivenow_break(Request $request){

        $breaks = DriveNowPaymentBreak::orderBy('created_at','desc')->get();
        
        return view('admin.providers.drivenow_due_break', compact('breaks'));
    }

    public function drivenow_tracker(Request $request){
        try{
            if(request()->has('f')){
                if($request->f == "blocked"){
                    $official_drivers = OfficialDriver::where('engine_status', '!=', 0)->where('status', '!=', 1)->orderBy('updated_at','desc')->get();
                    $page = "Drive to Own Tracker - Blocked Drivers";
                }if($request->f == "due"){
                    if(date('D') != 'Tue'){
                        $official_drivers = OfficialDriver::where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->orderBy('updated_at','desc')->get();
                    }else{
                        $official_drivers = OfficialDriver::where('status', '!=', 1)->where('amount_due', '>', 0)->orderBy('updated_at','desc')->get();
                    }
                    $page = "Drive to Own Tracker - Default Drivers";
                }
            }else{
                $official_drivers = OfficialDriver::with('provider','vehicle')->where('status','!=', 1)->get();
                $page = "Drive to Own Tracker";
            }
            $imeis = '';
            $over = count($official_drivers)-1;
            //Fetching IMEI Number to feed Tro Traker api
            for ($i=0; $i < count($official_drivers); $i++) {  
            
                if($official_drivers[$i]->vehicle){
                    if($official_drivers[$i]->vehicle->imei !=''){
                        $imeis .= str_replace(' ', '',$official_drivers[$i]->vehicle->imei) .",";
                        $official_drivers[$i]->vehicle->imei = str_replace(' ', '',$official_drivers[$i]->vehicle->imei);
                        $official_drivers[$i]->save();
                    }
                   
                }
            }
            $imeis = substr_replace($imeis,"",-1);

            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with actual protract account name";
                $password = "replac with protrack password";
                $signature = md5(md5($password).$time);

                $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                $token_json = curl($token_url);

                $token_details = json_decode($token_json, TRUE);

                $tro_access_token = $token_details['record']['access_token'];
                Setting::set('tro_access_token', $tro_access_token);
                Setting::save();
                Log::info("Tro Access Token Called");
            }

            if($tro_access_token !='' && $imeis !=''){
                $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                $status_json = curl($status_url);

                $status_details = json_decode($status_json, TRUE);

                $official_drivers = array();
                if($status_details){
                    if($status_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with actual protract account name";
                        $password = "replac with protrack password";
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
                    if($status_details['code']== '10007'){
                        Log::info(json_encode($status_details));
                    }

                    for ($i=0; $i < count($status_details['record']); $i++) { 

                        $official_drivers[$i] = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->where('status','!=', 1)->first();
                        
                        if($official_drivers){
                            $official_driver = OfficialDriver::findOrFail($official_drivers[$i]->id);
                            if($status_details['record'][$i]['oilpowerstatus'] == 0){
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                            }else{
                                $official_driver->engine_status = 0;
                                $official_driver->save();
                            }
                            $official_drivers[$i]->latitude = $status_details['record'][$i]['latitude'];
                            $official_drivers[$i]->longitude = $status_details['record'][$i]['longitude'];
                            $official_drivers[$i]->car_speed = $status_details['record'][$i]['speed'];

                            $official_drivers[$i]->oilpowerstatus = $status_details['record'][$i]['oilpowerstatus'];

                            $official_drivers[$i]->datastatus = $status_details['record'][$i]['datastatus'];

                            $official_drivers[$i]->hearttime = Carbon::createFromTimestamp($status_details['record'][$i]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                        }
                    
                    }
                }
            }

            $total_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_dues = OfficialDriver::where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();


        // dd($official_drivers[40]);
            return view('admin.providers.drivenow_tracker', compact('official_drivers','total_due', 'total_paid', 'total_tran_suc', 'total_tran_fail', 'total_tran_pen', 'total_tran', 'driver_due', 'driver_dues', 'blocked_drivers', 'page'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error!');
        }
    }

    public function alldriver_engine_control(){

        try{
            $drivers = OfficialDriver::where('status', '!=', 1)->get();
            if(!empty($drivers)){
                foreach ($drivers as $key => $driver) {
                    $update = OfficialDriver::find($driver->id);
                    $update->engine_control = 1;
                    $update->save();
                    // (new SendPushNotification)->DriverOnline($driver->id);
                }
                
            }
            return back()->with('flash_success', "Made ".count($drivers)."  drivers Engine Control On successfully!");
        }catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error: Not able to make driver Engine Control');
        }
    }

    public function drivenow_engine_status($id){
        $data = array();
        try{
            
            $official_driver = OfficialDriver::where('id', $id)->first();

            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with actual protract account name";
                $password = "replac with protrack password";
                $signature = md5(md5($password).$time);

                $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                $token_json = curl($token_url);

                $token_details = json_decode($token_json, TRUE);

                $tro_access_token = $token_details['record']['access_token'];
                Setting::set('tro_access_token', $tro_access_token);
                Setting::save();
                Log::info("Tro Access Token Called");
            }
            if($tro_access_token !='' && $official_driver->imei_number !=''){

                $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->imei_number;

                $status_json = curl($status_url);

                $status_details = json_decode($status_json, TRUE);

                if($status_details){
                    if($status_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with actual protract account name";
                        $password = "replac with protrack password";
                        $signature = md5(md5($password).$time);

                        $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                        $token_json = curl($token_url);

                        $token_details = json_decode($token_json, TRUE);

                        $tro_access_token = $token_details['record']['access_token'];
                        Setting::set('tro_access_token', $tro_access_token);
                        Setting::save();
                        Log::info("Tro Access Token Called");
                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$official_driver->imei_number;

                        $status_json = curl($status_url);

                        $status_details = json_decode($status_json, TRUE);
                    }
                    if($status_details['record'][0]['oilpowerstatus'] == 0){
                        $official_driver->engine_status = 1;
                        $official_driver->save();
                    }else{
                        $official_driver->engine_status = 0;
                        $official_driver->save();
                    }

                }
                Log::info("Engine Status: ". $status_json);
                       
            }
            if($official_driver->engine_status == 1){
                $data['status'] = 'Blocked';
            }else{
                $data['status'] = 'Active';
            }
        
            return response()->json($data);

        } catch(\GuzzleHttp\Exception\RequestException $e){
            Log::info($e);
            return response()->json($data);
        }
    }

    public function drivenow(Request $request, $id)
    {
        $Provider = Provider::where('id',$id)->first();
        if($request->has('t')){

            $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status','=',1)->first();
        }else{
            $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status','!=',1)->first();
        }

        
        // dd($official_driver->driver_name."( ". $official_driver->driver_id." ) - ".$weeks);
        
        $official_drivers = OfficialDriver::where('status', '!=',1)->get();
        // foreach ($official_drivers as $official_driver) {
        //     $official_driver->vehicle_cost = str_replace(' ','',$official_driver->vehicle_cost);
        //     $official_driver->save();
        // }
        // dd($official_driver->vehicle_cost);
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
            $total_paid_transaction = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->where('slp_ref_id', 'not like', 'Drivenow_D%')->sum('amount');
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
                                    'subaccount' => 'ACCT_v28qb2ok6xpbnrp',
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
            $contract_date = $official_driver->agreement_start_date;
            $date = Carbon::now();

            $completed_weeks = $date->diffInWeeks($contract_date);
            if($completed_weeks > $official_driver->contract_length){
                $completed_weeks = $official_driver->contract_length;
            }
            $official_driver->completed_weeks = $completed_weeks;
            $last_payment = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->where('network', '!=', 'Eganow')->orderBy('created_at','desc')->first();
            // dd($ext);
            
        return view('admin.drivenow.driver_profile', compact('official_driver','Provider','transactions', 'missed','trans_id','transact','contract', 'day_offs','activities','revoke','extras','due_daily_conversion','daily_extra','weekly_extra','total_extra','vehicle_paid','total_add_transaction','split','last_payment'));
            
    }

    public function drivenow_payment($id)
    {
        $Provider = Provider::where('id',$id)->first();
        $transaction = DriveNowTransaction::where('driver_id', $Provider->id)->where('status', 0)->orderBy('updated_at', 'desc')->first();
        $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=',1)->first();
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

        return view('admin.drivenow.driver_pay', compact('official_driver','Provider','transaction','trans_id'));
            
    }

    public function drivenow_paynow(Request $request)
    {
        $Provider = Provider::where('id',$request->driver_id)->first();
        $official_driver = OfficialDriver::where('id', $request->official_id)->first();
        $bill = '';
        if($request->has('bill_id')){
            $bill = DriveNowTransaction::where('id', $request->bill_id)->first();
        }
        try{
            $User = Provider::find($Provider->id);
            
            if($request->has('mobile')){
                $mobile = $request->mobile;
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
                $trans_id = "DriveNow".$code;
                $dup_trans = DriveNowRaveTransaction::where('slp_ref_id', $trans_id)->first();
                if($dup_trans){
                    $code = rand(100000, 999999);
                    $name = substr($User->first_name, 0, 2);
                    $req_id = $name.$code;
                    $trans_id = "DriveNow".$code;
                }
                $amount = number_format($request->amount,2);
                
                //SlydePay Send Invoice and Confirm Payment
                if($request->has('payment_mode') && $request->payment_mode == "MOBILE"){
                    try{
                        $client = new \GuzzleHttp\Client();
                        $invoice_url = "https://posapi.usebillbox.com/webpos/payNow";
                        $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];

                        $res = $client->post($invoice_url, [ 
                            'headers' => $headers,
                            'json' => ["requestId"=> $req_id,
                                        "appReference"=> "Eganow",
                                        "secret"=> "replace with password",
                                        "serviceCode"=> "670",
                                        "amount"=> $amount,
                                        "currency"=> "GHS",
                                        "customerName"=> $User->first_name ." ". $User->last_name,
                                        "customerSegment"=> "",
                                        "reference"=> "DriveNow Weekly Payment of ".$request->amount,
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
                           return view('admin.drivenow.pay_failed', compact('Provider')); 
                        }
                        $rave_transactions = new DriveNowRaveTransaction;
                        $rave_transactions->driver_id = $Provider->id;
                        $rave_transactions->official_id = $official_driver->id;
                        if($bill != ''){
                            $rave_transactions->bill_id = $bill->id;
                        }
                        
                        $rave_transactions->reference_id = $req_id;
                        $rave_transactions->slp_ref_id = $trans_id;
                        $rave_transactions->network = $request->network;
                        $rave_transactions->amount = number_format($request->amount,2);
                        $rave_transactions->status = 2;
                        $rave_transactions->save();
                        $network = $request->network;
                        return view('admin.drivenow.pay_success', compact('network','Provider'));
                    }catch(\GuzzleHttp\Exception\RequestException $e){
        Log::info($e);
                        if($e->getResponse()->getStatusCode() == '404' || $e->getResponse()->getStatusCode() == '500'){
                            return view('admin.drivenow.pay_failed', compact('Provider'));
                        }
                    } 
                }
                    
        } catch(Exception $e) { 
            Log:info($e);
            return view('admin.drivenow.pay_failed', compact('Provider'));
        }
            
    }

    public function drivenow_terminated(Request $request){
        

        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $transactions = OfficialDriver::where('status', 1)->orderBy('updated_at','desc')->get();
            $total_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Terminated Drivers from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else{

            $transactions = OfficialDriver::where('status', 1)->orderBy('updated_at','desc')->get();
            $total_due = OfficialDriver::where('status', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('amount_due', '>', 0)->count();
                }
                // dd($driver_due);
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Terminated Drivers";
        }
            $total_driver = OfficialDriver::where('status', 1)->count();
                // $driver_due = $total_driver - $driver_paid;
        return view('admin.providers.drivenow_terminated', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid'));
    }

    public function due_engine_control(Request $request,  $status, $id){
        try {

            $official_driver = OfficialDriver::where('id', $id)->first();
            $official_driver->due_engine_control = $status;
            $official_driver->save();
            return back()->with('flash_success', "Car Engine Control Updated!");
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }

    }

    public function remove_drivenow_driver(Request $request)
    {
        try {
            $Provider = Provider::findOrFail($request->driver_id);
            if($Provider) {
                $Provider->update(['official_drivers' => 0]);
                $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
                $official_driver->status = 1;
                $official_driver->terminated_reason = $request->reason;
                $official_driver->terminated_on = Carbon::now();
                $official_driver->save();
                $vehicle = DriveNowVehicle::where('id',$official_driver->vehicle_id)->first();
                $vehicle->status = 4;
                $vehicle->save();
                return back()->with('flash_success', "Driver Removed from DriveNow Drivers");
            } else {
                return redirect()->route('fleet.provider.document.index', $id)->with('flash_error', "Driver can not be removed from DriveNow Driver!");
            }
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }


    //PayStack APIs

    /**
     * Redirect the User to Paystack Payment Page
     * @return Url
     */
    public function redirectToGateway(Request $request)
    {
        Log::info(json_encode($request->all()));
        try{    
                $rave_transactions = DriveNowRaveTransaction::where('slp_ref_id', $request->reference)->first();
                $driver = Provider::where('id', $request->driver_id)->first();
                $official_driver = OfficialDriver::where('driver_id',$request->driver_id)->with('vehicle')->where('status','!=',1)->first();
                if($request->has('dob')){
                    $official_driver->dob = $request->dob;
                    $official_driver->save();
                }
                // dd($official_driver);
                if(!$rave_transactions){
                    $rave_transactions = new DriveNowRaveTransaction;
                    $rave_transactions->driver_id = $request->driver_id;
                    $rave_transactions->official_id = $request->official_id;
                    $rave_transactions->bill_id = $request->bill_id;
                    $rave_transactions->reference_id = $request->orderID;
                    $rave_transactions->slp_ref_id = $request->reference;
                    $rave_transactions->amount = $request->amt;
                    $rave_transactions->status = 2;
                    $rave_transactions->save();
                }
                // if($official_driver->vehicle->fleet->management_fee != ''){
                //     $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                //     $management_fee = round(($official_driver->vehicle->fleet->management_fee / $total_fee ) * $request->amt);
                //     $weekly = round(($official_driver->vehicle->fleet->weekly / $total_fee ) * $request->amt);
                //     $company_share = round(($official_driver->vehicle->fleet->company_share / $total_fee ) * $request->amt);
                //     $road_worthy_fee = round(($official_driver->vehicle->fleet->road_worthy_fee / $total_fee ) * $request->amt);
                //     $insurance_fee = round(($official_driver->vehicle->fleet->insurance_fee / $total_fee ) * $request->amt);
                //     $maintenance_fee = round(($official_driver->vehicle->fleet->maintenance_fee / $total_fee ) * $request->amt);
                //     $revenue = $road_worthy_fee + $maintenance_fee + $company_share;

                    // $data = array(  
                    //         'email' => $driver->email, 
                    //         'amount' => $request->amount,
                    //         'currency' => "GHS",
                    //         'reference' => $request->reference,
                    //         'orderID' => $request->orderID,
                    //         'split' => [
                    //                     'type' => 'flat',
                    //                     'bearer_type' => "account",
                    //                     'subaccounts' => [[
                    //                         'subaccount' => 'ACCT_nt6l9ila89nt53e',
                    //                         'share' => round($weekly * 100)
                    //                     ],
                    //                     [
                    //                     'subaccount' => 'ACCT_v28qb2ok6xpbnrp',
                    //                     'share' => round($revenue * 100)
                    //                     ],
                    //                     [
                    //                         'subaccount'=> 'ACCT_6m0wlmc5zzs0lm6',
                    //                         'share' => round($insurance_fee * 100)
                    //                     ]
                    //                   ] 
                    //                 ] 
                    //         );

                    // }else{
                    //     $data = array(  
                    //                 'email' => $driver->email, 
                    //                 'amount' => $request->amount,
                    //                 'currency' => "GHS",
                    //                 'reference' => $request->reference,
                    //                 'orderID' => $request->orderID,
                    //             );
                    // }

                // dd($data);
                
            return Paystack::getAuthorizationUrl()->redirectNow();
        }catch(\Exception $e) {
            Log::info($e);
            return back()->with('flash_error','The paystack token has expired. Please refresh the page and try again.');
        }        
    }

    
    public function handleGatewayCallback(Request $request)
    {
        
        try{
            $paymentDetails = Paystack::getPaymentData(); //this comes with all the data needed to process the transaction
            // Getting the value via an array method
            $inv_id = $paymentDetails['data']['metadata']['invoiceId'];// Getting InvoiceId I passed from the form
            $status = $paymentDetails['data']['status']; // Getting the status of the transaction
            $channel = $paymentDetails['data']['authorization']['channel'];
            $network = $paymentDetails['data']['authorization']['bank'];
            $fees = $paymentDetails['data']['fees'];
            $id = $paymentDetails['data']['id'];
            $amount = $paymentDetails['data']['amount']; //Getting the Amount
            $number = $randnum = rand(1111111111,9999999999);// this one is specific to application
            $number = 'year'.$number;
            // dd($status);
            if($status == "success"){ //Checking to Ensure the transaction was succesful
                
                $rave_transactions = DriveNowRaveTransaction::where('slp_ref_id', $request->reference)->first();
                $official_driver = OfficialDriver::where('driver_id', $rave_transactions->driver_id)->where('status', '!=', 1)->with('vehicle')->first();

                $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');
                $vehicle_repayment_before = $total_repayment - $add_repayment;

                $rave_transactions->status = 1;
                $rave_transactions->network = $network;
                $rave_transactions->fees = ($fees / 100);
                $rave_transactions->slp_resp = $id;
                $rave_transactions->due_before = $official_driver->amount_due;
                $rave_transactions->total_before = $vehicle_repayment_before;
                $rave_transactions->save();

                $extra_due = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1);
                $daily_extra = $extra_due->sum('daily_due');
                $weekly_extra = $extra_due->sum('due');

                

                $vehicle_ded = $rave_transactions->amount;
                $extra_ded = 0;

                if($official_driver->extra_pay > 0){

                        //Calculating Percentage of payment to deduct from due
                        if($official_driver->daily_DriveNow == 1 && $official_driver->daily_due > 0){
                            $tot_due = $official_driver->daily_due + $official_driver->daily_due_add;
                            $vehicle_per = $official_driver->daily_due / $tot_due;
                            $extra_per = $official_driver->daily_due_add / $tot_due;
                        }
                        if($official_driver->daily_DriveNow == 1 && $official_driver->daily_due <= 0){
                            $tot_due = $official_driver->daily_payment + $daily_extra;
                            $vehicle_per = $official_driver->daily_payment / $tot_due;
                            $extra_per = $daily_extra / $tot_due;
                        }
                        if($official_driver->amount_due > 0){
                            $tot_due = $official_driver->amount_due + $official_driver->amount_due_add;
                            $vehicle_per = $official_driver->amount_due / $tot_due;
                            $extra_per = $official_driver->amount_due_add / $tot_due;
                        }
                        if($official_driver->amount_due <= 0){
                            $tot_due = $official_driver->weekly_payment + $weekly_extra;
                            $vehicle_per = $official_driver->weekly_payment / $tot_due;
                            $extra_per = $weekly_extra / $tot_due;
                        }

                        $extra_ded = $rave_transactions->amount * $extra_per;

                        if($official_driver->daily_DriveNow == 1){
                            $official_driver->daily_due_add = ($official_driver->daily_due_add - $extra_ded);
                        }
                        $official_driver->amount_due_add = ($official_driver->amount_due_add - $extra_ded);

                        $extra_dues = $extra_due->get();
                        foreach ($extra_dues as $key => $extras) {
                            if($official_driver->daily_DriveNow == 1){
                                $add_due = $extra_ded * ($extras->daily_due/$daily_extra);
                            }else{
                                $add_due = $extra_ded * ($extras->due/$weekly_extra);
                            }
                                $drivenow_add_due = DriveNowAdditionalTransactions::where('tran_id', $rave_transactions->id)->where('type',$extras->id)->first();
                                if(!$drivenow_add_due){
                                    $drivenow_add_due = New DriveNowAdditionalTransactions;
                                }
                                
                                $drivenow_add_due->tran_id = $rave_transactions->id;
                                $drivenow_add_due->driver_id = $official_driver->driver_id;
                                $drivenow_add_due->official_id = $official_driver->id;
                                $drivenow_add_due->paid_amount = number_format($extra_ded,2);
                                $drivenow_add_due->type = $extras->id;
                                $drivenow_add_due->amount = number_format($add_due,2);
                                $drivenow_add_due->save();
                        }

                        

                        if($extra_ded >= $official_driver->extra_pay){

                            $official_driver->extra_pay = 0; 
                            DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1, 'completed_at'=> Carbon::now()]);

                        }else{
                            $official_driver->extra_pay = ($official_driver->extra_pay - $extra_ded);
                        }
                        $official_driver->save();
                        $vehicle_ded = $rave_transactions->amount * $vehicle_per;
                }  

                if($official_driver->daily_DriveNow == 1){
                    $official_driver->daily_due = $official_driver->daily_due - $vehicle_ded;
                }

                $official_driver->amount_due = ($official_driver->amount_due - $vehicle_ded);
                $official_driver->amount_paid = ($official_driver->amount_paid + $vehicle_ded);
                
                $official_driver->save();

                if($official_driver->vehicle->fleet->management_fee != ''){

                    $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                    $management_fee = round(($official_driver->vehicle->fleet->management_fee / $total_fee ) * $vehicle_ded);
                    $weekly = round(($official_driver->vehicle->fleet->weekly / $total_fee ) * $vehicle_ded);
                    $company_share = round(($official_driver->vehicle->fleet->company_share / $total_fee ) * $vehicle_ded);
                    $road_worthy_fee = round(($official_driver->vehicle->fleet->road_worthy_fee / $total_fee ) * $vehicle_ded);
                    $insurance_fee = round(($official_driver->vehicle->fleet->insurance_fee / $total_fee ) * $vehicle_ded);
                    $maintenance_fee = round(($official_driver->vehicle->fleet->maintenance_fee / $total_fee ) * $vehicle_ded);


                    $rave_transactions->management_fee = $management_fee ;
                    $rave_transactions->weekly = $weekly;
                    $rave_transactions->company_share = $company_share;
                    $rave_transactions->road_worthy_fee = $road_worthy_fee;
                    $rave_transactions->insurance_fee = $insurance_fee;
                    $rave_transactions->maintenance_fee = $maintenance_fee;
                    
                    $rave_transactions->save();
                }
                $total_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('amount');
                $add_repayment = DriveNowRaveTransaction::where('official_id', $official_driver->id)->where('status',1)->sum('add_charge');

                $vehicle_repayment_after = $total_repayment - $add_repayment;
                $rave_transactions->due = $vehicle_ded;
                $rave_transactions->add_charge =$extra_ded;
                $rave_transactions->due_after = $official_driver->amount_due;
                $rave_transactions->total_after = $vehicle_repayment_after;
                $rave_transactions->save();
                $Provider = Provider::where('id',$official_driver->driver_id)->first();

                if($official_driver->daily_DriveNow == 1){
                    $d_due = $official_driver->daily_due;
                    $due_c = 0;
                }else{
                    $d_due = $official_driver->amount_due;
                    $due_c = 0;
                }
                if($d_due <= $due_c &&  $official_driver->engine_status == 1){
                // if($official_driver->amount_due <=0 &&  $official_driver->engine_status == 1){
                    if($official_driver->imei_number != ''){
                        try{
                            $time = Carbon::now()->timestamp;
                            $account = "replace with actual protract account name";
                            $password = "replac with protrack password";
                            $signature = md5(md5($password).$time);

                            $url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                            $json = curl($url);

                            $details = json_decode($json, TRUE);
                            Log::info("Tro Track Status". json_encode($details));
                            if($details['code'] != '10009') {
                               
                                $tro_access_token = $details['record']['access_token'];
                                if($tro_access_token !=''){
                                    //Turn ON the Engine
                                     $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,0";

                                    $json = curl($url);

                                    $details = json_decode($json, TRUE);

                                    $message = "Your vehicle has been reactivated. Contact Eganow driver support team if you have any issues.";

                                    (new SendPushNotification)->DriverEngineUpdate($Provider->id,$message);

                                    $official_driver->engine_restore_reason = 'Payment Due';
                                    $official_driver->engine_restore_by = 0;
                                    $official_driver->engine_restore_on = Carbon::now();
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
                            }

                        }catch(\GuzzleHttp\Exception\RequestException $e){
                            Log::info($e->getResponse()->getBody()->getContents());
                            if($e->getResponse()->getStatusCode() == '404' || $e->getResponse()->getStatusCode() == '500'){
                                
                            }
                        }
                    }
                }
                
                
            }else{
                $rave_transactions = DriveNowRaveTransaction::where('slp_ref_id', $request->reference)->first();
                $official_driver = OfficialDriver::where('driver_id', $rave_transactions->driver_id)->where('status', '!=', 1)->first();
                $rave_transactions->status = 0;
                $rave_transactions->network = $network;
                // $rave_transactions->fees = ($fees / 100);
                $rave_transactions->slp_resp = $id;
                $rave_transactions->due_before = $official_driver->amount_due;
                $rave_transactions->due_after = $official_driver->amount_due;
                $rave_transactions->save();

            }
            // if($request->ajax()) {
            // }else{
                if(Auth::guard('provider')->user() ){
                    Log::info('coming for provider');
                    return redirect()->route('provider.drivenow');
                    
                }else if(Auth::guard('admin')->user()){
                    Log::info('coming for admin');
                    return redirect()->route('admin.drivenow.profile',$official_driver->driver_id);   
                }
            // }
            
        
      
        // Now you have the payment details,
        // you can store the authorization_code in your DB to allow for recurrent subscriptions
        // you can then redirect or do whatever you want
        }catch(\Exception $e) {
            Log::info($e);
             return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }


    public function drivenow_payment_reminder(){
        try{

            $official_drivers = OfficialDriver::where('amount_due', '>', 0)->where('status','!=', 1)->get();
            foreach ($official_drivers as $official_driver) {
                $message = "PAYMENTS DEADLINE REMINDER: 

                            You have an outstanding balance of : GHS ".$official_driver->amount_due."

                            All outstanding payments are to be cleared by end of every monday. Your vehicle will be automatically be deactivated if you have an outstanding balance by end of the day.  

                            Login in here to pay: http:http://domain-name/provider/drivenow

                            Contact the Eganow Team if you have any issues.";

                $provider = Provider::where('id', $official_driver->driver_id)->first();


                pushSMS($provider->country_code, $provider->mobile, $message);

            }
            return back()->with('flash_success', "Payment Reminder sent to ".count($official_drivers)." DriveNow Drivers");
        }catch(\Exception $e) {
            Log::info($e);
             return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_daily_payment_reminder(){
            try{

            $official_drivers = OfficialDriver::where('daily_due', '>', 0)->where('daily_drivenow', 1)->where('status','!=', 1)->get();
            foreach ($official_drivers as $official_driver) {
                $message = "PAYMENTS DEADLINE REMINDER: 

                            You have an outstanding balance of : GHS ".$official_driver->daily_due."

                            All outstanding payments are to be cleared by end of day. Your vehicle will be automatically be deactivated if you have an outstanding balance by end of the day.  

                            Login in here to pay: http:http://domain-name/provider/drivenow

                            Contact the Eganow Team if you have any issues.";

                $provider = Provider::where('id', $official_driver->driver_id)->first();


                pushSMS($provider->country_code, $provider->mobile, $message);

            }
            return back()->with('flash_success', "Payment Reminder sent to ".count($official_drivers)." DriveNow Drivers");
        }catch(\Exception $e) {
            Log::info($e);
             return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function vehicle_assign(Request $request){
        try{
            $official_driver = OfficialDriver::where('id', $request->official_id)->first();

            if($official_driver->vehicle_id != $request->vehicle_number){
                $vehicle_old = DriveNowVehicle::where('id', $official_driver->vehicle_id)->first();
                if($vehicle_old){
                    $vehicle_old->status = 4;
                    $vehicle_old->driver_id =  0;
                    $vehicle_old->official_id =  0;
                    $vehicle_old->save();
                }
                $vehicle = DriveNowVehicle::where('id',$request->vehicle_number)->first();

                    $official_driver->imei_number = $vehicle->imei;
                    $official_driver->vehicle_make = $vehicle->make;
                    $official_driver->vehicle_model = $vehicle->model;
                    $official_driver->vehicle_year = $vehicle->year;
                    $official_driver->vehicle_image = $vehicle->car_picture;
                    $official_driver->vehicle_id = $vehicle->id;
                    $official_driver->vehicle_number = $vehicle->reg_no;
                    $official_driver->save();

                $vehicle->status = 5;
                $vehicle->driver_id =  $official_driver->driver_id;
                $vehicle->official_id =  $official_driver->id;
                $vehicle->save();
            }

            return redirect()->route('admin.drivenow.due_payment')->with('flash_success', $vehicle->reg_no ." Assinged to ". $official_driver->driver_name);
        }catch(\Exception $e) {
            Log::info($e);
         return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_blocked_history(Request $request){
        if($request->has('f')){
            $Driver = OfficialDriver::where('driver_id', $request->f)->where('status', '!=', 1)->first();
            $histories = DriveNowBlockedHistory::where('driver_id',$request->f)->with('provider', 'official', 'engine_off')->orderBy('created_at', 'desc')->get();
            $page = $Driver->driver_name."' s Engine Blocked History";
        }else{
            $histories = DriveNowBlockedHistory::with('provider', 'official', 'engine_off')->orderBy('created_at', 'desc')->paginate(300);
            $page = "Drive to Own Blocked History";
        }
        
        // dd($histories[2]);
        return view('admin.providers.drivenow_block_history', compact('page','histories'));

    }

    public function drivenow_driver_off($id){
        try{

            $total_day_offs = DriverDayOff::where('driver_id', $id)->where('created_at', '>', Carbon::now()->startOfWeek())
                             ->where('created_at', '<', Carbon::now()->endOfWeek())
                             ->where('status', '!=', 2)
                             ->count();
            if($total_day_offs >=1){
                return back()->with('flash_error', "For this week, you have already taken the maximum number of days off possible");
            }
            $DriverOff = DriverDayOff::where('driver_id',$id)->whereDate('day_off', Carbon::today())->first();

            $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();
            $official_driver->day_off = 1;
            $official_driver->save();
            
            if(!$DriverOff){
                $DriverOff = new DriverDayOff;
            }
            
            $DriverOff->driver_id = $id;
            $DriverOff->official_id = $official_driver->id;
            $DriverOff->day_off = Carbon::now();
            $DriverOff->status = 0;
            $DriverOff->save();

            
            return redirect()->route('admin.drivenow.profile',$id)->with('flash_success', 'You have marked yourself off for the day');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_driver_on($id){
        try{

            $DriverOff = DriverDayOff::where('driver_id',$id)->whereDate('day_off', Carbon::today())->first();

            $day_off = DriverDayOff::where('driver_id', $id)->where('status', 0)->first();
            if($day_off){
                $date = Carbon::parse($day_off->created_at);
                $now = Carbon::now();
                
                $diff = $date->diffInMinutes($now);
                if($diff > 15){
                    return redirect()->route('admin.drivenow.profile',$id)->with('flash_error', 'You are not allowed to revoke day off after 15 mins');
                } 
            }
            $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();
            $official_driver->day_off = 0;
            $official_driver->save();
            
            $DriverOff->driver_id = $id;
            $DriverOff->day_off = Carbon::now();
            $DriverOff->status = 2;
            $DriverOff->save();

            return redirect()->route('admin.drivenow.profile',$id)->with('flash_success', 'Your day off for the day has been revoked');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function add_deposit(Request $request){
        try{
            $deposit = DriverDeposit::where('driver_id', $request->driver_id)->first();
            if(!$deposit){
                $deposit = new DriverDeposit;
                $deposit->amount = $request->amount;
            }else{
                $deposit->amount = $deposit->amount + $request->amount;
            }
            
            $deposit->driver_id = $request->driver_id;
            $deposit->added_by = Auth::guard('admin')->user()->id;
            $deposit->remarks = $request->remarks;
            $deposit->status = 0;
            $deposit->save();

            $driver = Provider::where('id', $request->driver_id)->first();
        //Create DriveNow Transaction for Deposit amount paid by Driver
            $code = rand(100000, 999999);
            $name = substr($driver->first_name, 0, 2);
            $req_id = $name.$code;
            $trans_id = "Drivenow_DD".$code;

            $rave_transactions = new DriveNowRaveTransaction;
            $rave_transactions->driver_id = $request->driver_id;
            $rave_transactions->reference_id = $req_id;
            $rave_transactions->slp_ref_id = $trans_id;
            $rave_transactions->slp_resp = $trans_id;
            $rave_transactions->network = "Eganow";
            $rave_transactions->amount = $request->amount;
            $rave_transactions->status = 1;
            $rave_transactions->save();

            return redirect()->route('admin.drivenow.deposits')->with('flash_success', 'Deposits added');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function refund_deposit(Request $request){
        try{
            $deposit = DriverDeposit::where('id', $request->id)->first();
            $deposit->refund = $request->refund;
            $deposit->refunded_by = Auth::guard('admin')->user()->id;
            $deposit->refund_reason = $request->reason;
            $deposit->acc_no = $request->acc_no;
            $deposit->acc_name = $request->acc_name;
            $deposit->bank_name = $request->bank_name;
            $deposit->bank_code = $request->bank_code;
            $deposit->status = 1;
            $deposit->save();

            $expense = new OfficeExpense;
            $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
 
            $code = substr(str_shuffle($str_result),0, 4);
            $exp_id = "FE".$code.date('H');
            $expense->exp_id = $exp_id;
            $expense->category = 10;
            $expense->paid_to = $request->paid;
            $expense->amount = $request->refund;
            $expense->date = Carbon::now();
            $expense->acc_no = $request->acc_no;
            $expense->bank_name = $request->bank_name;
            $expense->bank_code = $request->bank_code;
            $expense->description = $request->reason;
            $expense->added_by = Auth::guard('admin')->user()->id;
            $expense->status = 0;
            $expense->save();

            return redirect()->route('admin.drivenow.deposits')->with('flash_success', 'Refund Requested successfully');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function deposits(Request $request){
        $banks = Bank::all(); 
        $users = Admin::where('role', '!=', 'admin')->get();
        
        $vehicles = DriveNowVehicle::where('status', 4)->get();
        
        
        $deposit_month = DriverDeposit::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->sum('amount');
        $refund_month = DriverDeposit::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->sum('refund');
        if($request->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $filter = substr($dates[1],strpos($dates[1], "="));
            
            // $dates[1] = substr($dates[1], 0, strpos($dates[1], "?"));
            $page = "List of drivers made deposits from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
                if($filter =="=1"){
                    $deposits = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('driver','added','official')->groupby('driver_id')->get();
                }elseif($filter =="=2"){
                    $deposits = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->with('driver','added','official')->groupby('driver_id')->get();
                }elseif($filter =="=3"){
                    $deposits = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status','!=',1)->with('driver','added','official')->groupby('driver_id')->whereDoesntHave('official')->get();
                    
                }
                else{
                   $deposits = DriverDeposit::where('status', '!=',1)->with('driver','added')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->groupby('driver_id')->get(); 
                }
            
            
            $refunds = DriverDeposit::where('status', 1)->with('driver','added')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->groupby('driver_id')->get();
            $total_deposits = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->sum('amount');
            $total_refunds = DriverDeposit::whereNotNull('refund')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->sum('refund');
            $drivers_deposited = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->groupby('driver_id')->get();

            $drivers_refunded = DriverDeposit::where('status', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->groupby('driver_id')->count();
            $waiting_contract = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereDoesntHave('official')->groupby('driver_id')->where('status', '!=',1)->get();
            $waiting_allocation = DriverDeposit::whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereHas('vehicle')->groupby('driver_id')->get();
            
        }else{
            if($request->has('filter')){
                if($request->filter == 1){
                    $deposits = DriverDeposit::with('driver','added','official')->groupby('driver_id')->get();
                }elseif($request->filter == 2){
                    $deposits = DriverDeposit::where('status',1)->with('driver','added','official')->groupby('driver_id')->get();
                }elseif($request->filter == 3){
                    $deposits = DriverDeposit::where('status','!=',1)->with('driver','added','official')->groupby('driver_id')->whereDoesntHave('official')->get();
                }
                
            }else{
                $deposits = DriverDeposit::where('status', '!=',1)->with('driver','added','official')->groupby('driver_id')->get();
            }
            
            
            $refunds = DriverDeposit::where('status', 1)->with('driver','added')->get();
            $total_deposits = DriverDeposit::sum('amount');
            $total_refunds = DriverDeposit::whereNotNull('refund')->sum('refund');
            $drivers_deposited = DriverDeposit::distinct('driver_id')->get();
            $waiting_contract = DriverDeposit::whereDoesntHave('official')->where('status', '!=',1)->get();
            $waiting_allocation = DriverDeposit::whereHas('vehicle')->groupby('driver_id')->get();
            $drivers_refunded = DriverDeposit::where('status', 1)->distinct('driver_id')->count();
            $contracts = DriveNowContracts::where('status',1)->get();
            $page = "Driver Deposits";
        }
        $suppliers = DriveNowVehicleSupplier::orderBy('created_at' , 'desc')->where('status', '!=',1)->get();
        $allocated = (int)(count($deposits) - (count($waiting_allocation) + count($waiting_contract)));
        
        return view('admin.providers.deposits', compact('vehicles','page','deposits','total_deposits', 'drivers_deposited','deposit_month','banks','refunds','total_refunds','refund_month','drivers_refunded','users','transactions','waiting_allocation','waiting_contract','allocated','suppliers','contracts'));
    }

    //Ajax Call
    public function deposit_transactions($id){

        $transactions = DriveNowRaveTransaction::where('driver_id', $id)->where('slp_resp', 'like', '%Drivenow_D%')->get();

        $resp = "";
        $resp .= '<div class="table-responsive tab-pane active" >
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th>Trans ID</th>
                                <th>Amount</th>
                                <th>Deposited at</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>';
                if($transactions){
                    foreach($transactions as $transaction){
                    $resp .= '<tr>
                        <td>'.$transaction->slp_resp .'</td>
                        <td>'.$transaction->amount .'</td>
                        <td>'.date('D, j-M-Y, G:i', strtotime($transaction->updated_at)) .'</td>
                        <td>Paid</td>
                        </tr>';
                    }
                }
                
                
        $resp .= '</tbody>
                </table>
            </div>';
            Log::info('Transactions: '. $resp);
        return $resp;
    }

    public function block_offline_device(Request $request)
    {
        

        try{
                $drivenow_due_engine_control = Setting::get('drivenow_due_engine_control', 0);
                if($request->has('st')){
                    $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
                    $official_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->with('provider')->where('status','!=', 1)->get();
                }else{
                    $official_drivers = OfficialDriver::with('provider')->where('status','!=', 1)->get();
                }
                // $official_drivers = OfficialDriver::with('provider')->where('status','!=', 1)->get();
                $imeis = '';
                //Fetching IMEI Number to feed Tro Traker api
                for ($i=0; $i < count($official_drivers); $i++) { 
                    if(date('D') == 'Tue'){
                        $due_c = 0;
                    }else{
                        $due_c = $official_drivers[$i]->weekly_payment;
                    } 
                    $due = $official_drivers[$i]->amount_due;
                    if($official_drivers[$i]->imei_number !='' && $official_drivers[$i]->engine_status != 1 && $drivenow_due_engine_control != 0 && $due > $due_c && $official_drivers[$i]->due_engine_control != 1){
                        $imeis .= str_replace(' ', '',$official_drivers[$i]->imei_number) .",";
                        $official_drivers[$i]->imei_number = str_replace(' ', '',$official_drivers[$i]->imei_number);
                        $official_drivers[$i]->save();
                    }
                }
                $imeis = substr_replace($imeis,"",-1);
                    $tro_access_token = Setting::get('tro_access_token','');
                    if($tro_access_token !='' && $imeis !=''){

                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                        $status_json = curl($status_url);

                        $status_details = json_decode($status_json, TRUE);
                        //Checking Token Expiration
                        if($status_details['code']== '10012'){
                            $time = Carbon::now()->timestamp;
                            $account = "replace with actual protract account name";
                            $password = "replac with protrack password";
                            $signature = md5(md5($password).$time);

                            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                            $token_json = curl($token_url);

                            $token_details = json_decode($token_json, TRUE);

                            $tro_access_token = $token_details['record']['access_token'];
                            Setting::set('tro_access_token', $tro_access_token);
                            Setting::save();
                            Log::info("Tro Access Token Expired Called");

                            $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                            $status_json = curl($status_url);

                            $status_details = json_decode($status_json, TRUE);
                            
                        }
                        for ($i=0; $i < count($status_details['record']); $i++) { 

                            $official_driver = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->first();
                            $driver = Provider::where('id', $official_driver->driver_id)->first();
                            if($status_details['record'][$i]['oilpowerstatus'] == 0){
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                            }else{
                                $official_driver->engine_status = 0;
                                $official_driver->save();
                            }

                            $car_speed = $status_details['record'][$i]['speed'];
                            $offline_status = $status_details['record'][$i]['datastatus'];

                            if($car_speed > 3 ){
                                 $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043.";
                                Log::info("Car Speed Up: ". $official_driver->driver_name." ( ". $driver->id ." )");
                                (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                                $official_driver->block_try = "Speed up";
                                $official_driver->save();
                                //Send SMS Notification
                                $content = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
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

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                            }
                            else if($offline_status != 2){
                                $vehicle = DriveNowVehicle::where('imei',$status_details['record'][$i]['imei'])->first();
                                if($vehicle->sim !=''){
                                    $mobile = $vehicle->sim;
                                    // if($mobile[0] == 0){
                                    //     $receiver = $mobile;
                                    // }else{
                                    //     $receiver = "0".$mobile; 
                                    // }
                                    $content = urlencode("*22*2#");
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
                                    Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                                }
                                
                            }else if($offline_status ==2){
                                Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                                //Turn off the Engine
                                $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,1";

                                $json = curl($url);

                                $details = json_decode($json, TRUE);

                                $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
                                Log::info($message);
                                (new SendPushNotification)->DriverEngineUpdate($driver->id,$message);

                                $td = date('Y-m-d');
                                $official_driver->engine_off_reason = 'Payment Due';
                                $official_driver->engine_off_on = Carbon::now();
                                $official_driver->engine_off_by = Auth::guard('admin')->user()->id;;
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                                $blocked_history = DriveNowBlockedHistory::where('driver_id',$official_driver->driver_id)->whereDate('engine_off_on',$td)->first();
                                if(!$blocked_history){
                                   $blocked_history = new DriveNowBlockedHistory; 
                                }
                                
                                $blocked_history->official_id = $official_driver->id;
                                $blocked_history->driver_id = $official_driver->driver_id;
                                $blocked_history->engine_off_by = Auth::guard('admin')->user()->id;;
                                $blocked_history->amount_due = $official_driver->amount_due;
                                $blocked_history->engine_off_on = Carbon::now();
                                $blocked_history->engine_off_reason = $official_driver->engine_off_reason;
                                $blocked_history->save();

                                $vehicle = DriveNowVehicle::where('imei',$status_details['record'][$i]['imei'])->first();
                                if($vehicle->sim !=''){
                                    $mobile = $vehicle->sim;
                                    // if($mobile[0] == 0){
                                    //     $receiver = $mobile;
                                    // }else{
                                    //     $receiver = "0".$mobile; 
                                    // }
                                    $content = "*22*2#";
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
                                    Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                                }

                                //Send SMS Notification
                                $content = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
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

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                            } 
                        }
                    }
                
                    return back()->with('flash_error', "Blocked engines with due and sent SMS devices not reacheable.");
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    //Add driver to daily payments
    public function add_to_drivenow_daily($id){
        try{
            $official_driver = OfficialDriver::where('id', $id)->first();
            $daily_due = round($official_driver->weekly_payment / 6);
            $official_driver->daily_DriveNow = 1;
            $official_driver->daily_payment = $daily_due;
            $official_driver->daily_due = 0;
            // $official_driver->daily_payment = $request->daily_due;
            $official_driver->save();

            // if($request->change != 1){
                // $today = date('Y-m-d');
                // $next_due = date('Y-m-d', strtotime('tomorrow'));
                // $drivenow_transaction = DriveNowTransaction::where('daily_due_date',$today)->where('driver_id', $official_driver->driver_id)->first();
                        
                // if(!$drivenow_transaction){
                //     $drivenow_transaction = new DriveNowTransaction;
                //     $official_driver->next_due = $next_due;
                //     $official_driver->break = 0;
                //     $official_driver->daily_due = $official_driver->daily_due + $official_driver->daily_payment; 
                //     $official_driver->save();
                // }

                // $drivenow_transaction->driver_id = $official_driver->driver_id;
                // $drivenow_transaction->contract_id = $official_driver->id;
                // $drivenow_transaction->amount = $official_driver->daily_payment;
                // $drivenow_transaction->daily_due_date = $official_driver->next_due;
                // $drivenow_transaction->status = 0;
                // $drivenow_transaction->save();

                // return redirect()->route('admin.drivenow.drivenow_daily')->with('flash_success', 'Driver added to DriveNow Daily Payments');
            // }else{
                return redirect()->route('admin.drivenow.drivenow_daily')->with('flash_success', 'Driver added to DriveNow Daily Payments');
            // }
            
            
            

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

        //Add driver with pending due to daily payments 
    public function add_to_drivenow_daily_due(Request $request){
        try{
            $official_driver = OfficialDriver::where('id', $request->id)->first();
            $official_driver->daily_DriveNow = 1;
            $official_driver->daily_due = 0;
            $official_driver->daily_payment = $request->amount;
            $official_driver->save();
             
            return redirect()->route('admin.drivenow.drivenow_daily')->with('flash_success', 'Driver added to DriveNow Daily Payments');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }


    //Change Daily Due amount
    public function remove_drivenow_daily(Request $request){
        try{
            $official_driver = OfficialDriver::where('id', $request->id)->first();
            
            $official_driver->daily_DriveNow = 0;
            $official_driver->save();
            return redirect()->route('admin.drivenow.due_payment')->with('flash_success', 'Driver removed from DriveNow Daily Payments');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_daily(Request $request){
        
        $credit_pending_transactions = DriveNowRaveTransaction::where('status', 2)->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                 $CP = Helper::ConfirmPayment($credit_pending_transaction->id);
            }

        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $transactions = OfficialDriver::whereBetween('updated_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->where('daily_drivenow', 1)->orderBy('updated_at','desc')->get();
            $total_due = OfficialDriver::whereBetween('updated_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->where('daily_due', '>', 0)->where('daily_drivenow', 1)->sum('daily_due');
            
            $overall_due = OfficialDriver::whereBetween('updated_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');


            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                
            $driver_due = OfficialDriver::whereBetween('updated_at',[date($dates[0]), date($dates[1])])->whereRaw('daily_due > daily_payment')->where('status', '!=', 1)->where('daily_drivenow', 1)->count();
                
            $driver_dues = OfficialDriver::whereBetween('updated_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->where('daily_drivenow', 1)->where('daily_due', '>', 0)->count();
                
            $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Daily Due Payments from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else if(request()->has('f')){
            if($request->f == "blocked"){
                $transactions = OfficialDriver::where('engine_status', '!=', 0)->where('status', '!=', 1)->orderBy('updated_at','desc')->get();
                
                $total_due = OfficialDriver::where('engine_status', '!=', 0)->where('daily_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow', 1)->sum('daily_due');
                $overall_due = OfficialDriver::where('engine_status', '!=', 0)->where('amount_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
                $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::get()->count();
                   
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->whereRaw('daily_due > daily_payment')->count();
                    
                    $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->where('daily_due', '>', 0)->count();
                    $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                    $page = "DriveNow Blocked Drivers";
            }else if($request->f == "due"){
                
                $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_due', '>', 0)->where('daily_drivenow', 1)->orderBy('updated_at','desc')->get();
                
                $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->where('daily_due', '>', 0)->count();
                $total_due = OfficialDriver::where('daily_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow', 1)->sum('daily_due');
                $overall_due = OfficialDriver::where('amount_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
                $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::get()->count();
                $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->whereRaw('daily_due > daily_payment')->count();
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Drivers with Daily Due";
            }
        }else{
            
            $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->with('vehicle')->orderBy('updated_at','desc')->get();
            for ($o=0; $o < count($transactions); $o++) {
            
                $OldDriverOff = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', '<', Carbon::today())->update(['status' => 1]);
                $cur_day_off = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', Carbon::today())->where('status',0)->first();

                if(!$cur_day_off){
                    $transactions[$o]->day_off = 0;
                    $transactions[$o]->save();
                }
                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('add_charge');
                $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);
            }
            $total_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->where('daily_due','>',0)->sum('daily_due');
            $overall_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();

            $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->whereRaw('daily_due > daily_payment')->count();
            $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->where('daily_due', '>', 0)->count();
            
                    
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Daily Due Payments";
        }
            $vehicles = DriveNowVehicle::where('status', 4)->get();
            $total_driver = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', 1)->count();
            $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('daily_drivenow','=', 1)->where('status', '!=', 1)->count();
            $total_blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();

        return view('admin.providers.drivenow_daily_payments', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','vehicles','driver_dues','overall_due','total_blocked_drivers','total_driver'));
    }

    public function block_daily_due_drivers(Request $request)
    {
        try{
                $drivenow_due_engine_control = Setting::get('drivenow_due_engine_control', 0);
                $official_drivers = OfficialDriver::with('provider')->where('daily_drivenow',1)->where('status','!=', 1)->get();
                $imeis = '';
                //Fetching IMEI Number to feed Tro Traker api
                for ($i=0; $i < count($official_drivers); $i++) { 
                     $today = date('Y-m-d');
                    
                    if($official_drivers[$i]->next_due == $today){
                        $due_c = $official_drivers[$i]->daily_payment;
                    }else{
                        $due_c = 0;
                    }
                    $due = $official_drivers[$i]->daily_due;
                    if($official_drivers[$i]->imei_number !='' && $official_drivers[$i]->engine_status != 1 && $drivenow_due_engine_control != 0 && $due > $due_c && $official_drivers[$i]->due_engine_control != 1){
                        $imeis .= str_replace(' ', '',$official_drivers[$i]->imei_number) .",";
                        $official_drivers[$i]->imei_number = str_replace(' ', '',$official_drivers[$i]->imei_number);
                        $official_drivers[$i]->save();
                    }
                }
                $imeis = substr_replace($imeis,"",-1);
                    $tro_access_token = Setting::get('tro_access_token','');
                    if($tro_access_token !='' && $imeis !=''){

                        $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                        $status_json = curl($status_url);

                        $status_details = json_decode($status_json, TRUE);
                        //Checking Token Expiration
                        if($status_details['code']== '10012'){
                            $time = Carbon::now()->timestamp;
                            $account = "replace with actual protract account name";
                            $password = "replac with protrack password";
                            $signature = md5(md5($password).$time);

                            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                            $token_json = curl($token_url);

                            $token_details = json_decode($token_json, TRUE);

                            $tro_access_token = $token_details['record']['access_token'];
                            Setting::set('tro_access_token', $tro_access_token);
                            Setting::save();
                            Log::info("Tro Access Token Expired Called");

                            $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                            $status_json = curl($status_url);

                            $status_details = json_decode($status_json, TRUE);
                            
                        }
                        for ($i=0; $i < count($status_details['record']); $i++) { 

                            $official_driver = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->first();
                            $driver = Provider::where('id', $official_driver->driver_id)->first();
                            if($status_details['record'][$i]['oilpowerstatus'] == 0){
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                            }else{
                                $official_driver->engine_status = 0;
                                $official_driver->save();
                            }

                            $car_speed = $status_details['record'][$i]['speed'];
                            $offline_status = $status_details['record'][$i]['datastatus'];

                            if($car_speed > 3 ){
                                 $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043.";
                                Log::info("Car Speed Up: ". $official_driver->driver_name." ( ". $driver->id ." )");
                                (new SendPushNotification)->DriverBreakTime($driver->id,$message);
                                $official_driver->block_try = "Speed up";
                                $official_driver->save();
                                //Send SMS Notification
                                $content = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
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

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                            }
                            else if($offline_status != 2){
                                $vehicle = DriveNowVehicle::where('imei',$status_details['record'][$i]['imei'])->first();
                                if($vehicle->sim !=''){
                                    $mobile = $vehicle->sim;
                                    // if($mobile[0] == 0){
                                    //     $receiver = $mobile;
                                    // }else{
                                    //     $receiver = "0".$mobile; 
                                    // }
                                    $content = "*22*2#";
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
                                    Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                                }
                                
                            }else if($offline_status ==2){
                                Log::info("Turning off the Engine: ". $official_driver->driver_name." ( ". $driver->id ." )");
                                //Turn off the Engine
                                $url = "http://api.protrack365.com/api/command/send?access_token=".$tro_access_token."&imei=".$official_driver->imei_number."&command=RELAY,1";

                                $json = curl($url);

                                $details = json_decode($json, TRUE);

                                $message = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
                                Log::info($message);
                                (new SendPushNotification)->DriverEngineUpdate($driver->id,$message);

                                $td = date('Y-m-d');
                                $official_driver->engine_off_reason = 'Payment Due';
                                $official_driver->engine_off_on = Carbon::now();
                                $official_driver->engine_off_by = Auth::guard('admin')->user()->id;;
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                                $blocked_history = DriveNowBlockedHistory::where('driver_id',$official_driver->driver_id)->whereDate('engine_off_on',$td)->first();
                                if(!$blocked_history){
                                   $blocked_history = new DriveNowBlockedHistory; 
                                }
                                
                                $blocked_history->official_id = $official_driver->id;
                                $blocked_history->driver_id = $official_driver->driver_id;
                                $blocked_history->engine_off_by = Auth::guard('admin')->user()->id;;
                                $blocked_history->amount_due = $official_driver->amount_due;
                                $blocked_history->engine_off_on = Carbon::now();
                                $blocked_history->engine_off_reason = $official_driver->engine_off_reason;
                                $blocked_history->save();

                                $vehicle = DriveNowVehicle::where('imei',$status_details['record'][$i]['imei'])->first();
                                if($vehicle->sim !=''){
                                    $mobile = $vehicle->sim;
                                    // if($mobile[0] == 0){
                                    //     $receiver = $mobile;
                                    // }else{
                                    //     $receiver = "0".$mobile; 
                                    // }
                                    $content = "*22*2#";

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
                                    Log::info("Engine Block SMS Sent to ". $vehicle->sim ." - ". $official_driver->driver_name);
                                }

                                //Send SMS Notification
                                $content = "Vehicle deactivated due to payment issue. Please Make payment immediately and contact Eganow  driver support team on 0506428043";
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

                                // $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";

                                // $headers = ['Content-Type' => 'application/json'];
                                
                                // $res = $client->get($url, ['headers' => $headers]);

                                // $code = (string)$res->getBody();
                                // $codeT = str_replace("\n","",$code);
                            } 
                        }
                    }
                
                    return back()->with('flash_error', "Blocked engines with due and sent SMS devices not reacheable.");
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_contract_change(Request $request){
        try{
            $Provider = Provider::where('id',$request->driver_id)->first();
            $official_driver = OfficialDriver::where('id', $request->official_id)->first();
            $d_contracts = DriverContracts::where('driver_id',$Provider->id)->first();

                if($d_contracts){

                $contracts = DriverContracts::where('driver_id',$Provider->id)->update(['status'=>2]);

                }
                $contract = DriveNowContracts::where('id', $request->contract_id)->first();


                //Add New Contract to driver
                $driver_contract = new DriverContracts;

                $driver_contract->driver_id = $Provider->id;
                $driver_contract->official_id = $official_driver->id;
                $driver_contract->contract_id = $contract->id;
                $driver_contract->agreement_start_date = $request->agreement_start_date;
                $driver_contract->save();

                //Update the new contract id to official_driver table
                $official_driver->contract_id = $driver_contract->id;
                $official_driver->agreement_start_date = $request->agreement_start_date;
                $official_driver->agreed = 0;
                $official_driver->save();

                $Provider->agreed = 0;
                $Provider->agreement_start_date = $request->agreement_start_date;
                $Provider->save();

            return back()->with('flash_success', "New Contract assigned to ".$Provider->first_name." successfully");

            } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_extra(Request $request){
        $official_driver = OfficialDriver::where('id', $request->official_id)->first();
        

        $extra = new DriveNowExtraPayment;
        $extra->driver_id = $official_driver->driver_id;
        $extra->official_id = $official_driver->id;
        $extra->approved_by = Auth::guard('admin')->user()->id;
        $extra->reason = $request->reason;
        // $extra->count = $request->count;
        $extra->total = $request->total;
        // $due = $request->total / $request->count;
        
        if($request->type =='days'){
            $extra->daily_due = $request->due;
            $due = $request->due * 6;
        }else{
            $due = $request->due;
            $extra->daily_due = number_format(($due / 6),2);
        }
        $extra->due = $due;
        $extra->comments = $request->comments;
        // $extra->started_at = $request->started_at;
        $extra->type = $request->type;
        $extra->status = 0;
        $extra->save();

        $official_driver->extra_pay = $official_driver->extra_pay + $extra->total;
        $official_driver->save();
        
        return redirect()->route('admin.drivenow.extra_due')->with('flash_success', 'Additional Charges Applied');
    }

    public function extra_due(){
        $extras = DriveNowExtraPayment::orderBy('created_at','desc')->get();
        return view('admin.providers.extra_due', compact('extras'));
    }

    public function extra_deactivate($id){
        $extras = DriveNowExtraPayment::where('id',$id)->first();
        $extras->status = 1;
        $extras->save();
        $official_driver = OfficialDriver::where('id', $extras->official_id)->first();
        $official_driver->extra_pay = 0;
        $official_driver->save();
        return redirect()->route('admin.drivenow.extra_due')->with('flash_success', 'Additional Charges Removed');
    }

    public function invoice_history(Request $request, $id){
        $official = OfficialDriver::where('id', $id)->where('status','!=',1)->first();
        if($official){
            $invoices = DriveNowTransaction::where('contract_id', $official->id)->orderBy('updated_at','desc')->get();
            $page = "Invoices Generated for ".$official->driver_name;
            if(request()->has('filter_date')){
                $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                $invoices = DriveNowTransaction::where('contract_id', $official->id)->orderBy('updated_at','desc')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                $page = "Invoice generated for ".$official->driver_name." from ".date('jS M Y ',strtotime($dates[0])) ." to ".date('jS M Y ',strtotime($dates[1]));
            }
            return view('admin.providers.drivenow_invoices', compact('official','invoices','page'));
        }else{
            return back()->with('flash_error', "No Invoices found for this driver");
        }

    }
    public function invoice_histories(Request $request){
            $invoices = DriveNowTransaction::orderBy('updated_at','desc')->get()->take(1000);
            $page = "";
            if(request()->has('filter_date')){
                $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                $invoices = DriveNowTransaction::orderBy('updated_at','desc')->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get();
                $page = "Invoice generated from ".date('jS M Y ',strtotime($dates[0])) ." to ".date('jS M Y ',strtotime($dates[1]));
            }
            
            return view('admin.providers.drivenow_invoice_history', compact('invoices','page'));

    }

    public function drivenow_payment_review(Request $request){
        try{

        $credit_pending_transactions = DriveNowRaveTransaction::where('status', 2)->orderBy('created_at', 'desc')->get();



        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->orderBy('updated_at','desc')->get();
            $total_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->sum('amount_due');

            $overall_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');

            $total_fees= DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('fees');

            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Due Payments from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else if(request()->has('f')){
            if($request->f == "blocked"){
                $transactions = OfficialDriver::where('engine_status', '!=', 0)->where('daily_drivenow','!=', 1)->where('status', '!=', 1)->orderBy('updated_at','desc')->get();
                $total_due = OfficialDriver::where('engine_status', '!=', 0)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                $overall_due = OfficialDriver::where('engine_status', '!=', 0)->where('status', '!=', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
                $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
                $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::get()->count();
                    if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->where('amount_due', '>', 0)->count();
                    }
                    $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->where('amount_due', '>', 0)->count();
                    $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                    $page = "DriveNow Blocked Drivers";
            }else if($request->f == "due"){
                if(date('D') != 'Tue'){
                    $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->orderBy('updated_at','desc')->get();
                }else{
                    $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->orderBy('updated_at','desc')->get();
                }
                
                $total_due = OfficialDriver::where('amount_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                $overall_due = OfficialDriver::where('amount_due', '>', 0)->where('status', '!=', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
                $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
                $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::get()->count();
                    if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    }
                    $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                    $page = "DriveNow Drivers with Due";
            }
        }else{
                    
            $transactions = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->with('vehicle')->orderBy('updated_at','desc')->get();
            $official_drivers = OfficialDriver::where('status', '!=', 1)->with('vehicle')->orderBy('updated_at','desc')->get();
            for ($o=0; $o < count($transactions); $o++) {
            
                $OldDriverOff = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', '<', Carbon::today())->update(['status' => 1]);
                $cur_day_off = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', Carbon::today())->where('status',0)->first();


                if(!$cur_day_off){
                    $transactions[$o]->day_off = 0;
                    $transactions[$o]->save();
                }
                $transactions[$o]->txn = DriveNowTransaction::where('driver_id',$transactions[$o]->driver_id)->whereNotNull('due_date')->count();
                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('amount');
                // $transactions[$o]->txn_due = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('due');
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('add_charge');

                $transactions[$o]->txn_due = DriveNowRaveTransaction::where('driver_id', $transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->where('slp_ref_id', 'not Like', '%Drivenow_IA%')->sum('amount');
                $transactions[$o]->txn_dd = DriveNowRaveTransaction::where('driver_id', $transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'Like', '%Drivenow_DD%')->sum('amount');
                $transactions[$o]->txn_ia = DriveNowRaveTransaction::where('driver_id', $transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'Like', '%Drivenow_IA%')->sum('amount');

            }
            $total_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
            $overall_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_fees= DriveNowRaveTransaction::where('status',1)->sum('fees');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_dues = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    $today = date('Y-m-d');
                    $present = new \DateTime($today);
                    $next_due = date('Y-m-d', strtotime('next monday', strtotime($today)));

                    $today = date('Y-m-d');
                    $present = new \DateTime($today);

                    $additional_charge = 0;

                    
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Due Payments";
        }

            $vehicles = DriveNowVehicle::where('status', 4)->get();
            $total_driver = OfficialDriver::where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->count();
            $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('daily_drivenow','!=', 1)->where('status', '!=', 1)->count();
            $total_blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();
            $contracts = DriveNowContracts::where('status', 1)->get();


        return view('admin.providers.drivenow_payment_review', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','vehicles','driver_dues','contracts','total_fees','overall_due','total_blocked_drivers','total_driver'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_additional(Request $request){

        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $transactions = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver')->whereHas('official_driver')->where('status', 1)->orderBy('created_at', 'desc')->get();
            $total_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Payments transactions from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else{

            $transactions = DriveNowRaveTransaction::with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('created_at', '>', Carbon::now()->subDays(30)->endOfDay())
                        ->whereHas('official_driver')->where('status', 1)->where('add_charge','!=',0)->orderBy('updated_at', 'desc')->get();
            foreach ($transactions as $key => $transaction) {

                $extra_due = DriveNowExtraPayment::where('official_id', $transaction->official_driver->id)->where('updated_at', '<',$transaction->created_at)->where('status', '!=',1);

                $daily_extra = $extra_due->sum('daily_due');
                $weekly_extra = $extra_due->sum('due');
                $extra_dues = $extra_due->get();
                
                foreach ($extra_dues as $key => $extras) {
                    if($transaction->official_driver->daily_DriveNow == 1){
                        $add_due = $transaction->add_charge * ($extras->daily_due/$daily_extra);
                    }else{
                        $add_due = $transaction->add_charge * ($extras->due/$weekly_extra);
                    }
                        $drivenow_add_due = DriveNowAdditionalTransactions::where('tran_id', $transaction->id)->where('type',$extras->id)->first();
                        if(!$drivenow_add_due){
                            $drivenow_add_due = New DriveNowAdditionalTransactions;
                        }
                        
                        $drivenow_add_due->tran_id = $transaction->id;
                        $drivenow_add_due->driver_id = $transaction->official_driver->driver_id;
                        $drivenow_add_due->official_id = $transaction->official_driver->id;
                        $drivenow_add_due->paid_amount = number_format($transaction->add_charge,2);
                        $drivenow_add_due->type = $extras->id;
                        $drivenow_add_due->amount = number_format($add_due,2);
                        $drivenow_add_due->save();
                        
                }
                                                                
            }
        $drivenow_add = DriveNowAdditionalTransactions::orderBy('tran_id', 'desc')->paginate(300);
        $drivenow_extra = DriveNowExtraPayment::distinct('reason')->select('reason')->get();
        
            $total_due = OfficialDriver::where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::where('status',1)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Due Payment transactions";
        }
        $total_driver = OfficialDriver::where('status', '!=', 1)->count();
        $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->where('status', '!=', 1)->count();
        // dd($transactions);
                // $driver_due = $total_driver - $driver_paid;
        return view('admin.providers.drivenow_additional_due', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','total_add_charge','drivenow_extra'));
        // return view('admin.providers.drivenow_transactions', compact('transactions'));
    }

    public function drivenow_ut_due_payment(Request $request){
        try{
        
        $page = 'List of Untapped DriveNow Drivers';

        $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
        
        $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');


        $credit_pending_transactions = DriveNowRaveTransaction::where('status', 2)->whereIn('official_id',$drivers)->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                // dd($credit_pending_transaction->id);
                $CP = Helper::ConfirmPayment($credit_pending_transaction->id);    
            }

            if(request()->has('filter_date')){
                $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->orderBy('updated_at','desc')->with('transactions')->get();
                $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->sum('amount_due');

                $overall_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', '!=', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('amount');

                $total_add_charge = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('add_charge');

                $total_fees= DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->sum('fees');

                $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                    if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    }
                    $driver_dues = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                    

                $page = "DriveNow (Untapped) Due Payments from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
            }else if(request()->has('f')){
                if($request->f == "blocked"){
                    $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '!=', 0)->where('daily_drivenow','!=', 1)->where('status', '!=', 1)->orderBy('updated_at','desc')->get();
                    $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '!=', 0)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                    $overall_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '!=', 0)->where('status', '!=', 1)->sum('amount_due');
                    $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('amount');
                    $total_add_charge = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('add_charge');
                    $total_fees= DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('fees');
                    $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->count();
                    $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',0)->count();
                    $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',2)->count();
                    $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->get()->count();
                        if(date('D') != 'Tue'){
                            $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                        }else{
                            $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->where('amount_due', '>', 0)->count();
                        }
                        $driver_dues = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->where('amount_due', '>', 0)->count();
                        $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->distinct('driver_id')->count('driver_id');
                        $page = "DriveNow (Untapped) Blocked Drivers";
                }else if($request->f == "due"){
                    if(date('D') != 'Tue'){
                        $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->orderBy('updated_at','desc')->get();
                    }else{
                        $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->orderBy('updated_at','desc')->get();
                    }
                    
                    $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('amount_due', '>', 0)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                    $overall_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('amount_due', '>', 0)->where('status', '!=', 1)->sum('amount_due');
                    $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('amount');
                    $total_fees= DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('fees');
                    $total_add_charge = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('add_charge');
                    $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->count();
                    $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',0)->count();
                    $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',2)->count();
                    $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->get()->count();
                        if(date('D') != 'Tue'){
                            $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                        }else{
                            $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                        }
                        $driver_dues = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                        $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->distinct('driver_id')->count('driver_id');
                        $page = "DriveNow (Untapped) Drivers with Due";
                }
            }else{
                        
                $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->with('vehicle','transactions')->orderBy('updated_at','desc')->get();

                $official_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->with('vehicle')->orderBy('updated_at','desc')->get();
                foreach ($official_drivers as $driver) {
                    // Log::info($driver);
                   $driver->imei_number = $driver->vehicle->imei;
                   $driver->save();
                }
                for ($o=0; $o < count($transactions); $o++) {
                
                    $OldDriverOff = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', '<', Carbon::today())->update(['status' => 1]);
                    $cur_day_off = DriverDayOff::where('driver_id',$transactions[$o]->driver_id)->whereDate('day_off', Carbon::today())->where('status',0)->first();


                    if(!$cur_day_off){
                        $transactions[$o]->day_off = 0;
                        $transactions[$o]->save();
                    }
                    $transactions[$o]->txn = DriveNowTransaction::where('driver_id',$transactions[$o]->driver_id)->whereNotNull('due_date')->count();
                    $transactions[$o]->txn_amt = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('driver_id',$transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
                    $transactions[$o]->txn_adc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('add_charge');
                    $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);


                }
                $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow','!=', 1)->sum('amount_due');
                $overall_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->sum('amount_due');
                $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('amount');
                $total_add_charge = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('add_charge');
                $total_fees= DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('fees');
                $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->count();
                $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',0)->count();
                $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',2)->count();
                $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->get()->count();
                    if(date('D') != 'Tue'){
                        $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                    }else{
                        $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                    }
                    $driver_dues = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->where('amount_due', '>', 0)->count();
                        $today = date('Y-m-d');
                        $present = new \DateTime($today);
                        $next_due = date('Y-m-d', strtotime('next monday', strtotime($today)));

                        $today = date('Y-m-d');
                        $present = new \DateTime($today);

                        $additional_charge = 0;
                        if(date('D') == 'Wed'){
                            for ($i=0; $i < count($official_drivers); $i++) { 
                                $official_driver = OfficialDriver::whereIn('supplier_id', $fleets)->where('driver_id', $official_drivers[$i]->driver_id)->where('status', '!=', 1)->first();
                                $agreement_start_date = new \DateTime($official_driver->agreement_start_date);
                                $additional_charge = 0;
                                $txn_amt = DriveNowRaveTransaction::where('driver_id',$official_driver->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
                                $txn_adc = DriveNowRaveTransaction::where('driver_id',$official_driver->driver_id)->where('status',1)->sum('add_charge');

                                $vehicle_paid = $official_driver->pre_balance + ($txn_amt - $txn_adc);

                                if($vehicle_paid < $official_driver->vehicle_cost){
                                    if($present > $agreement_start_date){
                                        $drivenow_transaction = DriveNowTransaction::where('due_date',$next_due)->where('driver_id', $official_drivers[$i]->driver_id)->first();
                                        $extras = 0;
                                        if($official_driver->extra_pay > 0){
                                            $extras = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('due');
                                        }else{
                                            $official_driver->extra_pay = 0;
                                            DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->update(['status'=>1]);
                                        }
                                        
                                        $tot_due = $official_driver->weekly_payment;
                                        
                                        if(!$drivenow_transaction){
                                            $drivenow_transaction = new DriveNowTransaction;
                                            $drivenow_transaction->due_before = $official_driver->amount_due;
                                            $drivenow_transaction->balance_before = $official_driver->amount_paid;
                                            $official_driver->next_due = $next_due;
                                            $official_driver->break = 0;
                                            $official_driver->amount_due = $official_driver->amount_due + $official_driver->weekly_payment;

                                            $official_driver->balance_weeks = $official_driver->balance_weeks + 1;      
                                            $official_driver->amount_due_add = $official_driver->amount_due_add + $extras;    
                                            $official_driver->save();
                                        }

                                        $drivenow_transaction->driver_id = $official_drivers[$i]->driver_id;
                                        $drivenow_transaction->contract_id = $official_driver->id;
                                        $drivenow_transaction->amount = $official_driver->weekly_payment + $extras;
                                        $drivenow_transaction->due = $official_driver->weekly_payment;
                                        $drivenow_transaction->add_charge = $extras;
                                        $drivenow_transaction->due_date = $next_due;
                                        $drivenow_transaction->status = 0;
                                        $drivenow_transaction->save();


                                        $drivenow_due_transaction = DriveNowTransaction::where('due_date', '<', $next_due)->whereNotNull('due_date')->where('driver_id', $official_driver->driver_id)->where('status',0)->update(['status' => 3]);
                                    }
                                }
                                // else if($present < $agreement_start_date){
                                    
                                //     if($official_driver->initial_amount > 0){

                                //         $drivenow_transaction = DriveNowTransaction::where('due_date',$next_due)->where('driver_id', $official_drivers[$i]->driver_id)->first();
                                    
                                    
                                //         if(!$drivenow_transaction){
                                //             $drivenow_transaction = new DriveNowTransaction;

                                //             $official_driver->next_due = $next_due;
                                //             $official_driver->break = 0;
                                //             $official_driver->amount_due = $official_driver->amount_due + $official_driver->initial_amount;
                                //             $official_driver->save();
                                //             $official_driver->balance_weeks = $official_driver->balance_weeks + 1;          
                                //             $official_driver->save();

                                //         }
                                //             $drivenow_transaction->driver_id = $official_drivers[$i]->driver_id;
                                //             $drivenow_transaction->contract_id = $official_driver->id;
                                //             $drivenow_transaction->amount = $official_driver->initial_amount;
                                //             $drivenow_transaction->due_date = $official_driver->next_due;
                                //             $drivenow_transaction->status = 0;
                                //             $drivenow_transaction->save();
                                        
                                //     }

                                //     $drivenow_due_transaction = DriveNowTransaction::where('due_date', '<', $next_due)->where('driver_id', $official_driver->driver_id)->where('status',0)->update(['status' => 3]);
                                    
                                // }
                            }
                        }
                        

                        
                    $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->distinct('driver_id')->count('driver_id');
                    $page = "DriveNow (Untapped) Due Payments";
            }

            $vehicles = DriveNowVehicle::where('status', 4)->get();
            $total_driver = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('daily_drivenow', '!=', 1)->count();
            $blocked_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '=',1)->where('daily_drivenow','!=', 1)->where('status', '!=', 1)->count();
            $total_blocked_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '=',1)->where('status', '!=', 1)->count();
            $contracts = DriveNowContracts::where('status', 1)->get();


        return view('admin.providers.ut.drivenow_ut_due_payment', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','vehicles','driver_dues','contracts','total_fees','overall_due','total_blocked_drivers','total_driver','total_add_charge'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_ut_transactions(Request $request){
        if($request->has('st')){
            $status = array(0,2,3);
            $tag = 0;
            $title = "DriveNow Untapped Failed / Attempted Transactions";
        }else{
            $status = array(1);
            $tag = 1;
            $title = "DriveNow Untapped Payment Transactions";
        }

        $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
        
        $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');
        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            // $transactions = DriveNowRaveTransaction::whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver')->whereHas('official_driver')->where('status', 1)->orderBy('created_at', 'desc')->get();

            $transactions = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('created_at', '>', Carbon::now()->subDays(30)->endOfDay())
                        ->whereHas('official_driver')->whereIn('status',$status)->where('add_charge','!=',0)->orderBy('updated_at', 'desc')->get();

            $drivenow_extra = DriveNowExtraPayment::whereBetween('created_at',[date($dates[0]), date($dates[1])])->distinct('reason')->select('reason')->get();

            $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->WhereIn('status',$status)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->WhereIn('status',$status)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->Where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->WhereIn('status',$status)->distinct('driver_id')->count('driver_id');
                

            $page = $title . " from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else{

            $transactions = DriveNowRaveTransaction::whereIn('official_id',$drivers)->with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')->where('created_at', '>', Carbon::now()->subDays(30)->endOfDay())
                        ->whereHas('official_driver')->whereIn('status',$status)->orderBy('updated_at', 'desc')->get();
                        // dd($transactions);
            $drivenow_extra = DriveNowExtraPayment::distinct('reason')->select('reason')->get();

            $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->WhereIn('status',$status)->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::whereIn('official_id',$drivers)->WhereIn('status',$status)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->Where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->distinct('driver_id')->count('driver_id');
                $page = $title;
        }
        $total_driver = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->count();
        $blocked_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '=',1)->where('status', '!=', 1)->count();
        
                // $driver_due = $total_driver - $driver_paid;
        return view('admin.providers.ut.drivenow_ut_transactions', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','total_add_charge','drivenow_extra','tag'));
        // return view('admin.providers.drivenow_transactions', compact('transactions'));
    }

    public function drivenow_ut_break(Request $request){
            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
        
            $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');
            $breaks = DriveNowPaymentBreak::whereIn('official_id',$drivers)->orderBy('created_at','desc')->get();
        
        
        return view('admin.providers.ut.drivenow_ut_due_break', compact('breaks'));
    }

    public function drivenow_ut_terminated(Request $request){
        
        $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
        
        $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');

        if(request()->has('filter_date')){
            $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
            $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', 1)->orderBy('updated_at','desc')->get();
            $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->where('slp_ref_id', 'not Like', '%Drivenow_DD%')->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->whereBetween('created_at',[date($dates[0]), date($dates[1])])->where('status',1)->distinct('driver_id')->count('driver_id');
                

            $page = "DriveNow Terminated Drivers from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
        }else{

            $transactions = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', 1)->orderBy('updated_at','desc')->get();
            $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('amount_due', '>', 0)->count();
                }
                // dd($driver_due);
                $driver_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->distinct('driver_id')->count('driver_id');
                $page = "DriveNow Terminated Drivers";
        }
            $total_driver = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', 1)->count();
                // $driver_due = $total_driver - $driver_paid;
        return view('admin.providers.ut.drivenow_ut_terminated', compact('page','transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid'));
    }

    public function drivenow_ut_tracker(Request $request){
        try{

            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
        
            $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');

            if(request()->has('f')){
                if($request->f == "blocked"){
                    $official_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '!=', 0)->where('status', '!=', 1)->orderBy('updated_at','desc')->get();
                    $page = "Drive to Own Tracker - Blocked Drivers";
                }if($request->f == "due"){
                    if(date('D') != 'Tue'){
                        $official_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->orderBy('updated_at','desc')->get();
                    }else{
                        $official_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('amount_due', '>', 0)->orderBy('updated_at','desc')->get();
                    }
                    $page = "Drive to Own Tracker - Default Drivers";
                }
            }else{
                $official_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->with('provider','vehicle')->where('status','!=', 1)->get();
                $page = "Drive to Own Tracker";
            }
            $imeis = '';
            $over = count($official_drivers)-1;
            //Fetching IMEI Number to feed Tro Traker api
            for ($i=0; $i < count($official_drivers); $i++) {  
            
                if($official_drivers[$i]->vehicle){
                    if($official_drivers[$i]->vehicle->imei !=''){
                        $imeis .= str_replace(' ', '',$official_drivers[$i]->vehicle->imei) .",";
                        $official_drivers[$i]->vehicle->imei = str_replace(' ', '',$official_drivers[$i]->vehicle->imei);
                        $official_drivers[$i]->save();
                    }
                   
                }
            }
            $imeis = substr_replace($imeis,"",-1);

            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with actual protract account name";
                $password = "replac with protrack password";
                $signature = md5(md5($password).$time);

                $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                $token_json = curl($token_url);

                $token_details = json_decode($token_json, TRUE);

                $tro_access_token = $token_details['record']['access_token'];
                Setting::set('tro_access_token', $tro_access_token);
                Setting::save();
                Log::info("Tro Access Token Called");
            }

            if($tro_access_token !='' && $imeis !=''){
                $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

                $status_json = curl($status_url);

                $status_details = json_decode($status_json, TRUE);

                $official_drivers = array();
                if($status_details){
                    if($status_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with actual protract account name";
                        $password = "replac with protrack password";
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
                    if($status_details['code']== '10007'){
                        Log::info(json_encode($status_details));
                    }

                    for ($i=0; $i < count($status_details['record']); $i++) { 

                        $official_drivers[$i] = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->where('status','!=', 1)->first();
                        
                        if($official_drivers){
                            $official_driver = OfficialDriver::findOrFail($official_drivers[$i]->id);
                            if($status_details['record'][$i]['oilpowerstatus'] == 0){
                                $official_driver->engine_status = 1;
                                $official_driver->save();
                            }else{
                                $official_driver->engine_status = 0;
                                $official_driver->save();
                            }
                            $official_drivers[$i]->latitude = $status_details['record'][$i]['latitude'];
                            $official_drivers[$i]->longitude = $status_details['record'][$i]['longitude'];
                            $official_drivers[$i]->car_speed = $status_details['record'][$i]['speed'];

                            $official_drivers[$i]->oilpowerstatus = $status_details['record'][$i]['oilpowerstatus'];

                            $official_drivers[$i]->datastatus = $status_details['record'][$i]['datastatus'];

                            $official_drivers[$i]->hearttime = Carbon::createFromTimestamp($status_details['record'][$i]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                        }
                    
                    }
                }
            }

            $total_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->sum('amount');
            $total_tran_suc = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',1)->count();
            $total_tran_fail = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',0)->count();
            $total_tran_pen = DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('status',2)->count();
            $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                }
                $driver_dues = OfficialDriver::whereIn('supplier_id', $fleets)->where('status', '!=', 1)->where('amount_due', '>', 0)->count();
                $blocked_drivers = OfficialDriver::whereIn('supplier_id', $fleets)->where('engine_status', '=',1)->where('status', '!=', 1)->count();


        // dd($official_drivers[40]);
            return view('admin.providers.ut.drivenow_ut_tracker', compact('official_drivers','total_due', 'total_paid', 'total_tran_suc', 'total_tran_fail', 'total_tran_pen', 'total_tran', 'driver_due', 'driver_dues', 'blocked_drivers', 'page'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', 'Server Error!');
        }
    }

    
}

