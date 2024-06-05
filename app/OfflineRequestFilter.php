<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfflineRequestFilter extends Model
{
   protected $fillable = [
        'request_id','provider_id','status','user_id', 'distance', 'duration'
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
     * The services that belong to the user.
     */
    public function request()
    {
        return $this->belongsTo('App\UserRequests', 'request_id', 'id');
    }
    
    public function provider_profiles()
    {
        return $this->belongsTo('App\ProviderProfile', 'provider_id','provider_id');
    }

    public function provider_device()
    {
        return $this->belongsTo('App\ProviderDevice', 'provider_id','provider_id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'provider_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id','id');
    }
}
