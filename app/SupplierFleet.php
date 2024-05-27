<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplierFleet extends Model
{
    protected $fillable = ['name', 'vehicle_cost', 'initial_amount', 'due_length', 'monthly_due', 'due_date', 'acc_no', 'acc_name', 'bank_name', 'bank_code', 'amount_due', 'amount_paid', 'status', 'supplier_id',     ];

    public function supplier(){
        return $this->belongsTo('App\DriveNowVehicleSupplier', 'supplier_id','id');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at'
    ];
}
