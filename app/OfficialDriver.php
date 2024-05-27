<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfficialDriver extends Model
{
    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'vehicle_number',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'contract_length',
        'weekly_payment',
        'agreement_start_date',
        'contract_address',
        'deposit',
        'agreed_on',
        'status',
        'agreed',
        'next_due',
        'balance_weeks',
        'amount_paid',
        'vehicle_image',
        'amount_due',
        'engine_off_reason',
        'engine_off_by',
        'engine_off_on',
        'engine_restore_reason',
        'engine_restore_by',
        'engine_restore_on',        
        'daily_due',
        'daily_payment',
        'daily_drivenow',
        'vehicle_cost'
        
    ];

    protected $hidden = [
         'created_at', 'updated_at', 'deleted_at','imei_number','updated_on','updated_by','engine_control','terminated_reason','terminated_on','due_engine_control','driver_id','contract_id','supplier_id','weekly','company_share','maintenance_fee','insurance_fee','road_worthy_fee','management_fee','extra_pay','engine_off_reason','engine_off_by','engine_off_on','engine_restore_reason','engine_restore_by','engine_restore_on','block_try','day_off','contact','pre_balance','initial_amount','work_pay_balance'    ];

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function additional_charges()
    {
        return $this->hasMany('App\DriveNowExtraPayment', 'id','official_id');
    }

    public function transactions()
    {
        return $this->hasMany('App\DriveNowRaveTransaction', 'official_id','id');
    }

    public function invoice()
    {
        return $this->hasMany('App\DriveNowTransaction', 'contract_id','id')->WeeklyInvoices();
    }

    public function contract()
    {
        return $this->belongsTo('App\DriverContracts', 'contract_id','id');
    }

    public function admin()
    {
        return $this->belongsTo('App\Admin', 'updated_by','id');
    }

    public function engine_on_by()
    {
        return $this->belongsTo('App\Admin', 'engine_restore_by','id');
    }

    public function engine_off()
    {
        return $this->belongsTo('App\Admin', 'engine_off_by','id');
    }

    public function vehicle()
    {
        return $this->belongsTo('App\DriveNowVehicle', 'vehicle_id','id');
    }

    public function credit_score()
    {
        return $this->hasMany('App\DriveNowCreditScore', 'official_id','id');
    }
    

}
