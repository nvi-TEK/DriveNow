<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowRaveTransaction extends Model
{
     protected $fillable = ['official_id',  'network', 'amount','status',  'bill_id'];

     protected $hidden = [
         'created_at', 'updated_at','reference_id', 'slp_ref_id', 'slp_resp','driver_id','weekly','company_share','maintenance_fee','insurance_fee','road_worthy_fee','management_fee','add_charge'];

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function official_driver()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }

    public function extra_due()
    {
        return $this->hasMany('App\DriveNowExtraPayment', 'official_id','official_id');
    }

    public function add_split(){
        return $this->hasMany('App\DriveNowAdditionalTransactions', 'tran_id');
    }

    public function drivenow_transaction()
    {
        return $this->belongsTo('App\DriveNowTransaction', 'bill_id');
    }
}
