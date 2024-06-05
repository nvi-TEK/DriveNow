<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowVehiclePayment extends Model
{
    protected $fillable = ['supplier_id','car_id','amount','status','approved_by','due_on','paid_on'];

    public function vehicle()
    {
        return $this->belongsTo('App\DriveNowVehicle', 'car_id','id');
    }

    public function supplier(){
        return $this->belongsTo('App\DriveNowVehicleSupplier', 'supplier_id','id');
    }

    public function paid_by(){
        return $this->belongsTo('App\Admin', 'approved_by','id');
    }

}
