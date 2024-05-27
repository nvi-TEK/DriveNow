<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowVehicle extends Model
{
    protected $fillable = [
        'id', 'make', 'model', 'year', 'reg_no', 'image', 'price', 'maintenance_date', 'insurance_file', 'insurance_expire', 'road_worthy_file', 'road_worthy_expire', 'monthly_due', 'status',  'allocated_date',  'vehicle_color', 'chasis_no', 'transmission_type',
    ];

    protected $hidden = [
         'created_at', 'updated_at', 'deleted_at','driver_id', 'official_id', 'supplier_id','fleet_id','sim','imei','initial_amount','maintenance_date','due_length','monthly_due','due_date','vehicle_cost'
    ];

    public function driver()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function drive_now_history()
    {
        return $this->hasMany('App\OfficialDriver', 'id','vehicle_id');
    }

    public function drive_now()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }

    public function supplier(){
        return $this->belongsTo('App\DriveNowVehicleSupplier', 'supplier_id','id');
    }

    public function fleet(){
        return $this->belongsTo('App\SupplierFleet', 'fleet_id','id');
    }

}
