<?php

namespace App;

use App\Notifications\FleetResetPassword;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Fleet extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','company','logo','mobile', 'latitude', 'longitude', 'referal'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'deleted_at'
    ];

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new FleetResetPassword($token));
    }

    public function driver()
    {
        return $this->hasMany('App\Provider', 'fleet')->where('approved',1);;
        
    }

    /**
     * Used to get provider bank details
     */
    public function fleet_subaccount()
    {
        return $this->belongsTo('App\FleetSubaccount', 'id','fleet_id');
    }
}
