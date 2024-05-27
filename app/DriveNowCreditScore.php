<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowCreditScore extends Model
{ 
    protected $fillable = ['driver_id', 'official_id', 'vehicle_id', 'oia', 'aia', 'ops', 'aps', 'c_score', 'p_score', 'month', 'year'];

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function official()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }
    public function vehicle()
    {
        return $this->belongsTo('App\DriveNowVehicle', 'vehicle_id','id');
    }
}
