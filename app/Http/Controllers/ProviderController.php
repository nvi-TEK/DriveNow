<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRequests;
use App\Provider;
use App\RequestFilter;
use App\OfficialDriver;
use App\DriveNowTransaction;
use App\DriveNowRaveTransaction;
use App\DriverDayOff;
use App\DriverActivity;
use App\DriveNowContracts;
use App\DriverContracts;
use Carbon\Carbon;
use Setting;
use Auth;
use Log;
use DB;
use App\Http\Controllers\ProviderResources\TripController;
use Paystack;// Paystack package
use App\DriveNowExtraPayment;
use App\DriveNowAdditionalTransactions;
use App\Helpers\Helper;


class ProviderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->middleware('provider');  
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $Provider = Provider::where('id',\Auth::guard('provider')->user()->id)->first();
        if($Provider->official_drivers == 1 ){
            if($Provider->agreed !=1){
                return redirect()->route('provider.agreement',$Provider->id);
            }else{
                return redirect()->route('provider.drivenow');
            }
            
        }
        
        return view('provider.index');
    }

    public function agreement()
    {

        $Provider = OfficialDriver::where('driver_id', \Auth::guard('provider')->user()->id)->where('status', '!=', 1)->first();

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

    public function agree(Request $request, $id)
    {

        $Provider = Provider::where('id', $id)->first();

        if($request->has('contract_id')){
            $driver_contract = DriverContracts::where('driver_id',$Provider->id)->where('contract_id', $request->contract_id)->where('status',0)->first();

            if($driver_contract){
                $driver_contract->status = 1;
                $driver_contract->agreed_on = Carbon::now();
                $driver_contract->save();
            }
        }

        $Provider->agreed = 1;
        $Provider->agreed_on = Carbon::now();
        $Provider->save();

        $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
        $official_driver->agreed = 1;
        $official_driver->agreed_on = Carbon::now();
        $official_driver->save();
        

        return redirect()->route('provider.drivenow');
            
    }

    public function drivenow(Request $request)
    {
        $Provider = Provider::where('id',\Auth::guard('provider')->user()->id)->first();
        $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
        $global_engine = Setting::get('global_engine', 0);
        $transact = DriveNowTransaction::where('driver_id', $Provider->id)->where('status', 0)->orderBy('updated_at', 'desc')->first();
        $missed = '';
        // $missed = DriveNowTransaction::where('driver_id',$Provider->id)->where('status', 3)->first();

        $code = rand(1000, 9999);
        $name = substr($Provider->first_name, 0, 2);
        $reference = "AWT".$code.$name;
        
        $credit_pending_transactions = DriveNowRaveTransaction::where('driver_id', $Provider->id)->whereIn('status', [2,3])->where('created_at', '>=', Carbon::now()->subDays(2)->toDateTimeString())->orderBy('created_at', 'desc')->get();

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                 $CP = Helper::ConfirmPayment($credit_pending_transaction->id);
            }
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
            $transactions = DriveNowRaveTransaction::where('driver_id', $Provider->id)->where('status', '!=', 3)->orderBy('created_at', 'desc')->get();

            $activities = array();
            for($i = 30; $i >= 0; $i--)
            {
                if($i == 0){
                    $date = date("Y-m-d");
                }else{
                    $date = date("Y-m-d", strtotime("-$i days"));
                }
                Log::info($date);

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
            $extras = $due_daily_conversion = array();
            $due_daily_conversion = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)
                // ->where('reason', '=','Pending Due')
                ->first();
                $extras = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->where('reason', '!=','Pending Due')
                ->get();
                $daily_due = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('daily_due');
                $daily_extra = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('daily_due');
                $weekly_extra = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('due');
                $total_extra = DriveNowExtraPayment::where('official_id', $official_driver->id)->where('status','!=',1)->sum('total');

            $total_paid_transaction = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->where('slp_ref_id', 'not like', 'DriveNow_D%')->sum('amount');
            $total_add_transaction = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->sum('add_charge');
            $vehicle_paid = $official_driver->pre_balance + ($total_paid_transaction - $total_add_transaction);

            if((int)$vehicle_paid > (int)$official_driver->vehicle_cost){

                $vehicle_paid = $official_driver->vehicle_cost;
            }
            $contract_date = $official_driver->agreement_start_date;
            $date = Carbon::now();

            $completed_weeks = $date->diffInWeeks($contract_date);
            if($completed_weeks > $official_driver->contract_length){
                $completed_weeks = $official_driver->contract_length;
            }
            $official_driver->completed_weeks = $completed_weeks;
            $last_payment = DriveNowRaveTransaction::where('official_id',$official_driver->id)->where('status',1)->where('network', '!=', 'Eganow')->orderBy('created_at','desc')->first();
            

            return view('provider.drive_own', compact('official_driver','Provider','transactions', 'missed', 'trans_id', 'transact','activities','revoke','extras','due_daily_conversion','daily_due','daily_extra','weekly_extra','total_extra','total_add_transaction','vehicle_paid','last_payment'));
           
        
             
    }

    public function drivenow_payment(Request $request)
    {
        $Provider = Provider::where('id',\Auth::guard('provider')->user()->id)->first();
        $transaction = DriveNowTransaction::where('driver_id', $Provider->id)->where('status', 0)->orderBy('updated_at', 'desc')->first();
        $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
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

        return view('provider.pay', compact('official_driver','Provider','transaction','trans_id'));
            
    }

    public function drivenow_missed_payment(Request $request, $id)
    {
        $Provider = Provider::where('id',\Auth::guard('provider')->user()->id)->first();
        $transaction = DriveNowTransaction::where('driver_id', $Provider->id)->where('id', $id)->first();
        $official_driver = OfficialDriver::where('driver_id', $Provider->id)->where('status', '!=', 1)->first();
        
        return view('provider.pay', compact('official_driver','Provider','transaction'));
            
    }


    public function drivenow_paynow(Request $request)
    {
        $Provider = Provider::where('id',$request->driver_id)->first();
        $official_driver = OfficialDriver::where('id', $request->official_id)->where('status', '!=', 1)->first();
        $bill = '';
        if($request->has('bill_id')){
            $bill = DriveNowTransaction::where('id', $request->bill_id)->first();
        }
        

        try{
            $User = Provider::find(Auth::user()->id);
            
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
                $amount = (int)$request->amount;
                
                //SlydePay Send Invoice and Confirm Payment
                if($request->has('payment_mode') && $request->payment_mode == "MOBILE"){
                    try{
                        $client = new \GuzzleHttp\Client();
                        $invoice_url = "https://posapi.usebillbox.com/webpos/payNow";
                        $headers = ['Content-Type' => 'application/json', "appId" => "e489fa8d-63cc-9d02-545f-8f6f81a3ceec"];

                        $res = $client->post($invoice_url, [ 
                            'headers' => $headers,
                            'json' => ["requestId"=> $req_id,
                                        "appReference"=> "replace with original",
                                        "secret"=> "replace with actual secret code",
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
                           return view('provider.payment_failed'); 
                        }
                        $rave_transactions = new DriveNowRaveTransaction;
                        $rave_transactions->driver_id = $Provider->id;
                        $rave_transactions->official_id = $official_driver->id;
                        if($bill != ''){
                            $rave_transactions->bill_id = $bill->id;
                            $rave_transactions->due = $bill->due;
                            $rave_transactions->add_charge = $bill->add_charge;
                        }
                        
                        $rave_transactions->reference_id = $req_id;
                        $rave_transactions->slp_ref_id = $trans_id;
                        $rave_transactions->network = $request->network;
                        $rave_transactions->amount = number_format($request->amount,2);
                        $rave_transactions->status = 2;
                        $rave_transactions->save();
                        $network = $request->network;
                        return view('provider.payment_success', compact('network'));
                    }catch(\GuzzleHttp\Exception\RequestException $e){
Log::info($e);
                        if($e->getResponse()->getStatusCode() == '404' || $e->getResponse()->getStatusCode() == '500'){
                            return view('provider.payment_failed');
                        }
                    } 
                }
                    

        } catch(Exception $e) { 
            Log:info($e);
            return view('provider.payment_failed');
        }
            
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function incoming(Request $request) {
        $API = new TripController(\Auth::guard('provider')->user());
        return $API->index($request);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function accept(Request $request, $id) {
        $API = new TripController(\Auth::guard('provider')->user());
        return $API->accept($request, $id);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function reject($id)
    {
        return (new TripController())->destroy($id);
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id) {
        $API = new TripController(\Auth::guard('provider')->user());
        $API->update($request, $id);
        return back();
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function rating(Request $request, $id) {
        $API = new TripController(\Auth::guard('provider')->user());
        return $API->rate($request, $id);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function earnings()
    {

        $provider = Provider::where('id',\Auth::guard('provider')->user()->id)
                    ->with('service','accepted','cancelled')
                    ->get();

        $weekly = UserRequests::where('provider_id',\Auth::guard('provider')->user()->id)
                    ->with('payment')
                    ->where('created_at', '>=', Carbon::now()->subWeekdays(7))
                    ->get();

        $today = UserRequests::where('provider_id',\Auth::guard('provider')->user()->id)
                    ->where('created_at', '>=', Carbon::today())
                    ->count();

        $fully = UserRequests::where('provider_id',\Auth::guard('provider')->user()->id)
                    ->with('payment','service_type')
                    ->get();

        return view('provider.payment.earnings',compact('provider','weekly','fully','today'));
    }

    /**
     * available.
     *
     * @return \Illuminate\Http\Response
     */
    public function available(Request $request)
    {
        (new ProviderResources\ProfileController)->available($request);
        return back();
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function location_edit()
    {
        return view('provider.location.index');
    }

    /**
     * Update latitude and longitude of the user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function location_update(Request $request)
    {
        $this->validate($request, [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);
        
        try {
            $Provider = Auth::user();
            $Provider->latitude = $request->latitude;
            $Provider->longitude = $request->longitude;
            $Provider->save();
            if($request->ajax())
                return response()->json(['msg' => 'Location Updated Successfully'], 200);
            else
                return back()->with(['flash_success' => 'Location Updated successfully!']);
        } catch (\Throwable $th) {
            if($request->ajax())
                return response()->json(['msg' => 'Driver Not Found']);
            else
                return back()->with(['flash_error' => 'Driver Not Found!']);
        }        
    }

    /**
     * upcoming history.
     *
     * @return \Illuminate\Http\Response
     */
    public function upcoming_trips()
    {
        $fully = (new ProviderResources\TripController)->upcoming_trips()->getData();
        return view('provider.payment.upcoming',compact('fully'));
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */


    public function cancel(Request $request) {

        try{

           return (new ProviderResources\TripController)->cancel($request);

        } catch (ModelNotFoundException $e) {
            return back()->with(['flash_error' => "Something Went Wrong"]);
        }

    }

         /**
     * Show the application change password.
     *
     * @return \Illuminate\Http\Response
     */
    public function change_password()
    {
        return view('provider.profile.change_password');
    }

    /**
     * Change Password.
     *
     * @return \Illuminate\Http\Response
     */
    public function update_password(Request $request)
    {
        $this->validate($request, [
                'password' => 'required|confirmed',
                'old_password' => 'required',
            ]);

        $Provider = \Auth::user();

        if(password_verify($request->old_password, $Provider->password))
        {
            $Provider->password = bcrypt($request->password);
            $Provider->save();

            return back()->with('flash_success','Password changed successfully!');
        } else {
            return back()->with('flash_error','Please enter correct password');
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

                if($official_driver->vehicle->fleet->management_fee != ''){
                    $total_fee = $official_driver->vehicle->fleet->management_fee + $official_driver->vehicle->fleet->maintenance_fee + $official_driver->vehicle->fleet->insurance_fee + $official_driver->vehicle->fleet->road_worthy_fee + $official_driver->vehicle->fleet->company_share+$official_driver->vehicle->fleet->weekly;

                    $management_fee = round(($official_driver->vehicle->fleet->management_fee / $total_fee ) * $request->amt);
                    $weekly = round(($official_driver->vehicle->fleet->weekly / $total_fee ) * $request->amt);
                    $company_share = round(($official_driver->vehicle->fleet->company_share / $total_fee ) * $request->amt);
                    $road_worthy_fee = round(($official_driver->vehicle->fleet->road_worthy_fee / $total_fee ) * $request->amt);
                    $insurance_fee = round(($official_driver->vehicle->fleet->insurance_fee / $total_fee ) * $request->amt);
                    $maintenance_fee = round(($official_driver->vehicle->fleet->maintenance_fee / $total_fee ) * $request->amt);
                    $revenue = $road_worthy_fee + $maintenance_fee + $company_share;

                    $data = array(  
                            'email' => $driver->email, 
                            'amount' => $request->amount,
                            'currency' => "GHS",
                            'reference' => $request->reference,
                            'orderID' => $request->orderID,
                            'split' => [
                                        'type' => 'flat',
                                        'bearer_type' => "account",
                                        'subaccounts' => [[
                                            'subaccount' => 'ACCT_nt6l9ila89nt53e',
                                            'share' => round($weekly * 100)
                                        ],
                                        [
                                        'subaccount' => 'ACCT_v28qbriveNowk6xpbnrp',
                                        'share' => round($revenue * 100)
                                        ],
                                        [
                                            'subaccount'=> 'ACCT_6m0wlmc5zzs0lm6',
                                            'share' => round($insurance_fee * 100)
                                        ]
                                      ] 
                                    ] 
                            );

                    }else{
                        $data = array(  
                                    'email' => $driver->email, 
                                    'amount' => $request->amount,
                                    'currency' => "GHS",
                                    'reference' => $request->reference,
                                    'orderID' => $request->orderID,
                                );
                    }

                // dd($data);
            return Paystack::getAuthorizationUrl()->redirectNow();
        }catch(\Exception $e) {
            Log::info($e);
            return back()->with('flash_error','The paystack token has expired. Please refresh the page and try again.');
        }        
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
            return redirect()->route('provider.drivenow',$id)->with('flash_success', 'You have marked yourself off for the day');

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
                    return redirect()->route('provider.drivenow',$id)->with('flash_error', 'You are not allowed to revoke day off after 15 mins');
                } 
            }
            $official_driver = OfficialDriver::where('driver_id', $id)->where('status', '!=', 1)->first();
            $official_driver->day_off = 0;
            $official_driver->save();
            
            $DriverOff->driver_id = $id;
            $DriverOff->day_off = Carbon::now();
            $DriverOff->status = 2;
            $DriverOff->save();

            return redirect()->route('provider.drivenow',$id)->with('flash_success', 'Your day off for the day has been revoked');

        }catch(\Exception $e){
            Log::info($e);
            return back()->with('flash_error', "Something went wrong! Please try again later.");
        }
    }


}
