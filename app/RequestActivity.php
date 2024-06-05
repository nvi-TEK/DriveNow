<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RequestActivity extends Model
{

	protected $fillable = [
	'request_id',
	'user_id',
	'fleet_id',
	'provider_id',
	'status'
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
        return $this->belongsTo('App\UserRequests');
    }
    
    public function provider_profiles()
    {
        return $this->belongsTo('App\ProviderProfile', 'provider_id','provider_id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id','id');
    }

    public function fleet()
    {
        return $this->belongsTo('App\Fleet','fleet_id','id');
    }

}
