<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserRequestPayment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'request_id',
        'payment_mode',
        'fixed',
        'distance',
        'commision',
        'discount',
        'tax',
        'total',
        'wallet',
        'amount_to_collect',
        'trip_fare',
        'sub_total',
        'driver_earnings',
        'money_to_wallet',
        'minimum_fare',
        'donation'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
      'remember_token', 'created_at', 'updated_at'
    ];

        /**
     * The services that belong to the user.
     */
    public function request()
    {
        return $this->belongsTo('App\UserRequests');
    }

    public function promocode()
    {
        return $this->belongsTo('App\Promocode', 'promocode_id','id');
    }
}
