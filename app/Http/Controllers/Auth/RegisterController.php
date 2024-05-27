<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Marketers;
use App\MarketerReferrals;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

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
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
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
            'email' => 'required|email|max:255|unique:users',
            'mobile' => 'required|max:255',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $User =  User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'country_code' => $data['country_code'],
            'password' => bcrypt($data['password']),
            'payment_mode' => 'CASH'
        ]);

         if($data['referral'] != ''){
                try{
                    $marketer = Marketers::where('referral_code', $data['referral'])->first();
                    $user = User::find($User->id);
                    $user->marketer = $marketer->id;
                    $marketer_referrals = new MarketerReferrals;
                    $marketer_referrals->marketer_id = $marketer->id;
                    $marketer_referrals->user_id = $user->id;
                    $marketer_referrals->referrer_code = $data['referral'];
                    $marketer->total_referrals = $marketer->total_referrals + 1;
                    $marketer->user_referrals = $marketer->user_referrals + 1;
                    $marketer_referrals->save();
                    $marketer->save();
                    $user->save(); 
                }
                catch (Exception $e) {
                    
                }
                
            }
            return $User;

        // send welcome email here
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
        return view('user.auth.register', compact('referal'));
    }
}
