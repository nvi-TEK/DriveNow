<?php

namespace App\Http\Controllers\Resource;

use App\Marketers;
use App\MarketerReferrals;
use App\Provider;
use App\User;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Exception;
use Setting;
use \Carbon\Carbon;

class MarketerResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $marketers = Marketers::orderBy('created_at' , 'desc')->get();
        $top_performers = Marketers::orderBy('total_referrals','desc')->take(4)->get();
        $drivers = Provider::count();
        $users = User::count();
        $today_drivers = Provider::where('created_at', '>=', Carbon::today())->count();
        $today_users = User::where('created_at', '>=', Carbon::today())->count();
        $referrals = MarketerReferrals::orderBy('id','desc')->count();
        $today = MarketerReferrals::where('created_at', '>=', Carbon::today())->count();
        $today_user = MarketerReferrals::whereNotNull('user_id')->where('created_at', '>=', Carbon::today())->count();
        $today_driver = MarketerReferrals::whereNotNull('driver_id')->where('created_at', '>=', Carbon::today())->count();
        $user_referrals = MarketerReferrals::whereNotNull('user_id')->count();
        $driver_referrals = MarketerReferrals::whereNotNull('driver_id')->count();
        
        return view('admin.marketers.index', compact('marketers', 'today', 'user_referrals', 'driver_referrals','referrals', 'users', 'drivers','today_user','today_driver','top_performers','today_users', 'today_drivers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.marketers.create');
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
            'email' => 'required|unique:marketers,email|email|max:255',
            'mobile' => 'between:6,13',
        ]);

        try{

            $marketer = new Marketers;
            $marketer->first_name = $request->first_name;
            $marketer->last_name = $request->last_name;
            $marketer->mobile = $request->mobile;
            $marketer->email = $request->email;

            $rand = rand(100, 999);
            $name =  substr($request->first_name, 0, 2);
            $referral_code = strtoupper($name.$rand);
            
            $marketer->referral_code = $referral_code;
            
            $marketer->latitude = $request->latitude;
            $marketer->longitude = $request->longitude;
            $marketer->address = $request->address;

            $marketer->save();
            //Send SMS to marketer with their Referal Code
            $link = url("/marketer_stats/".$referral_code);
            $to = $request->mobile;
            $to = str_replace(" ", "", $to);
            if($request->has('country_code')){
                $cc = $request->country_code;
            }else{
                 $cc = "+233";
            }
           
            $from = "Eganow";
            if(str_contains($cc,"23") == true){
                $content = urlencode("Thanks for joining the Eganow Ambassador Program. Your unique Referral Code is: ".$referral_code."

Use the the link below to view your progress:".$link);
                $clientId = env("HUBTEL_API_KEY");
                $clientSecret = env("HUBTEL_API_SECRET");

                // $sendSms =  (new HubtelMessage)
                // ->from($from)
                // ->to($to)
                // ->content($content);

                $sendSms = sendSMS($from, $to, $content, $clientId, $clientSecret);
               if(count($sendSms) == 1 || $sendSms == FALSE){
                    $content = urlencode("Thanks for joining the Eganow Ambassador Program. Your unique Referral Code is: ".$referral_code."

Use the the link below to view your progress:".$link);
                    $mobile = $to;
                    if($mobile[0] == 0){
                        $receiver = $mobile;
                    }else{
                        $receiver = "0".$mobile; 
                    }


                    // $client1 = new \GuzzleHttp\Client();

                    // $url1 = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&getBalance=true";

                    // $headers1 = ['Content-Type' => 'application/json'];
                    
                    // $res1 = $client1->get($url1, ['headers' => $headers1]);

                    // $data = json_decode($res1->getBody());

                    // $balance = round(str_replace("Messaging balance for API User: f3En@x is","", $data));

                    $client = new \GuzzleHttp\Client();

                    $url = "https://app.rancardmobility.com/rmcs/sendMessage.jsp?username=f3En@x&password=F@4YNh$&to=".$receiver."&from=Eganow&text=".$content."&smsc=RANCARD";
                    
                    $headers = ['Content-Type' => 'application/json'];
                    
                    $res = $client->get($url, ['headers' => $headers]);

                    $code = (string)$res->getBody();
                    $codeT = str_replace("\n","",$code);
                
                    if($codeT != "000"){
                        $to = $cc . $to;
                        $content = "Thanks for joining the Eganow Ambassador Program. Your unique Referral Code is: ".$referral_code."

Use the the link below to view your progress:".$link;
                        $sendTwilio = sendMessageTwilio($to, $content);
                        //Log::info($sendTwilio);
                    }
                }
                
                
            }
            else{
                $to = $cc . $to ;
                $content = "Thanks for joining the Eganow Ambassador Program. Your unique Referral Code is: ".$referral_code."

Use the the link below to view your progress:".$link;
                $sendTwilio = sendMessageTwilio($to, $content);
                //Log::info($sendTwilio);
            }


            return redirect()->route('admin.marketer.index')->with('flash_success','Marketer Details Saved Successfully');

        } 

        catch (Exception $e) {
            return back()->with('flash_error', 'Marketer Not Found');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Marketer  $marketer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // 
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Marketer  $marketer
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $marketer = Marketers::findOrFail($id);
            return view('admin.marketers.edit',compact('marketer'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Marketer  $marketer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }
        
        $this->validate($request, [
            // 'name' => 'required|max:255',
            'mobile' => 'between:6,13',
        ]);

        try {

            $marketer = Marketers::findOrFail($id);

            $marketer->first_name = $request->first_name;
            $marketer->last_name = $request->last_name;
            $marketer->mobile = $request->mobile;
            $marketer->email = $request->email;
            
            $marketer->latitude = $request->latitude;
            $marketer->longitude = $request->longitude;
            $marketer->address = $request->address;
            $marketer->save();

            return redirect('/admin/marketer')->with('flash_success', 'Marketer Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'Marketer Not Found');
        }
    }

    public function drivers($id){

        try{

            $requests = MarketerReferrals::where('marketer_id',$id)
                        ->with('payment')
                        ->get();



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
     * @param  \App\Marketer  $Marketer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at ...');
        }
        
        try {
            Marketers::find($id)->delete();
            return back()->with('flash_success', 'Marketer deleted successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'Marketer Not Found');
        }
    }

}
