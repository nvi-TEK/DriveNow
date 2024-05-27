<?php

namespace App;

use DB;

use Illuminate\Database\Eloquent\Model;

class UserRequests extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_id','user_id','current_provider_id',
        'service_type_id','status','cancelled_by',
        'paid','distance','s_latitude','d_latitude',
        's_longitude','d_longitude','paid','s_address', 
        'd_address', 'assigned_at','schedule_at','started_at',
        'finished_at', 'use_wallet', 'user_rated', 'provider_rated',
        's_title', 'd_title','reroute', 'driver_latitude', 
        'driver_longitude','estimated_fare','distance_price', 'time', 
        'time_price', 'tax_price', 'base_price', 'wallet_balance', 'discount', 'pickup_note', 'total', 'eta', 'accepted_at','delivery_image','pay_resp','donation','pickup_add_flat','pickup_add_area','pickup_add_landmark','delivery_add_flat','delivery_add_area','delivery_add_landmark'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
         'created_at', 'updated_at'
    ];

    /**
     * ServiceType Model Linked
     */
    public function service_type()
    {
        return $this->belongsTo('App\ServiceType');
    }
    
    /**
     * UserRequestPayment Model Linked
     */
    public function payment()
    {
        return $this->hasOne('App\UserRequestPayment', 'request_id');
    }

    /**
     * UserRequestRating Model Linked
     */
    public function rating()
    {
        return $this->hasOne('App\UserRequestRating', 'request_id');
    }

    /**
     * The user who created the request.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    /**
     * UserRequestRating Model Linked
     */
    public function filter()
    {
        return $this->hasMany('App\RequestFilter', 'request_id');
    }

    public function fleet()
    {
        return $this->belongsTo('App\Fleet','fleet_id','id');
    }

    /**
     * The provider assigned to the request.
     */
    public function provider()
    {
        return $this->belongsTo('App\Provider','current_provider_id','id');
    }

    public function provider_service()
    {
        return $this->belongsTo('App\ProviderService', 'current_provider_id', 'provider_id');
    }

    public function provider_device()
    {
        return $this->belongsTo('App\ProviderDevice', 'current_provider_id', 'provider_id');
    }

    public function provider_profiles()
    {
        return $this->belongsTo('App\ProviderProfile', 'current_provider_id', 'provider_id');
    }

    public function scopePendingRequest($query, $user_id)
    {
        return $query->where('user_id', $user_id)
                ->whereNotIn('status' , ['CANCELLED', 'COMPLETED', 'SCHEDULED']);
    }

    public function scopeRequestHistory($query)
    {
        return $query->orderBy('user_requests.created_at', 'desc')
                        ->where('user_requests.provider_id', '!=', 0)
                        ->with('user','payment','provider','service_type');
    }

    public function scopeUserTrips($query, $user_id)
    {
        return $query->where('user_requests.user_id', '=', $user_id)
                    ->where('user_requests.provider_id', '!=', 0)
                    ->whereIn('user_requests.status' , ['CANCELLED', 'COMPLETED'])
                    ->select('user_requests.*')
                    ->orderBy('created_at','desc')
                    ->with('payment','service_type','user','provider','provider_profiles','rating');
    }

    public function scopeUserLastTrips($query, $user_id)
    {
        return $query->where('user_requests.user_id', '=', $user_id)
                    ->where('user_requests.provider_id', '!=', 0)
                    ->whereIn('user_requests.status' , ['CANCELLED', 'COMPLETED','SCHEDULED'])
                    ->select('user_requests.*')
                    ->orderBy('created_at','desc')
                    ->with('payment','service_type','user','provider','provider_profiles','rating');
    }

    public function scopeUserTripDetails($query, $user_id, $request_id)
    {
        return $query->where('user_requests.user_id', '=', $user_id)
                    ->where('user_requests.provider_id', '!=', 0)
                    ->where('user_requests.id', '=', $request_id)
                    ->where('user_requests.status', '=', 'COMPLETED')
                    ->select('user_requests.*')
                    ->with('payment','service_type','user','provider','provider_profiles','rating');
    }

    public function scopeUserRequestStatusCheck($query, $user_id, $check_status)
    {
        return $query->where('user_requests.user_id', $user_id)
                    ->where('user_requests.user_rated',0)
                    ->whereNotIn('user_requests.status', $check_status)
                    ->select('user_requests.*')
                    ->with('user','provider','service_type','provider_service','provider_profiles','rating','payment');
    }

        public function scopeUserUpcomingTripDetails($query, $user_id, $request_id)
    {
        return $query->where('user_requests.user_id', '=', $user_id)
                    ->where('user_requests.id', '=', $request_id)
                    ->where('user_requests.status', '=', 'SCHEDULED')
                    ->select('user_requests.*')
                    ->with('service_type','user','provider','provider_profiles');
    }

     public function scopeUserUpcomingTrips($query, $user_id)
    {
        return $query->where('user_requests.user_id', '=', $user_id)
                    ->where('user_requests.status', '=', 'SCHEDULED')
                    ->select('user_requests.*')
                    ->orderBy('created_at','desc')
                    ->with('service_type','provider','provider_profiles');
    }

     public function scopeUserRequestAssignProvider($query, $user_id, $check_status)
    {
        return $query->where('user_requests.user_id', $user_id)
                    ->where('user_requests.user_rated',0)
                    ->where('user_requests.provider_id',0)
                    ->whereIn('user_requests.status', $check_status)
                    ->select('user_requests.*')
                    ->with('filter');
    }


    public function scopeProviderUpcomingRequest($query, $user_id)
    {
        return $query->where('user_requests.provider_id', '=', $user_id)
                    ->where('user_requests.status', '=', 'SCHEDULED')
                    ->select('user_requests.*')
                    ->orderBy('created_at','desc')
                    ->with('service_type','user','provider','provider_profiles');
    }

}
