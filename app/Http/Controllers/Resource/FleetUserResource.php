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
use Auth;

class FleetUserResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('archive', '!=', '1')->where('fleet',Auth::user()->id)->orderBy('created_at' , 'desc')->get();
        return view('fleet.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('fleet.users.create');
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
        ]);

        try{
            if($request->mobile[0] == "0"){
                $request->mobile = ltrim($request->mobile, 0);
            }
            $user = $request->all();

            $user['payment_mode'] = 'CASH';
            $user['fleet'] = Auth::user()->id;
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
            $requests = UserRequests::where('user_id', $id)->with('payment')->get();
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
            return view('fleet.users.user-details', compact('user', 'earnings', 'requests', 'total_request', 'completed_request', 'cancelled_request'));
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
            return view('fleet.users.edit',compact('user'));
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

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->mobile = $request->mobile;  
            $user->fleet = Auth::user()->id;            
            $user->save();

            return redirect()->route('fleet.user.index')->with('flash_success', 'User Updated Successfully');    
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
            return back()->with('flash_success', 'User deleted successfully');
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
            return view('fleet.request.index', compact('requests'));
        }

        catch (Exception $e) {
             return back()->with('flash_error','Something Went Wrong!');
        }

    }

}
