<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowAdditionalTransactions extends Model
{

 protected $fillable = ['driver_id', 'official_id', 'tran_id', 'paid_amount', 'type', 'amount'];

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function official()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }


    public function transaction()
    {
        return $this->belongsTo('App\DriveNowRaveTransaction', 'tran_id','id');
    }

    public function due_type()
    {
        return $this->belongsTo('App\DriveNowExtraPayment', 'type','id');
    }
}
