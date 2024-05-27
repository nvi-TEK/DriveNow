<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverDeposit extends Model
{
    protected $fillable = ['driver_id','added_by','amount','remarks','status','acc_no','acc_name','bank_name','bank_code','refunded_by','refund','refund_reason'];

    public function added()
    {
        return $this->belongsTo('App\Admin', 'added_by','id');
    }
    public function refunded()
    {
        return $this->belongsTo('App\Admin', 'refunded_by','id');
    }
    public function driver()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }
    public function official()
    {
        return $this->belongsTo('App\OfficialDriver', 'driver_id','driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo('App\DriveNowVehicle', 'driver_id','driver_id');
    }
}
