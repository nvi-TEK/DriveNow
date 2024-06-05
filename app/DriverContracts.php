<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverContracts extends Model
{
   protected $fillable = ['driver_id','official_id','agreement_start_date','contract_id','status','remarks','agreed_on','cancelled_on','cancelled_by'];

    public function cancelled()
    {
        return $this->belongsTo('App\Admin', 'cancelled_by','id');
    }
    public function driver()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }
    public function official()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }

    public function contract()
    {
        return $this->belongsTo('App\DriveNowContracts', 'contract_id','id');
    }
}
