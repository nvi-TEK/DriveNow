<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FailedRequest extends Model
{
   protected $fillable = [
        'user_id',
        'service_type_id','status',
        'distance','s_latitude','d_latitude','s_longitude',
        'd_longitude','s_address', 'd_address',
        'assigned_at','schedule_at', 'use_wallet',
        's_title', 'd_title'
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
     * The user who created the request.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

   
    public function fleet()
    {
        return $this->belongsTo('App\Fleet','fleet_id','id');
    }

}
