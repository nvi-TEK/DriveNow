<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Setting;

class HomeController extends Controller
{
    protected $UserAPI;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(UserApiController $UserAPI)
    {
        $this->middleware('auth');
        $this->UserAPI = $UserAPI;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $Response = $this->UserAPI->request_status_check()->getData();

        if(empty($Response->data))
        {
            if($request->has('service')){
                $cards = (new Resource\CardResource)->index();
                $service = (new Resource\ServiceResource)->show($request->service);
                return view('user.request',compact('cards','service'));
            }else{
                $services = $this->UserAPI->services()->getData();
                // dd($services);
                return view('user.dashboard',compact('services'));
            }
        }else{
            if($Response->data[0]->payment == null){
                $total = 0;
            }
            else{
                $total = $Response->data[0]->payment->total;
            }
            return view('user.ride.waiting')->with('request',$Response->data[0])->with('total', $total);
        }
    }

    

    /**
     * Show the application profile.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile()
    {
        return view('user.account.profile');
    }

    /**
     * Show the application profile.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit_profile()
    {
        return view('user.account.edit_profile');
    }

    /**
     * Update profile.
     *
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request)
    {

        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }

        return $this->UserAPI->update_profile($request);
    }

    /**
     * Show the application change password.
     *
     * @return \Illuminate\Http\Response
     */
    public function change_password()
    {
        return view('user.account.change_password');
    }

    /**
     * Change Password.
     *
     * @return \Illuminate\Http\Response
     */
    public function update_password(Request $request)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }
        
        return $this->UserAPI->change_password($request);
    }

    /**
     * Trips.
     *
     * @return \Illuminate\Http\Response
     */
    public function trips()
    {
        $trips = $this->UserAPI->trips()->getData();
        return view('user.ride.trips',compact('trips'));
    }

     /**
     * Payment.
     *
     * @return \Illuminate\Http\Response
     */
    public function payment()
    {
        $cards = (new Resource\CardResource)->index();
        return view('user.account.payment',compact('cards'));
    }


    /**
     * Wallet.
     *
     * @return \Illuminate\Http\Response
     */
    public function wallet(Request $request)
    {
        $cards = (new Resource\CardResource)->index();
        return view('user.account.wallet',compact('cards'));
    }

    /**
     * Promotion.
     *
     * @return \Illuminate\Http\Response
     */
    public function promotion(Request $request)
    {
        $promocodes = $this->UserAPI->promocodes()->getData();
        return view('user.account.promotion',compact('promocodes'));
    }

    /**
     * Add promocode.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_promocode(Request $request)
    {
        return $this->UserAPI->add_promocode($request);
    }

    /**
     * Upcoming Trips.
     *
     * @return \Illuminate\Http\Response
     */
    public function upcoming_trips()
    {
        $trips = $this->UserAPI->upcoming_trips()->getData();
        return view('user.ride.upcoming',compact('trips'));
    }

    public function chat_history(Request $request)
    {
        $this->validate($request, [
                'request_id' => 'required|integer'
            ]);
        try{

            $Chat = array();
            $Chat['data'] = Chat::where('request_id',$request->request_id)
                        //->where('provider_id', \Auth::user()->id)
                        ->get();
            return response()->json($Chat);
        }catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }

    /**
     * Used to send estimate message
     */
    public function estimate_msg(Request $request){
        try {
            $user = Auth::user();
            $to = $user->mobile;
            $from = "Eganow Driver";            
            $content .= 'Distance '.$request->distance.' KM';
            $content .= 'ETA '.$request->eta;
            $content .= 'Estimate Fare '.$request->est_fare;
            $clientId = env("HUBTEL_API_KEY");
            $clientSecret = env("HUBTEL_API_SECRET");            
            $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
            return 1;
        } catch (\Throwable $th) {
            return 0;
        }        
    }

}
