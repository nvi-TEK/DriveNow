<?php

namespace App\Http\Controllers\ProviderAuth;

use Illuminate\Http\Request;
use App\Provider;
use App\Marketers;
use App\Fleet;
use App\MarketerReferrals;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/provider/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('provider.guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:providers',
            'mobile' => 'required|max:255',
            'password' => 'required|min:6|confirmed',
            'referral' => 'max:255|exists:marketers,referral_code'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return Provider
     */
    protected function create(array $data)
    {
        $Provider = Provider::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'wallet_balance' => 0.00,
            'mobile' => $data['mobile'],
            'fleet' => 1,
            'country_code' => $data['country_code'],
            'password' => bcrypt($data['password']),
        ]);
        if($data['referral'] != ''){
                    try{
                        $driver = Provider::find($Provider->id);

                        $marketer = Marketers::where('referral_code', $data['referral'])->first();

                        $user_referal = User::where('referal', $data['referral'])->first();

                        $driver_referal = Provider::where('referal', $data['referral'])->first();

                        $fleet_referal = Fleet::where('referal', $data['referral'])->first();
                        
                        if($user_referal)
                        {  $driver->user_referred = $data['referral'];
                           $driver->wallet_balance = Setting::get('referal_balance');
                           $driver->referral_used = 1;
                           $driver->save();
                        }else if($driver_referal)
                        {  $driver->driver_referred = $data['referral'];
                           $driver->wallet_balance = Setting::get('referal_balance');
                           $driver->referral_used = 1;
                           $driver->save();
                        }else if($fleet_referal)
                        {  $driver->fleet = $fleet_referal->id;
                           $driver->save();
                        }else if($marketer){
                            $marketer = Marketers::where('referral_code', $data['referral'])->first();
                            $driver = Provider::find($Provider->id);
                            $driver->marketer = $marketer->id;
                            $marketer_referrals = new MarketerReferrals;
                            $marketer_referrals->marketer_id = $marketer->id;
                            $marketer_referrals->driver_id = $driver->id;
                            $marketer_referrals->referrer_code = $data['referral'];
                            $marketer->total_referrals = $marketer->total_referrals + 1;
                            $marketer_referrals->save();
                            $marketer->save();
                            $driver->referral_used = 1;
                            $driver->save(); 
                        }
                    }
                    catch (Exception $e) {
                        
                    }  
            }
            return $Provider;
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm(Request $request)
    {

        if($request->has('referal')){
            $referal = $request->referal; 
        }else{
            $referal = '';
        }
        
        return view('provider.auth.register', compact('referal'));
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('provider');
    }
}
