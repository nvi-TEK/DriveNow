<?php

namespace App\Http\Controllers\Resource;

use App\User;
use App\UserRequests;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\Controller;
use Exception;
use Setting;
use \Carbon\Carbon;
use App\RaveTransaction;
use App\UserComments;
use Auth;
use App\IndividualPush;
use Session;
use Log;


class UserResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd($request->all());
        $AllUsers = User::where('archive', '!=', '1')->orderBy('created_at' , 'desc');
        $filter = 0;
        if(request()->has('filter')){
                $filter = $request->filter;
                if ($request->filter == 1) {
                    $page = 'List of Users for '.date('F');
                    $users = $AllUsers->whereMonth('created_at',Carbon::now()->month);
                }else if($request->filter == 2){
                    $requests = User::wherehas('trips')->get()->pluck('id');
                    $page = 'List of Active User';
                    $users = $AllUsers->whereIn('id', $requests);
                }else if($request->filter == 3){
                    
                        $page = 'List of Verified Users';
                        $users = $AllUsers->where('first_name', "!=", '');
                    
                }else if($request->filter == 4){
                    
                        $page = 'List of Unverified Users';
                        $users = $AllUsers->where('first_name', '');
                    
                }else{
                    $page = 'List of Users';
                    $users = $AllUsers;
                }
                } else if($request->has('search')){
                        $page = 'Search result for "'.$request->search .'"';
                        $users = $AllUsers->where('first_name','like', '%'.$request->search.'%')->orwhere('email','like', '%'.$request->search.'%')->orwhere('mobile','like', '%'.$request->search.'%');
                }
                else{
                    $page = 'List of Users';
                    $users = $AllUsers; 
                }
                if(request()->has('filter_date')){
                        $dates = explode(',', (str_replace(" to ", ",", $request->filter_date)));
                        $numbers = $users->whereBetween('created_at',[date($dates[0]), date($dates[1])])->count();
                        $users = $users->whereBetween('created_at',[date($dates[0]), date($dates[1])])->paginate(300);
                        $page = $page." from " .date('d-m-Y', strtotime($dates[0])) ." to ". date('d-m-Y', strtotime($dates[1]))." ( ". $numbers." results)";
                }else{
                    $users = $users->paginate(300);
                }
            
        $total_users = User::where('archive', '!=', '1')->count();
        $valid_users = User::where('first_name', "!=", '')->count();
        $unverified_users = User::where('first_name', '')->count();
        $newuser = User::where('archive', '!=', '1')->whereMonth('created_at',Carbon::now()->month)->whereYear('created_at',Carbon::now()->year)->count();
        $requests = UserRequests::whereMonth('created_at', Carbon::now()->month)->groupBy('user_id')->count();
        return view('admin.users.index', compact('users','newuser', 'requests', 'page', 'total_users', 'valid_users','filter','unverified_users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.users.create');
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
            'email' => 'required|unique:users,email|email|max:255',
            'mobile' => 'between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
            'password' => 'required|min:6',
            'postal_code' => 'required',
        ]);

        try{
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }

            $user = $request->all();

            $user['payment_mode'] = 'CASH';
            $user['password'] = bcrypt($request->password);
            if($request->hasFile('picture')) {
                $user['picture'] = Helper::upload_picture($request->file('picture'));
            }

            $user = User::create($user);

            return back()->with('flash_success','User Details Saved Successfully');

        } 

        catch (Exception $e) {
            return back()->with('flash_error', 'User Not Found');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::with('trips')->findOrFail($id);
            $requests = UserRequests::where('user_id', $id)->with('payment','user','provider')->orderBy('created_at', 'desc')->get();
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
            
            $userComments = UserComments::where('user_id', $id)->with('user','moderator')->orderBy('created_at', 'desc')->get();
            for($i = 0; $i < count($userComments); $i++){
                $userComments[$i]->posts = UserComments::where('marketer_id',$userComments[$i]->moderator->id)->count();
            }
            $moderator_posts = UserComments::where('marketer_id', Auth::guard('admin')->user()->id)->count();

            $custom_pushes = IndividualPush::where('user_id', $id)->with('moderator')->orderBy('created_at', 'desc')->get();
             $mes = "";

             $user = User::find($user->id);
             //Transaction

             $credit_pending_transactions = RaveTransaction::where('user_id', $user->id)->where('status', 2)->where('type', 'credit')->orderBy('created_at', 'desc')->get();
            if($credit_pending_transactions){
                foreach ($credit_pending_transactions as $credit_pending_transaction) {
                $payToken = $credit_pending_transaction->rave_ref_id;

                $client1 = new \GuzzleHttp\Client();
                $headers = ['Content-Type' => 'application/json'];

                $status_url = "https://app.slydepay.com/api/merchant/invoice/checkstatus";
                $status = $client1->post($status_url, [ 
                    'headers' => $headers,
                    'json' => ["emailOrMobileNumber"=>"replace with payment email",
                                "merchantKey"=>"replace with merchant key",
                                "payToken"=>$payToken,
                                "confirmTransaction" => true]]);

                $result = array();
                $result = json_decode($status->getBody(),'true');
                Log::info("Driver Wallet balance status: ". $payToken." - ". $result['result']);
                if($result['success'] == TRUE && $result['result'] == "CONFIRMED"){

                    $credit_pending_transaction->last_balance = $user->wallet_balance;
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

        $transactions = RaveTransaction::where('user_id', $user->id)->orderBy('created_at', 'desc')->paginate(300);

// dd($requests[0]->user);
            return view('admin.users.user-details', compact('user', 'earnings', 'requests', 'total_request', 'completed_request', 'cancelled_request', 'userComments', 'moderator_posts', 'custom_pushes', 'mes','transactions'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            $user = User::findOrFail($id);
            return view('admin.users.edit',compact('user'));
        } catch (ModelNotFoundException $e) {
            return $e;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'mobile' => 'between:6,13',
            'picture' => 'mimes:jpeg,jpg,bmp,png|max:5242880',
        ]);

        try {
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }

            $user = User::findOrFail($id);

            if($request->hasFile('picture')) {
                $user->picture = Helper::upload_picture($request->file('picture'));
            }

             if ($request->has('wallet_balance')){
                $user->wallet_balance += $request->wallet_balance;

                $code = rand(1000, 9999);
                $name = substr($user->first_name, 0, 2);
                $reference = "UWC".$code.$name;

                $rave_transactions = new RaveTransaction;
                $rave_transactions->user_id = $user->id;
                $rave_transactions->reference_id = $reference;
                $rave_transactions->narration = "Credit from Eganow";
                $rave_transactions->amount = $request->wallet_balance;
                $rave_transactions->status = 1;
                $rave_transactions->type = "credit";
                $rave_transactions->save();
            }


            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->mobile = $request->mobile;            
            $user->save();

            return back()->with('flash_success', 'User Updated Successfully');    
        } 

        catch (ModelNotFoundException $e) {
            return back()->with('flash_error', 'User Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(Setting::get('demo_mode', 0) == 1) {
            return back()->with('flash_error', 'Disabled for demo purposes! Please contact us at info@drivetry.com');
        }
        try {
            $user = User::find($id);
            $user->email = $user->email . $user->id;
            $user->archive = 1;
            $user->save();

            return back()->with('flash_success', 'User Archived successfully');
        } 
        catch (Exception $e) {
            return back()->with('flash_error', 'User Not Found');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Provider  $provider
     * @return \Illuminate\Http\Response
     */
    public function request($id){
        try{
            $requests = UserRequests::where('user_requests.user_id',$id)
                    ->RequestHistory()
                    ->get();
            return view('admin.request.index', compact('requests'));
        }
        catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }

    }

}
