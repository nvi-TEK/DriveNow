<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowTransaction extends Model
{
     protected $fillable = ['id','driver_id','contract_id','amount','due_date','status','due','add_charge','paid_date','delay','paid_amount','balance_amount','balance_score','payment_status','pay_score'];

     public function provider()
        {
            return $this->belongsTo('App\Provider', 'driver_id','id');
        }

     public function official_driver()
        {
            return $this->belongsTo('App\OfficialDriver', 'contract_id','id');
        }

    public function transactions(){
            return $this->hasMany('App\DriveNowRaveTransaction', 'bill_id');
    }
    public function scopeWeeklyInvoices($query)
    {
        return $query->where('drivenow_transactions.due_date', '!=', '');
    }

}
