<?php

namespace App;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Marketers extends Model
{
     use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'referral_code','mobile', 'latitude', 'longitude','total_referrals','user_referrals','driver_referrals'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    public function marketer_referrals()
    {
        return $this->hasMany('App\MarketerReferrals', 'marketer_id');
    }
}
