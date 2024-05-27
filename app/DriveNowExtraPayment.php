<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowExtraPayment extends Model
{
     protected $fillable = ['driver_id', 'official_id', 'approved_by', 'reason', 'comments','count','total','due','daily_due','started_at','type','completed','amount_paid'];

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

    public function add_txn()
    {
        return $this->hasMany('App\DriveNowAdditionalTransactions', 'type');
    }

    public function transaction(){
        return $this->hasMany('App\DriveNowRaveTransaction', 'official_id','official_id');
    }
}
