<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class riveNowPaymentBreak extends Model
{
   protected $fillable = ['driver_id', 'official_id', 'approved_by', 'reason', 'comments','count'];

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function official_driver()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }

    public function admin()
    {
        return $this->belongsTo('App\Admin', 'approved_by','id');
    }
}
