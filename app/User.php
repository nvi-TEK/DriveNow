<?php

namespace App;

use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens,Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'mobile', 'picture', 'password', 'device_type','device_token','login_by', 'payment_mode','social_unique_id','device_id','wallet_balance','country_code','otp','otp_activation','social_unique_id','referal','fleet', 'app_version','android_app_version','ios_app_version'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * The services that belong to the user.
     */
    public function trips()
    {
        return $this->hasMany('App\UserRequests');
    }

    public function active_requests()
    {
        return $this->hasMany('App\UserRequests','user_id')
                    ->whereNotIn('user_requests.status' , ['CANCELLED', 'COMPLETED','SCHEDULED','SEARCHING']);
    }
    public function completed_requests()
    {
        return $this->hasMany('App\UserRequests','user_id')
                    ->where('user_requests.status' ,  'COMPLETED');
    }

    public function transaction()
    {
        return $this->hasMany('App\RaveTransaction', 'user_id', 'id');
        
    }
}
