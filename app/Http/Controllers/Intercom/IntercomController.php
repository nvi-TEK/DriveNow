<?php

namespace App\Http\Controllers\Intercom;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intercom\IntercomClient;
use DB;
use App\User;
use App\UserRequests;

class IntercomController extends Controller
{
        /** 
     * Base
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function __construct()
    {
        $this->client = new IntercomClient('', null);
    }

       /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
           
        $providers = DB::table('providers')
        ->leftjoin('provider_profiles','provider_profiles.provider_id','=','providers.id')
        ->leftjoin('provider_services','provider_services.provider_id','=','providers.id')
        ->leftjoin('service_types','service_types.id','=','service_type_id')
        ->get();
        foreach ($providers as $key => $value) {
           $this->update($value);
        }
    }

    public function users(){
        $allUsers = DB::table('users')->get();
        foreach ($allUsers as $key => $data) {
            $last_ride = UserRequests::where('user_id', $data->id)->where('paid', '1')->orderBy('created_at', 'desc')->first();
            if ($last_ride) {
                 $this->client->users->update([
            'user_id' => $data->first_name.' '.$data->last_name,
            "email" => $data->email,
            'name' => $data->first_name.' '.$data->last_name,
            'phone' =>$data->country_code.''.$data->mobile,
            "custom_attributes" => [ 'name' => $data->first_name.' '.$data->last_name,'first_name' => $data->first_name, 'last_name'  => $data->last_name, 'default payment' => $data->payment_mode, 'Device Type' => $data->device_type, 'average rating' => $data->rating, 'User' =>'True','Wallet Balance' =>$data->wallet_balance, 'last_request_at' => strtotime($last_ride->created_at), 'last_request_status' => $last_ride->status, 'last_request_from' => $last_ride->s_address, 'last_request_to' => $last_ride->d_address]
        ]);
            }
            else{
                $this->client->users->update([
                    "email" => $data->email,
                    "custom_attributes" => [ 'name' => $data->first_name.' '.$data->last_name,'first_name' => $data->first_name, 'last_name'  => $data->last_name,'mobile' =>$data->country_code.''.$data->mobile, 'default payment' => $data->payment_mode, 'Device Type' => $data->device_type, 'average rating' => $data->rating, 'User' =>'True','Wallet Balance' =>$data->wallet_balance]
                ]);
            }
        }
    }


    /**
     * Create a user
     *
     * @return \Illuminate\Http\Response
     */
    public function update($data)
    {
        try {
               $this->client->users->update([
            'user_id' => $data->first_name.' '.$data->last_name,
            "email" => $data->email,
            'name' => $data->first_name.' '.$data->last_name,
            'phone' =>$data->country_code.''.$data->mobile,
            "custom_attributes" => [ 'name' => $data->first_name.' '.$data->last_name,'first_name' => $data->first_name, 'last_name'  => $data->last_name,'mobile' =>$data->country_code.''.$data->mobile, 'avatar'  => $data->avatar,'login_by'  => $data->login_by,'description'  => $data->description, 'rating'  => $data->rating, 'RatingCount' =>$data->rating_count, 'status'  => $data->status,'account number'  => $data->acc_no, 'account name'  => $data->acc_name, 'bank_name'  => $data->bank_name,'dl_city'  => $data->dl_city,'dl_no'  => $data->dl_no,'Car Reg Number'  => $data->car_registration, 'insurance_type'  => $data->insurance_type,'insurance expires'  => $data->insurance_expire,'Service type'  => $data->name,'Delivery Driver'  => $data->is_delivery,'charge_type'=>$data->calculator,'Uploaded Document' => $data->document_uploaded, 'Provider' => 'True', 'approved_by' =>$data->approved_by]
        ]);
        } catch (\Exception $error) {

        }
     
    }

        /**
     * Update a user
     *
     * @return \Illuminate\Http\Response
     */
    // public function update2($data)
    // {
    //     $eligible = $this->checkyear($data->year);
    //     $this->client->users->update([
    //         "email" => $data->email,
    //         "custom_attributes" => [ 'name' => $data->first_name.' '.$data->last_name,'provider_id' => $data->id,'first_name' => $data->first_name, 'last_name'  => $data->last_name,'mobile' => $data->mobile, 'host'  => 'true', 'verified' => $data->email_token, 'vehicle2' =>$data->vehicleName, 'make2' => $data->make, 'model2' => $data->model, 'year2' => $data->year, 'eligible2'  => $eligible, 'referral Link' =>  'https://drivetry.co.uk/refer/'.$data->referral_code, 'referral code' => $data->referral_code]
    //     ]);
    // }

    //         /**
    //  * Update a user
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function update3($data)
    // {
    //     $eligible = $this->checkyear($data->year);
    //     $this->client->users->update([
    //         "email" => $data->email,
    //         "custom_attributes" => [ 'name' => $data->first_name.' '.$data->last_name,'provider_id' => $data->id,'first_name' => $data->first_name, 'last_name'  => $data->last_name,'mobile' => $data->mobile, 'host'  => 'true', 'verified' => $data->email_token, 'vehicle3' =>$data->vehicleName, 'make3' => $data->make, 'model3' => $data->model, 'year3' => $data->year, 'eligible3'  => $eligible, 'referral Link' =>  'https://drivetry.co.uk/refer/'.$data->referral_code, 'referral code' => $data->referral_code]
    //     ]);
    // }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
       dd($this->client->users->getUsers([]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function rate_limits()
    {
       $rate_limit = $intercom->getRateLimitDetails();
    }

    // /**
    //  * Update a user (Note: This method is an alias to the create method. In practice you
    //  * can use create to update users if you wish)
    //  *
    //  * @param  \Illuminate\Http\Request  $request
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function update($data)
    // {
    //     $client->users->update([
    //         "email" => "test@example.com",
    //         "custom_attributes" => ['foo' => 'bar']
    //     ]);
    // }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyAll()
    {
       $allUsers = $this->client->users->getUsers([]);
       foreach ($allUsers->users as $key => $user) {
           $this->client->users->permanentlyDeleteUser($user->id);
           # code...
       }
       return 'users Deleted';
    }
}
