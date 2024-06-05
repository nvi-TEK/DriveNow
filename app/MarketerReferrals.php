<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarketerReferrals extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
'referrer_code',
'marketer_id',
'driver_id',
'user_id',
'amount',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 
    ];

    public function drivers()
    {
        return $this->belongsTo('App\Provider');
    }

}
