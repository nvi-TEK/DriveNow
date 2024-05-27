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
use App\DriveNowVehicleRepairHistory;
use App\DriveNowCreditScore;

class DriveNOwResource extends Controller
{
	public function vehicle_list()
    {
        try {
        	
        	$fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
        	$drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');

            $vehicles = DriveNowVehicle::whereIn('fleet_id', $fleets)->get();
            

            $imeis = $n_imeis = '';
            
            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with account name";
                $password = "replace with account password";
                $signature = md5(md5($password).$time);

                $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

                $token_json = curl($token_url);

                $token_details = json_decode($token_json, TRUE);

                $tro_access_token = $token_details['record']['access_token'];
                Setting::set('tro_access_token', $tro_access_token);
                Setting::save();
                
            }            
            $k = $l =0; $veh = array();        
            foreach ($vehicles as $vehicle) {
            	if($vehicle->imei != ''){
            	 $imeis .= $vehicle->imei.",";
            	}
            }
                    
            $vehicle_not_tracked = $l;
            $imeis = substr_replace($imeis,"",-1);
            Log::info($imeis);

            
            $status_url = "http://api.protrack365.com/api/track?access_token=".$tro_access_token."&imeis=".$imeis;

            $status_json = curl($status_url);

            $status_details = json_decode($status_json, TRUE);
            if($status_details){
            	if($status_details['code']== '10012'){
                        $time = Carbon::now()->timestamp;
                        $account = "replace with account name";
                        $password = "replace with account password";
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
            }
            Log::info(json_encode($status_details));


            $online_vehicles = $offline_vehicles = $blocked = $active = $total_vehicles = 0 ;
            $vehicles = array();
            $j = 0;
            for ($i=0; $i < count($status_details['record']); $i++) { 
                Log::info("Before: ". json_encode($status_details['record'][$i]));
                $drivenow_vehicle = DriveNowVehicle::where('imei', $status_details['record'][$i]['imei'])->with('driver')->first();

                if($drivenow_vehicle){

                    $vehicles[$j] = $drivenow_vehicle;
                    $vehicles[$j]->latitude = $status_details['record'][$i]['latitude'];
                    $vehicles[$j]->longitude = $status_details['record'][$i]['longitude'];
                    $vehicles[$j]->car_speed = $status_details['record'][$i]['speed'];
                    $vehicles[$j]->accstatus = $status_details['record'][$i]['oilpowerstatus'];
                    $vehicles[$j]->datastatus = $status_details['record'][$i]['datastatus'];
                    $vehicles[$j]->acctime = round(($status_details['record'][$i]['acctime'])/3600);
                    $vehicles[$j]->hearttime = Carbon::createFromTimestamp($status_details['record'][$i]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                    $vehicles[$j]->gpstime = Carbon::createFromTimestamp($status_details['record'][$i]['gpstime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                    $vehicles[$j]->systemtime = Carbon::createFromTimestamp($status_details['record'][$i]['systemtime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                    $j = $j+1;
                }
                    
                $official_drivers = OfficialDriver::where('imei_number',$status_details['record'][$i]['imei'])->first();
                
                if($official_drivers){
                    $official_driver = OfficialDriver::findOrFail($official_drivers->id);
                    if($status_details['record'][$i]['oilpowerstatus'] == 0){
                        $official_driver->engine_status = 1;
                        $official_driver->save();
                    }else{
                        $official_driver->engine_status = 0;
                        $official_driver->save();
                    }
                }
                if($status_details['record'][$i]['datastatus'] == 2){
                    $online_vehicles += 1;
                }
                if($status_details['record'][$i]['datastatus'] == 4){
                    $offline_vehicles += 1;

                }
                $total_vehicles = count($status_details['record']);

                if($status_details['record'][$i]['oilpowerstatus'] == 1){
                    $active += 1;
                }
                if($status_details['record'][$i]['oilpowerstatus'] == 0){
                    $blocked += 1;
                }
            
            }
            $total_paid = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');
            return view('drivenow.vehicle_list',compact('vehicles','vehicle','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','vehicles','driver_dues','total_fees','overall_due','total_blocked_drivers','total_driver','total_add_charge','online_vehicles', 'offline_vehicles','blocked','total_paid'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function vehicle_profile($id)
    {
        try {
            $vehicle = DriveNowVehicle::where('id', $id)->with('drivenow','driver')->first();

            $d = Carbon::parse(Carbon::now())->diffInDays($vehicle->drivenow->next_due);
            $driver = OfficialDriver::where('id',$vehicle->drivenow->id)->first();

            $transactions = DriveNowRaveTransaction::with('drivenow_transaction')->where('official_id',$vehicle->drivenow->id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->orderBy('created_at', 'desc')->get();

            $txn_amt = DriveNowRaveTransaction::where('driver_id', $driver->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');
            $txn_adc = DriveNowRaveTransaction::where('driver_id', $driver->driver_id)->where('status',1)->sum('add_charge');

            $vehicle_paid = $driver->pre_balance + ($txn_amt - $txn_adc);

            $repairs = DriveNowVehicleRepairHistory::where('car_id',$id)->orderBy('created_at' , 'desc')->get();

            $tro_access_token = Setting::get('tro_access_token','');
            if($tro_access_token == ''){
                $time = Carbon::now()->timestamp;
                $account = "replace with account name";
                $password = "replace with account password";
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
                        $account = "replace with account name";
                        $password = "replace with account password";
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

                    $vehicle->oilpowerstatus = $status_details['record'][0]['oilpowerstatus'];

                    $vehicle->datastatus = $status_details['record'][0]['datastatus'];

                    $vehicle->hearttime = Carbon::createFromTimestamp($status_details['record'][0]['hearttime'], new \DateTimeZone('Europe/London'))->diffForHumans();
                    $vehicle->acctime = $status_details['record'][0]['acctime'];
                    $vehicle->battery = $status_details['record'][0]['battery'];
                        
                    
                    
                }
            }

            $defaults = DriveNowBlockedHistory::with('provider', 'official', 'engine_off')->where('engine_off_reason','Payment Due')->where('official_id',$driver->id)->orderBy('created_at', 'desc')->get();
            // dd($defaults);
            
            return view('drivenow.vehicle_profile',compact('vehicle','d','transactions','driver','vehicle_paid','repairs','defaults'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function driver_list()
    {

        try {
        	
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


        	for ($o=0; $o < count($drivers); $o++) {
                $drivers[$o]->txn_amt = DriveNowRaveTransaction::where('driver_id',$drivers[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');

                $drivers[$o]->txn_adc = DriveNowRaveTransaction::where('driver_id',$drivers[$o]->driver_id)->where('status',1)->sum('add_charge');
                $drivers[$o]->vehicle_paid = $drivers[$o]->pre_balance + ($drivers[$o]->txn_amt - $drivers[$o]->txn_adc);
        	}

            
             return view('drivenow.driver_list',compact('drivers','online_drivers', 'offline_drivers','driver_due','blocked_drivers'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function transactions(){
    	try{

	    	$fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
	        $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');
	    	$transactions = DriveNowRaveTransaction::with('provider', 'drivenow_transaction','official_driver','extra_due','add_split')
	                        ->whereHas('official_driver')->where('status', 1)->whereIn('official_id',$drivers)->orderBy('updated_at', 'desc')->get();
	        $total_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->sum('amount_due');
            $total_paid = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');
            $total_add_charge = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->sum('add_charge');
            $total_tran_suc = DriveNowRaveTransaction::where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->whereIn('official_id',$drivers)->count();
            $total_tran_fail = DriveNowRaveTransaction::where('status',0)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->whereIn('official_id',$drivers)->count();
            $total_tran_pen = DriveNowRaveTransaction::where('status',2)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->whereIn('official_id',$drivers)->count();
            $total_tran =  DriveNowRaveTransaction::whereIn('official_id',$drivers)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->get()->count();
                if(date('D') != 'Tue'){
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->whereRaw('amount_due > weekly_payment')->count();
                }else{
                    $driver_due = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->where('amount_due', '>', 0)->count();
                }
                $driver_paid = DriveNowRaveTransaction::where('status',1)->whereIn('official_id',$drivers)->distinct('driver_id')->count('driver_id');
                
	        $total_driver = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->count();
	        $blocked_drivers = OfficialDriver::where('engine_status', '=',1)->whereIn('supplier_id', $fleets)->where('status', '!=', 1)->count();
	        $due = DriveNowTransaction::orderBy('created_at','desc')->where('due_date','!=','')->first();
	        
	        $d = Carbon::parse(Carbon::now())->diffInDays($due->due_date);
            // dd($transactions[0]);
	        return view('drivenow.transactions',compact('transactions','total_due','total_paid','total_tran_suc','total_tran_fail','total_tran_pen','total_tran','driver_due','driver_paid','blocked_drivers','total_add_charge','due','d','total_driver'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
            
    }
    public function change_password(){
        return view('drivenow.password');
    }
     public function password_update(Request $request)
    {

        $this->validate($request,[
            'old_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        try {

           $Admin = Admin::find(Auth::guard('admin')->user()->id);

            if(password_verify($request->old_password, $Admin->password))
            {
                $Admin->password = bcrypt($request->password);
                $Admin->save();

                return back()->with('flash_success','Password Updated');
            }
        } catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }
    }


    

    public function docs_home(){
        return view('drivenow.docs.home');
    }

    public function docs_list_driver(){
        return view('drivenow.docs.list_drivers');
    }
    public function docs_vehicle_profile(){
        return view('drivenow.docs.vehicle_profile');
    }
    public function docs_transactions(){
        return view('drivenow.docs.payment_transactions');
    }
    public function docs_list_vehicles(){
        return view('drivenow.docs.list_vehicles');
    }

    public function driver_transaction($id)
    {
        try {
            $driver = OfficialDriver::where('id',$id)->first();
            $vehicle = DriveNowVehicle::where('id', $driver->vehicle->id)->with('drivenow','driver')->first();

            $d = Carbon::parse(Carbon::now())->diffInDays($driver->next_due);
            

            // $transactions = DriveNowRaveTransaction::with('drivenow_transaction')
            //                 ->where('official_id',$driver->id)
            //                 ->where('status',1)
            //                 ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->get();
            // foreach ($transactions as $tran) {
            //     $trans = DriveNowRaveTransaction::find($tran->id);
            //     $trans->bill_id= str_replace(' ', '', $tran->bill_id);
            //     $trans->save();
            // }
            $transactions = DriveNowRaveTransaction::with('drivenow_transaction')
                            ->where('official_id',$driver->id)
                            ->where('status',1)
                            ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                            ->orderBy('created_at', 'desc')
                            ->selectRaw('*, sum(amount) as sum')
                            ->groupBy('bill_id')
                            ->get();
            $txn_amt = DriveNowRaveTransaction::where('driver_id', $driver->driver_id)->where('status',1)->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->sum('amount');
            $txn_adc = DriveNowRaveTransaction::where('driver_id', $driver->driver_id)->where('status',1)->sum('add_charge');

            $vehicle_paid = $driver->pre_balance + ($txn_amt - $txn_adc);

            $repairs = DriveNowVehicleRepairHistory::where('car_id',$id)->orderBy('created_at' , 'desc')->get();

            $defaults = DriveNowBlockedHistory::with('provider', 'official', 'engine_off')->where('engine_off_reason','Payment Due')->where('official_id',$driver->id)->orderBy('created_at', 'desc')->get();
            // dd($defaults);
            
            return view('drivenow.driver_transaction',compact('vehicle','d','transactions','driver','vehicle_paid','repairs','defaults'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }
    public function data_transaction(Request $request)
    {
        
        try {
            
            // dd($drivers[3]);
                        
            /*
            $transactions = DriveNowRaveTransaction::with('drivenow_transaction')
                            ->where('status',1)
                            ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')->get();
            foreach ($transactions as $tran) {
                $trans = DriveNowRaveTransaction::find($tran->id);
                $trans->bill_id= str_replace(' ', '', $tran->bill_id);
                $trans->save();
            }
            */
            if(request()->has('filter_date')){
                $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                $transactions = DriveNowRaveTransaction::with('drivenow_transaction')
                            ->where('status',1)
                            ->whereHas('official_driver')
                            ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                            ->orderBy('created_at', 'desc')
                            ->selectRaw('*, sum(amount) as sum')
                            ->groupBy('bill_id')
                            ->whereBetween('created_at',[date($dates[0]), date($dates[1])])
                            ->get();
                $page = "Payments transactions from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]));
            }else{
                $date =  Carbon::today()->subDays(90)->format('Y-m-d');
                $transactions = DriveNowRaveTransaction::with('drivenow_transaction','official_driver')
                            ->whereHas('official_driver')
                            ->where('status',1)
                            ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                            ->orderBy('created_at', 'desc')
                            ->selectRaw('*, sum(amount) as sum')
                            ->groupBy('bill_id')
                            ->whereDate('created_at','>=', $date)
                            ->get();
                $page = 'Payments transactions of last 90 days';
            }
            
            // dd($transactions[10]->official_driver);
            return view('drivenow.report_transaction',compact('transactions','page'));
        } catch (ModelNotFoundException $e) {
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function drivenow_data(Request $request){
        try{
            if($request->has('year')){
                if($request->year < 2022){
                    return back()->with('flash_error', "Warning: Access Restricted to Years Beyond 2022!");
                }else{
                    $year = $request->year;
                }
                
            }else{
                $year = date('Y');
            }
            if($year == date('Y')){
                $start = 1;
                $end = date('m');
            }else{
                $start = 1;
                $end = 12;
            }
            

            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
            $drivers = OfficialDriver::where('status', '!=', 1)->whereIn('supplier_id', $fleets)->pluck('id');
            for($i = $start; $i <=$end; $i++){
                $date_start = Carbon::create($year, $i)->startOfMonth()->format('Y-m-d');
                $date_end = Carbon::create($year, $i)->lastOfMonth()->format('Y-m-d'); 
                // dd($date_end);
                $inv = DriveNowTransaction::whereYear('due_date', $year)
                                ->whereMonth('due_date', $i)
                                ->orderBy('due_date', 'desc')
                                ->whereNotIn('contract_id',$drivers)
                                // ->whereNotNull('due_date')
                                // ->whereNull('skip')
                                ->selectRaw("SUM(pay_score) as ops, SUM(paid_amount) as oia, COUNT(*) as inv, SUM(due) as t_due, SUM(due_before) as t_bdue, SUM(amount) as t_amount, due_date as date")
                                ->first();
                                // dd($inv);
                $txn = DriveNowRaveTransaction::whereMonth('created_at',$i)
                                ->whereYear('created_at',$year)
                                ->orderBy('created_at', 'desc')
                                // ->whereNotNull('bill_id')
                                ->whereNotIn('official_id',$drivers)
                                ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                                
                                ->where('status',1)
                                ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                                ->first();
                $csh = DriveNowRaveTransaction::whereMonth('created_at',$i)
                                ->whereYear('created_at',$year)
                                ->orderBy('created_at', 'desc')
                                ->whereNull('bill_id')
                                ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                                
                                ->where('status',1)
                                ->where('network', 'Eganow')
                                ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                                ->first();
                                // dd($csh);

                                $invoices[$i]['ops'] = $inv->ops;
                                $invoices[$i]['due_date'] = $inv->date;
                                $invoices[$i]['inv'] = $inv->inv;
                                $invoices[$i]['t_amount'] = round($inv->t_amount);
                                $invoices[$i]['t_due'] = round($inv->t_due);
                                $paid_amount = round($txn->total - $txn->extra);
                                $cash_paid = round($csh->total - $csh->extra);
                                $invoices[$i]['oia'] = $paid_amount;
                                
                                // $invoices[$i]['cash'] = $cash_paid;
                                // dd($csh);

                
            }
            // dd($invoices[12]);
            $fleets = SupplierFleet::where('management_fee','!=','')->first();

            $transactions = OfficialDriver::whereYear('agreement_start_date','<=',$year)
                            // ->where('daily_drivenow', '!=', 1)
                            ->where('status', '!=', 1)
                            // ->with('vehicle','transactions','invoice')
                            ->whereNull('supplier_id')
                            // ->where('supplier_id', $fleets->id)
                            ->orderBy('created_at','desc') 
                            ->get();
            Log::info('start: '. Carbon::now());
            
            $ov_cs = array();
            // Log::info('Process start: '. Carbon::now());
            for ($o=0; $o < count($transactions); $o++) {
                $late = $good = $default = 0;
                
                $transactions[$o]->txn = DriveNowTransaction::where('contract_id',$transactions[$o]->id)->whereNotNull('due_date')->count();

                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('official_id',$transactions[$o]->id)->where('status',1)->where('slp_ref_id', 'NOT LIKE', "%DriveNow_D%")->sum('amount');
                if($transactions[$o]->driver_id == 4558){
                    Log::info($transactions[$o]->driver_id." - ".$transactions[$o]->txn_amt);
                } 
                               
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('official_id',$transactions[$o]->id)->where('status',1)->sum('add_charge');
                $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);
                
                $t_ops = $t_aps =  $t_oia =  $t_aia = 0;
                
                
                       
                        $transactions[$o]->c_score = DriveNowCreditScore::where('official_id', $transactions[$o]->id)->where('year', $year)->get();
                        
            
            }
            // dd($transactions);
            Log::info('end: '. Carbon::now());
            return view('drivenow.report', compact('transactions','year','invoices','start','end'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function ut_drivenow_data(Request $request){
        try{
            if($request->has('year')){
                if($request->year < 2022){
                    return back()->with('flash_error', "Warning: Access Restricted to Years Beyond 2022!");
                }else{
                    $year = $request->year;
                }
                
            }else{
                $year = date('Y');
            }
            if($year == date('Y')){
                $start = 1;
                $end = date('m');
            }else{
                $start = 10;
                $end = 12;
            }
            // dd($year);
            //Credit Score Calculation for drivers
            // $drivers = OfficialDriver::orderBy("agreement_start_date", 'desc')
            //                 // ->where('daily_drivenow', '!=', 1)
            //                 // ->whereNull('supplier_id')
            //                 // ->where('status', '!=', 1)
            //                 ->get();
            //                 $j = 0;
            // for ($i=0; $i < count($drivers); $i++) { 
            //     $transactions = DriveNowTransaction::where('contract_id', $drivers[$i]->id)
            //                     // ->where('created_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
            //                     ->orderBy("created_at",'desc')
            //                     ->whereNotNull('due_date')
            //                     // ->groupBy(DB::raw("DATE_FORMAT(created_at,'%d %m %Y')"))
            //                     ->get();
            //                     // dd($transactions);
            //     foreach ($transactions as $transaction) { 

                    
            //         $diff = $paid_date = $tran_id = $paid_amount = $balance_ratio = '';
            //         if($transaction->due != ''){
                        
            //                 $due_amount = $transaction->due + $transaction->due_before; 
            //                 if($due_amount < 0){
            //                     $due_amount = $transaction->due;
            //                 }
                        
            //         }else{
            //             $due_amount = $transaction->amount;
            //         }
                    
            //         $payments = DriveNowRaveTransaction::where('bill_id',$transaction->id)
            //                     ->where('status',1)
            //                     ->orderBy('updated_at', 'desc')
            //                     ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")->get();

            //         Log::info($payments);

            //         // Credit Scrore Calculation for Weekly Invoices
            //         if($payments[0]['total'] != ''){
            //             // Log::info($payments);
            //             $tran_id = $payments[0]['transaction_id'];
                        
            //             if($payments[0]['paid_at'] != ''){
            //                 $invoice_date = new Carbon($transaction->due_date);
            //                 $paid_date = new Carbon($payments[0]['paid_at']);
            //                 $diff = $invoice_date->diffInDays($paid_date,false);  
            //                 $transaction->paid_date = $paid_date;
            //                 $transaction->delay = $diff;
            //                 $transaction->save();
            //             }
                        
                           
            //             $paid_amount = round($payments[0]['total'] - $payments[0]['extra']);
            //             $transaction->paid_amount = round($paid_amount);
            //             $transaction->balance_amount = $due_amount - $paid_amount;
            //             if($due_amount > 0){
            //                 $balance_ratio = ($paid_amount / $due_amount)*100;
            //             }else{
            //                 $balance_ratio = 0;
            //             }
                        
                        
            //             if($balance_ratio >= 100){
            //                 $transaction->balance_score = 4;
            //             }else if($balance_ratio > 75 && $balance_ratio < 100){
            //                 $transaction->balance_score = 3;
            //             }else if($balance_ratio > 35 && $balance_ratio < 75){
            //                 $transaction->balance_score = 2;
            //             }else if($balance_ratio > 1 && $balance_ratio < 35){
            //                 $transaction->balance_score = 1;
            //             }else{
            //                 $transaction->balance_score = 0;
            //             }
            //             $transaction->save();
                        
            //             if($paid_amount >= $due_amount && $diff <= 0){
            //                 $transaction->payment_status = 3;
            //                 $transaction->pay_score = 3;
            //                 $transaction->save();

            //             }else if($paid_amount >= $due_amount && $diff >= 1){
            //                 $transaction->payment_status = 2;
            //                 $transaction->pay_score = 2;
            //                 $transaction->save();
            //             }else{

            //                 $transaction->payment_status = 1;
            //                 $transaction->pay_score = 1;
            //                 $transaction->save(); 
            //             } 
            //         }else{
            //             $daily = DriveNowTransaction::where('contract_id', $drivers[$i]->id)
            //                     ->where('daily_due_date', $transaction->due_date)
            //                     ->first();
            //                     if(count($daily) > 0){
            //                         $j = $j+1;
            //                         Log::info('Weekly Invoice: '. $transaction->id . ' Due Date: '. $transaction->due_date);
            //                         Log::info('Daily invoice: '. $daily->id.  ' Due Date: '. $daily->daily_due_date);
            //                         $transaction->payment_status = 0;
            //                         $transaction->skip=1;
            //                         $transaction->save();
            //                     }
            //             Log::info('coming: '. $transaction->id);
            //                 $transaction->paid_amount = 0;
            //                 $transaction->balance_amount = $due_amount;
            //                 $transaction->payment_status = 1;
            //                 $transaction->pay_score = 0;
            //                 $transaction->balance_score = 0;
            //                 $transaction->save(); 
            //         }

            //         //Credit Score Calculation for Daily Invoices


            //     }
            // }
            
            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
            $drivers = OfficialDriver::whereIn('supplier_id', $fleets)->pluck('id');
            for($i = 1; $i <=12; $i++){
                $date_start = Carbon::create($year, $i)->startOfMonth()->format('Y-m-d');
                $date_end = Carbon::create($year, $i)->lastOfMonth()->format('Y-m-d'); 
                // dd($date_end);
                $inv = DriveNowTransaction::whereMonth('due_date', $i)
                                // ->whereBetween('due_date',[$date_start, $date_end])
                                ->whereYear('due_date',$year)
                                ->orderBy('due_date', 'desc')
                                ->whereIn('contract_id',$drivers)
                                ->whereNotNull('due_date')
                                // ->where('contract_id','>=',178)
                                ->whereNull('skip')
                                ->selectRaw("SUM(pay_score) as ops, SUM(paid_amount) as oia, COUNT(*) as inv, SUM(due) as t_due, SUM(due_before) as t_bdue, SUM(amount) as t_amount, due_date as date")
                                ->first();
                
                $txn = DriveNowRaveTransaction::whereMonth('created_at',$i)
                                ->whereYear('created_at',$year)
                                ->orderBy('created_at', 'desc')
                                ->whereIn('official_id',$drivers)                                ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                                
                                ->where('status',1)
                                ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                                ->first();

                                $invoices[$i]['ops'] = $inv->ops;
                                $invoices[$i]['inv'] = $inv->inv;
                                $invoices[$i]['t_amount'] = round($inv->t_amount);
                                $invoices[$i]['t_due'] = round($inv->t_due);
                                $paid_amount = $txn->total - $txn->extra;
                                $invoices[$i]['oia'] = round($paid_amount);
            

                
            }
            // dd($invoices);
            $fleets = SupplierFleet::where('management_fee','!=','')->first();

            $transactions = OfficialDriver::whereYear('agreement_start_date','<=',$year)
                            ->where('status', '!=', 1)
                            // ->whereYear('agreement_start_date',$year)
                            // ->where('daily_drivenow', '!=', 1)
                            ->whereIn('id',$drivers)
                            // ->with('vehicle','transactions','invoice')
                            // ->whereNull('supplier_id')
                            // ->where('supplier_id', $fleets->id)
                            ->orderBy('created_at','desc') 
                            ->get();
            Log::info('start: '. Carbon::now());
            for ($o=0; $o < count($transactions); $o++) {
            
                $transactions[$o]->txn = DriveNowTransaction::where('driver_id',$transactions[$o]->driver_id)->whereNotNull('due_date')->count();
                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->where('slp_ref_id', 'NOT LIKE', "%DriveNow_D%")->sum('amount');
                if($transactions[$o]->driver_id == 4558){
                    Log::info($transactions[$o]->driver_id." - ".$transactions[$o]->txn_amt);
                }
                $transactions[$o]->c_score = DriveNowCreditScore::where('official_id', $transactions[$o]->id)->where('year', $year)->get();
                
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('driver_id',$transactions[$o]->driver_id)->where('status',1)->sum('add_charge');
                $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);

                $t_ops = $t_aps =  $t_oia =  $t_aia = 0;
                
                // for($i = 1; $i <=12; $i++){
                //     $bills = DriveNowTransaction::where('driver_id',$transactions[$o]->driver_id)
                //                         ->whereYear('due_date', $year)
                //                         ->whereMonth('due_date', $i)
                //                         // ->whereYear('created_at', $year)
                //                         // ->whereMonth('created_at', $i)
                //                         ->whereNull('skip')                                        
                //                         ->selectRaw("SUM(pay_score) as ops, SUM(paid_amount) as oia, COUNT(*) as inv, SUM(due) as t_due, SUM(due_before) as t_bdue, SUM(amount) as t_amount, created_at as date, SUM(add_charge) as extra, payment_status as p_status")       
                //                         ->orderBy('created_at', 'desc')
                //                         ->first();
                //     $b = DriveNowTransaction::where('driver_id',$transactions[$o]->driver_id)
                //                         ->whereYear('due_date', $year)
                //                         ->whereMonth('due_date', $i)
                //                         ->whereYear('created_at', $year)
                //                         ->whereMonth('created_at', $i)
                //                         ->orderBy('created_at', 'desc')
                //                         ->whereNull('skip')->first();          
                                        

                //     $b_txn = DriveNowRaveTransaction::whereMonth('created_at',$i)
                //                         ->where('driver_id',$transactions[$o]->driver_id)
                //                         ->whereYear('created_at',$year)
                //                         ->orderBy('created_at', 'desc')
                //                         // ->whereNotNull('bill_id')
                //                         ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                //                         
                //                         ->where('status',1)
                //                         ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                //                         ->first();

                //                         $bil = array();
                //     $bill[$i]['ops'] = $bills->ops;
                //     $bill[$i]['inv'] = $bills->inv;
                //     $aia = round($bills->t_amount - $bills->extra);
                //     $bill[$i]['aia'] = $aia;
                //     $aps = $bills->inv * 3;
                //     $bill[$i]['aps'] =$aps;
                //     $paid_amount = round($b_txn->total - $b_txn->extra);
                //     $bill[$i]['oia'] = $paid_amount;
                //     if(count($b) >0){
                //         $bill[$i]['payment_status'] = $b->payment_status;
                //         $bill[$i]['date'] = $b->due_date;
                //     }else{
                //         $bill[$i]['payment_status'] = 0;
                //         $bill[$i]['date'] = '';
                //     }
                    
                    
                //     if($aps > 0){
                //         $cs = round(($bills->ops / $aps) *100);
                //     }else{
                //         $cs = 0;
                //     }
                //     if($aia > 0){
                //         $ps = round(($paid_amount / $aia) *100);
                //     }else{
                //         $ps = 0;
                //     }
                //     $bill[$i]['c_score'] = $cs;
                //     $bill[$i]['p_score'] = $ps;                    
                //     $t_ops = $t_ops + $bills->ops;
                //     $t_aps = $t_aps + $aps;
                //     $t_oia = $t_oia + $paid_amount;
                //     $t_aia = $t_aia + $aia;
                // }
                //         if($t_aps > 0){
                //             $t_cs = round(($t_ops / $t_aps)*100);
                //         }else{
                //             $t_cs = 0;
                //         }
                //         if($t_aia > 0){
                //             $t_ps = round(($t_oia / $t_aia)*100);
                //         }else{
                //             $t_ps = 0;
                //         }
                //         $transactions[$o]->bill=$bill;
                //         $transactions[$o]->t_ops=$t_ops;
                //         $transactions[$o]->t_cs = $t_cs;
                //         $transactions[$o]->t_ps = $t_ps;
                //         $transactions[$o]->t_aps=$t_aps;
                //         $transactions[$o]->t_oia=$t_oia;
                //         $transactions[$o]->t_aia=$t_aia;
                        // dd($transactions[$o]);
                        // dd($transactions[$o]->t_oia);
                        // Log::info('Process End: '.$o.' - '. Carbon::now());




            }
            Log::info('End: '. Carbon::now());
            // dd($transactions[11]);
            return view('drivenow.ut_report', compact('transactions','year','invoices','start','end'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function asset_data(Request $request){
        try{
            if($request->has('year')){
                if($request->year < 2022){
                    return back()->with('flash_error', "Warning: Access Restricted to Years Beyond 2022!");
                }else{
                    $year = $request->year;
                }
                
            }else{
                $year = date('Y');
            }
            if($year == date('Y')){
                $start = 1;
                $end = date('m');
            }else{
                $start = 1;
                $end = 12;
            }
            
            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
            $drivers = OfficialDriver::whereIn('supplier_id', $fleets)->pluck('id');
            for($i = $start; $i <= $end; $i++){
                $date_start = Carbon::create($year, $i)->startOfMonth()->format('Y-m-d');
                $date_end = Carbon::create($year, $i)->lastOfMonth()->format('Y-m-d'); 

                // dd($date_end);
                $inv = DriveNowTransaction::whereMonth('due_date', $i)
                                // ->whereBetween('due_date',[$date_start, $date_end])
                                ->whereYear('due_date',$year)
                                ->orderBy('due_date', 'desc')
                                ->whereNotIn('contract_id',$drivers)
                                ->whereNotNull('due_date')
                                // ->where('contract_id','>=',178)
                                // ->whereNull('skip')
                                ->selectRaw("SUM(pay_score) as ops, SUM(paid_amount) as oia, COUNT(*) as inv, SUM(due) as t_due, SUM(due_before) as t_bdue, SUM(amount) as t_amount, due_date as date")
                                ->first();
                
                $txn = DriveNowRaveTransaction::whereMonth('created_at',$i)
                                ->whereYear('created_at',$year)
                                ->orderBy('created_at', 'desc')
                                ->whereNotIn('official_id',$drivers)   
                                ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                                
                                ->where('status',1)
                                ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                                ->first();


                                $invoices[$i]['ops'] = $inv->ops;
                                $invoices[$i]['inv'] = $inv->inv;
                                $invoices[$i]['t_amount'] = round($inv->t_amount);
                                $invoices[$i]['t_due'] = round($inv->t_due);
                                $paid_amount = $txn->total - $txn->extra;
                                $invoices[$i]['oia'] = round($paid_amount);
                                $invoices[$i]['act'] = $inv->oia;
            }
            // dd($invoices[2]);
            $transactions = DriveNowVehicle::whereYear('created_at','<=',$year)
                            ->whereNotIn('status', [6,8])
                            ->whereNotIn('fleet_id',$fleets)
                            ->orderBy('created_at','desc') 
                            ->get();
                            // dd($transactions);
            Log::info('start: '. Carbon::now());
            for ($o=0; $o < count($transactions); $o++) {
            
                $transactions[$o]->txn = DriveNowTransaction::where('contract_id',$transactions[$o]->official_id)->whereNotNull('due_date')->count();
                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('official_id',$transactions[$o]->official_id)->where('status',1)->where('slp_ref_id', 'NOT LIKE', "%DriveNow_D%")->sum('amount');
                if($transactions[$o]->driver_id == 4558){
                    Log::info($transactions[$o]->driver_id." - ".$transactions[$o]->txn_amt);
                }
                for($i=$start; $i<=$end; $i++){
                                        $c_score = DriveNowCreditScore::where('vehicle_id', $transactions[$o]->id)->where('year', $year)->where('month', $i)
                                        ->selectRaw("SUM(ops) as ops, SUM(aps) as aps, SUM(oia) as oia, SUM(aia) as aia, SUM(p_score) as p_score, SUM(c_score) as c_score, month, year, vehicle_id,official_id")
                                        ->groupBy('vehicle_id')
                                        ->first();

                    if($c_score){
                        $data[$i]['ops'] = $c_score->ops;
                        $data[$i]['aps'] = $c_score->aps;
                        $data[$i]['oia'] = $c_score->oia;
                        $data[$i]['aia'] = $c_score->aia;
                        $data[$i]['c_score'] = $c_score->c_score;
                        $data[$i]['p_score'] = $c_score->p_score;
                        $data[$i]['month'] = $c_score->month;
                        $data[$i]['year'] = $c_score->year;
                    }else{
                        $data[$i]['ops'] = 0;
                        $data[$i]['aps'] = 0;
                        $data[$i]['oia'] = 0;
                        $data[$i]['aia'] = 0;
                        $data[$i]['c_score'] = 0;
                        $data[$i]['p_score'] = 0;
                        $data[$i]['month'] = $i;
                        $data[$i]['year'] = $year;
                    }
                    

                }
                $transactions[$o]->c_score = $data;
                // if($transactions[$o]->id == 120){
                //     dd($transactions[$o]->c_score);
                // }
                Log::info($transactions[$o]->c_score);
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('official_id',$transactions[$o]->official_id)->where('status',1)->sum('add_charge');
                $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);

                $t_ops = $t_aps =  $t_oia =  $t_aia = 0;
                
            }
            // dd($transactions[10]->c_score[10]['month']);
            Log::info('End: '. Carbon::now());
            
            return view('drivenow.asset_report', compact('transactions','year','invoices','start','end'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }
    public function ut_asset_data(Request $request){
        try{
            if($request->has('year')){
                if($request->year < 2022){
                    return back()->with('flash_error', "Warning: Access Restricted to Years Beyond 2022!");
                }else{
                    $year = $request->year;
                }
                
            }else{
                $year = date('Y');
            }
            if($year == date('Y')){
                $start = 1;
                $end = date('m');
            }else{
                $start = 10;
                $end = 12;
            }
            
            $fleets = SupplierFleet::where('management_fee','!=','')->get()->pluck('id');
            $drivers = OfficialDriver::whereIn('supplier_id', $fleets)->pluck('id');
            for($i = $start; $i <=$end; $i++){
                $date_start = Carbon::create($year, $i)->startOfMonth()->format('Y-m-d');
                $date_end = Carbon::create($year, $i)->lastOfMonth()->format('Y-m-d'); 
                // dd($date_end);
                $inv = DriveNowTransaction::whereMonth('due_date', $i)
                                ->whereBetween('due_date',[$date_start, $date_end])
                                ->whereYear('due_date',$year)
                                ->orderBy('due_date', 'desc')
                                ->whereIn('contract_id',$drivers)
                                ->whereNotNull('due_date')
                                // ->where('contract_id','>=',178)
                                ->whereNull('skip')
                                ->selectRaw("SUM(pay_score) as ops, SUM(paid_amount) as oia, COUNT(*) as inv, SUM(due) as t_due, SUM(due_before) as t_bdue, SUM(amount) as t_amount, due_date as date")
                                ->first();
                
                $txn = DriveNowRaveTransaction::whereMonth('created_at',$i)
                                ->whereYear('created_at',$year)
                                ->orderBy('created_at', 'desc')
                                ->whereIn('official_id',$drivers)   
                                ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                                
                                ->where('status',1)
                                ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                                ->first();


                                $invoices[$i]['ops'] = $inv->ops;
                                $invoices[$i]['inv'] = $inv->inv;
                                $invoices[$i]['t_amount'] = round($inv->t_amount);
                                $invoices[$i]['t_due'] = round($inv->t_due);
                                $paid_amount = $txn->total - $txn->extra;
                                $invoices[$i]['oia'] = round($paid_amount);
            }
            
            $transactions = DriveNowVehicle::whereYear('created_at','<=',$year)
                            // ->where('status', '!=', 6)
                            ->whereNotIn('status', [6,8])
                            ->whereIn('fleet_id',$fleets)
                            ->orderBy('created_at','desc') 
                            ->get();
            Log::info('start: '. Carbon::now());
            for ($o=0; $o < count($transactions); $o++) {
            
                $transactions[$o]->txn = DriveNowTransaction::where('contract_id',$transactions[$o]->official_id)->whereNotNull('due_date')->count();
                $transactions[$o]->txn_amt = DriveNowRaveTransaction::where('official_id',$transactions[$o]->official_id)->where('status',1)->where('slp_ref_id', 'NOT LIKE', "%DriveNow_D%")->sum('amount');
                if($transactions[$o]->driver_id == 4558){
                    Log::info($transactions[$o]->driver_id." - ".$transactions[$o]->txn_amt);
                }
                for($i=$start; $i<=$end; $i++){
                    $c_score = DriveNowCreditScore::where('vehicle_id', $transactions[$o]->id)->where('year', $year)->where('month', $i)
                                        ->selectRaw("SUM(ops) as ops, SUM(aps) as aps, SUM(oia) as oia, SUM(aia) as aia, SUM(p_score) as p_score, SUM(c_score) as c_score, month, year, vehicle_id,official_id")
                                        ->groupBy('vehicle_id')
                                        ->first();
                    if($c_score){
                        $data[$i]['ops'] = $c_score->ops;
                        $data[$i]['aps'] = $c_score->aps;
                        $data[$i]['oia'] = $c_score->oia;
                        $data[$i]['aia'] = $c_score->aia;
                        $data[$i]['c_score'] = $c_score->c_score;
                        $data[$i]['p_score'] = $c_score->p_score;
                        $data[$i]['month'] = $c_score->month;
                        $data[$i]['year'] = $c_score->year;
                    }else{
                        $data[$i]['ops'] = 0;
                        $data[$i]['aps'] = 0;
                        $data[$i]['oia'] = 0;
                        $data[$i]['aia'] = 0;
                        $data[$i]['c_score'] = 0;
                        $data[$i]['p_score'] = 0;
                        $data[$i]['month'] = $i;
                        $data[$i]['year'] = $year;
                    }
                    

                }
                $transactions[$o]->c_score = $data;
                // if($transactions[$o]->id == 120){
                //     dd($transactions[$o]->c_score);
                // }
                Log::info($transactions[$o]->c_score);
                $transactions[$o]->txn_adc = DriveNowRaveTransaction::where('official_id',$transactions[$o]->official_id)->where('status',1)->sum('add_charge');
                $transactions[$o]->vehicle_paid = $transactions[$o]->pre_balance + ($transactions[$o]->txn_amt - $transactions[$o]->txn_adc);

                $t_ops = $t_aps =  $t_oia =  $t_aia = 0;
            }
            // dd($transactions[10]->c_score[10]['month']);
            Log::info('End: '. Carbon::now());
            
            return view('drivenow.ut_asset_report', compact('transactions','year','invoices','start','end'));
        } catch (ModelNotFoundException $e) {
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }

    public function generate_token(Request $request){
        try {

                $client = new Client(['http_errors' => false]);
                $url ="https://domain-name/api/provider/oauth/token";
                $headers = [
                    'Content-Type' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest'
                ];
                $body = [
                            "email" =>"davidk@untapped-global.com",
                            "password" =>"123456",
                            "device_type" =>"ios",
                            "device_id" =>"81325728a451944dssd",
                            "device_token" =>"sdsdsddfdfdfsfasdfdsfdsf"
                        ];            
                $res = $client->post($url, [
                    'headers' => $headers,
                    'body' => json_encode($body),
                ]);
                $subaccount = json_decode($res->getBody(), true);
                $token = $subaccount['data']['access_token'];
                Setting::set('ut_token', $token);
                Setting::save();
                $data['token'] = $token;
                $data['message'] = "New API Key Generated!";
                return $data;
                // return back()->with('flash_success', "New API Key Generated!");
                // return view('drivenow.password'); 
            
        } catch (Exception $e) {
            Log::info($e);
            return back()->with('flash_error', "Something Went Wrong! Contact Eganow Admin.");        }
    }

    public function calculate_credit_score(Request $request){
        try {
            // $year = date('Y'); 
            // $month = 01;
            // if($year == date('Y')){
            //     $start = 1;
            //     $end = date('m');
            // }else{
            //     $start = 1;
            //     $end = 12;
            // }
            $year = 2024;
            $month = 3;
        // Credit Score Calculation for drivers
            $drivers = OfficialDriver::orderBy("agreement_start_date", 'desc')
                            // ->where('daily_drivenow', '!=', 1)
                            // ->whereNull('supplier_id')
                            // ->where('status', '!=', 1)
                            ->get();
                            $j = 0;
            for ($i=0; $i < count($drivers); $i++) { 
                $transactions = DriveNowTransaction::where('contract_id', $drivers[$i]->id)
                                ->whereMonth('due_date', $month)
                                ->whereYear('due_date',$year)
                                ->orderBy('due_date', 'desc')
                                ->get();
                                
                foreach ($transactions as $transaction) { 

                    $diff = $paid_date = $tran_id = '';
                    $paid_amount = $balance_ratio = 0;
                    if($transaction->due != ''){
                        
                            $due_amount = $transaction->due + $transaction->daily_due_before; 
                            if($due_amount < 0){
                                $due_amount = $transaction->due;
                            }
                        
                    }else{
                        $due_amount = $transaction->amount;
                    }
                    
                    $payments = DriveNowRaveTransaction::where('bill_id',$transaction->id)
                                ->where('status',1)
                                ->orderBy('updated_at', 'desc')
                                ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")->get();

                    Log::info($payments);

                    // Credit Scrore Calculation for Weekly Invoices
                    if($payments[0]['total'] != ''){

                        $tran_id = $payments[0]['transaction_id'];
                        
                        if($payments[0]['paid_at'] != ''){
                            $invoice_date = new Carbon($transaction->daily_due_date);
                            $paid_date = new Carbon($payments[0]['paid_at']);
                            $diff = $invoice_date->diffInDays($paid_date,false);  
                            $transaction->paid_date = $paid_date;
                            $transaction->delay = $diff;
                            $transaction->save();
                        }
                        $paid_amount = round($payments[0]['total'] - $payments[0]['extra']);
                        $transaction->paid_amount = round($paid_amount);
                        $transaction->balance_amount = $due_amount - $paid_amount;

                        if($due_amount > 0){
                            $balance_ratio = ($paid_amount / $due_amount)*100;
                        }else{
                            $balance_ratio = 0;
                        } 
                        
                        if($balance_ratio >= 100){
                            $transaction->balance_score = 4;
                        }else if($balance_ratio > 75 && $balance_ratio < 100){
                            $transaction->balance_score = 3;
                        }else if($balance_ratio > 35 && $balance_ratio < 75){
                            $transaction->balance_score = 2;
                        }else if($balance_ratio > 1 && $balance_ratio < 35){
                            $transaction->balance_score = 1;
                        }else{
                            $transaction->balance_score = 0;
                        }
                        $transaction->save();
                        
                        if($paid_amount >= $due_amount && $diff <= 1){
                            $transaction->payment_status = 3;
                            $transaction->pay_score = 3;
                            $transaction->save();

                        }else if($paid_amount >= $due_amount && $diff >= 2){
                            $transaction->payment_status = 2;
                            $transaction->pay_score = 2;
                            $transaction->save();
                        }else{

                            $transaction->payment_status = 1;
                            $transaction->pay_score = 1;
                            $transaction->save(); 
                        } 
                    }else{
                        // Log::info('coming: '. $transaction->id);
                        //      $daily = DriveNowTransaction::where('contract_id', $drivers[$i]->id)
                        //         ->where('daily_due_date', $transaction->due_date)
                        //         ->whereNotNull('daily_due_date')
                        //         ->first();
                                
                        //     if(count($daily) > 0){
                        //         $j = $j+1;
                        //         Log::info('Weekly Invoice: '. $transaction->id . ' Due Date: '. $transaction->due_date);
                        //         Log::info('Daily invoice: '. $daily->id.  ' Due Date: '. $daily->daily_due_date);
                        //         $transaction->payment_status = 0;
                        //         $transaction->skip=1;
                        //         $transaction->save();
                        //     }
                            $transaction->paid_amount = 0;
                            $transaction->balance_amount = $due_amount;
                            $transaction->payment_status = 1;
                            $transaction->pay_score = 0;
                            $transaction->balance_score = 0;
                            $transaction->save(); 
                    }
                    
                }
                $bills = DriveNowTransaction::where('contract_id',$drivers[$i]->id)
                                        ->whereYear('due_date', $year)
                                        ->whereMonth('due_date', $month)
                                        // ->orWhere('daily_due_date','like',$q)
                                        // ->orwhereYear('daily_due_date', $year)
                                        // ->whereYear('created_at', $year)
                                        // ->whereMonth('created_at', $month)
                                        //->whereNull('skip')                                        
                                        ->selectRaw("SUM(pay_score) as ops, SUM(paid_amount) as oia, COUNT(*) as inv, SUM(due) as t_due, SUM(due_before) as t_bdue, SUM(amount) as t_amount, created_at as date, SUM(add_charge) as extra, payment_status as p_status")       
                                        ->orderBy('created_at', 'desc')
                                        ->first();
                $b = DriveNowTransaction::where('contract_id',$drivers[$i]->id)
                                    ->whereYear('due_date', $year)
                                    ->whereMonth('due_date', $month)
                                    ->whereYear('created_at', $year)
                                    ->whereMonth('created_at', $month)
                                    ->orderBy('created_at', 'desc')
                                    ->whereNull('skip')->first();          
                                    

                $b_txn = DriveNowRaveTransaction::whereMonth('created_at',$month)
                                    ->where('official_id',$drivers[$i]->id)
                                    ->whereYear('created_at',$year)
                                    ->orderBy('created_at', 'desc')
                                    // ->whereNotNull('bill_id')
                                    ->where('slp_ref_id', 'not Like', '%DriveNow_DD%')
                                    
                                    ->where('status',1)
                                    ->selectRaw("sum(amount) as total, sum(add_charge) as extra, created_at as paid_at, id as transaction_id")
                                    ->first();
                                        
                    $aia = round($bills->t_amount - $bills->extra);
                    
                    $aps = $bills->inv * 3;
                    
                    $oia = round($b_txn->total - $b_txn->extra);
                    
                    if($aps > 0){
                        $cs = round(($bills->ops / $aps) *100);
                    }else{
                        $cs = 0;
                    }
                    if($aia > 0){
                        $ps = round(($oia / $aia) *100);
                    }else{
                        $ps = 0;
                    }
                    // Update credit score 
                        $l = DriveNowTransaction::where('contract_id',$drivers[$i]->id)
                                        ->whereYear('due_date', $year)
                                        ->whereMonth('due_date', $month)
                                        ->count();
                        $credit_score = DriveNowCreditScore::where('month', $month)->where('year', $year)->where('official_id',$drivers[$i]->id)->first();
                        if(!$credit_score){
                            $credit_score = new DriveNowCreditScore;
                        }

                        if($l > 0){
                            
                            $credit_score->driver_id = $drivers[$i]->driver_id;
                            $credit_score->official_id = $drivers[$i]->id;
                            $credit_score->vehicle_id = $drivers[$i]->vehicle_id;
                            $credit_score->oia = $oia;
                            $credit_score->aia = $aia;
                            $credit_score->ops = $bills->ops;
                            $credit_score->aps = $aps;
                            $credit_score->c_score = $cs;
                            $credit_score->p_score = $ps;
                            $credit_score->month = $month;
                            $credit_score->year = $year;
                            $credit_score->save();
                        }else{
                            $credit_score->driver_id = $drivers[$i]->driver_id;
                            $credit_score->official_id = $drivers[$i]->id;
                            $credit_score->vehicle_id = $drivers[$i]->vehicle_id;
                            $credit_score->oia = 0;
                            $credit_score->aia = 0;
                            $credit_score->ops = 0;
                            $credit_score->aps = 0;
                            $credit_score->c_score = 0;
                            $credit_score->p_score = 0;
                            $credit_score->month = $month;
                            $credit_score->year = $year;
                            $credit_score->save();
                        }
            }
            
            return back()->with('flash_success', "Credit Score Calculated successfully!");
            
        } catch (Exception $e) {
            Log::info($e);
               return back()->with('flash_error', "Something Went Wrong! Contact Eganow Admin.");
        }
        
    }
    
}

?>