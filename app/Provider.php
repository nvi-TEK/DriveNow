<?php

namespace App;

use App\Notifications\ProviderResetPassword;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Provider extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'mobile',
        'address',
        'avatar',
        'gender',
        'latitude',
        'longitude',
        'referal',
        'marketer',
        'status',
        'description',
        'country_code',
        'otp',
        'otp_activation',
        'document_uploaded',
        'social_unique_id',
        'fleet',
        'wallet_balance',
        'availability',
        'upload_notify',
        'approved_at',
        'available_on',
        'bonus',
        'ambassador',
        'app_version',
        'android_app_version',
        'ios_app_version',
        'available_balance',
        'location_updated',
        'official_drivers',
        'agreed',
        'contract_length',
        'weekly_payment',
        'vehicle_cost',
        'agreement_start_date',
        'contract_address','deposit','agreed_on','zen_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'updated_at', 'created_at', 'deleted_at'
    ];

    /**
     * The services that belong to the user.
     */
    public function service()
    {
        return $this->hasOne('App\ProviderService');
    }

    public function fleetowner()
    {
        return $this->belongsTo('App\Fleet');
    }

    public function marketer()
    {
        return $this->hasOne('App\MarketerReferrals', 'driver_id');
        
    }

    public function transaction()
    {
        return $this->hasMany('App\RaveTransaction', 'driver_id', 'id');
        
    }

    public function official()
    {
        return $this->hasOne('App\OfficialDriver', 'driver_id', 'id');
        
    }

    public function approved()
    {
        return $this->belongsTo('App\Admin', 'approved_by','id');
    }

    /** Referrals **/

    public function user_referrals()
    {
        return $this->hasMany('App\User','driver_referred','referal');
    }

    public function driver_referrals()
    {
        return $this->hasMany('App\Provider','driver_referred','referal');    }

    /** Received Requests */
    public function request_activity()
    {
        return $this->hasMany('App\RequestActivity', 'provider_id', 'id');
    }

    /**
     * The services that belong to the user.
     */
    public function incoming_requests()
    {
        return $this->hasMany('App\RequestFilter')->where('status', 0);
    }

    /**
     * The services that belong to the user.
     */
    public function requests()
    {
        return $this->hasMany('App\RequestFilter');
    }

    /**
     * The services that belong to the user.
     */
    public function profile()
    {
        return $this->hasOne('App\ProviderProfile');
    }

    /**
     * The services that belong to the user.
     */
    public function device()
    {
        return $this->hasOne('App\ProviderDevice');
    }

    /**
     * The services that belong to the user.
     */
    public function trips()
    {
        return $this->hasMany('App\UserRequests');
    }

    /**
     * The services accepted by the provider
     */
    public function accepted()
    {
        return $this->hasMany('App\UserRequests','provider_id')
                    ->where('status','!=','CANCELLED');
    }

    public function active_requests()
    {
        return $this->hasMany('App\UserRequests','provider_id')
                    ->where('status','!=','CANCELLED')->where('status', '!=', 'COMPLETED');
    }

    //Completed  Requests
    public function completed_requests()
    {
        return $this->hasMany('App\UserRequests','provider_id')
                    ->where('status','COMPLETED');
    }

    /**
     * service cancelled by provider.
     */
    public function cancelled()
    {
        return $this->hasMany('App\UserRequests','provider_id')
                ->where('status','CANCELLED');
    }

    /**
     * The services that belong to the user.
     */
    public function documents()
    {
        return $this->hasMany('App\ProviderDocument');
    }

    /**
     * The services that belong to the user.
     */
    public function document($id)
    {
        return $this->hasOne('App\ProviderDocument')->where('document_id', $id)->first();
    }



    /**
     * The services that belong to the user.
     */
    public function pending_documents()
    {
        return $this->hasMany('App\ProviderDocument')->where('status', 'ASSESSING')->count();
    }

    public function accessed_documents()
    {
        return $this->hasMany('App\ProviderDocument')->where('status', 'ACTIVE')->count();
    }

    public function declined_documents()
    {
        return $this->hasMany('App\ProviderDocument')->where('status', 'DECLINED')->count();
    }

    public function total_documents()
    {
        return $this->hasMany('App\ProviderDocument')->count();
    }

    /**
     * Used to get provider bank details
     */
    public function provider_subaccount()
    {
        return $this->belongsTo('App\DriverSubaccount', 'id','driver_id');
    }
    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ProviderResetPassword($token));
    }
}
