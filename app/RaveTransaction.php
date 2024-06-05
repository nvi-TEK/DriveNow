<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RaveTransaction extends Model
{

/**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['driver_id','reference_id','rave_ref_id','flwref','narration','amount','transaction_fee','type','status','credit','last_balance','last_availbale_balance'];

   public function request()
    {
        return $this->belongsTo('App\UserRequests','request_id', 'id');
    }
    
    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id','id');
    }
}
