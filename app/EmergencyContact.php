<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmergencyContact extends Model
{
	protected $fillable = ['first_name','last_name','email','picture','mobile','country_code', 'user_id', 'driver_id'];

	/**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
         'created_at', 'updated_at'
    ];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id','id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }
}
