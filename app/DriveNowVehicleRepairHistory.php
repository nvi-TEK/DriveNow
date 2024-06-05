<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowVehicleRepairHistory extends Model
{
    protected $fillable = ['car_id','paid','type','amount','description','added_by','status','approved_by'];

    public function added()
    {
        return $this->belongsTo('App\Admin', 'added_by','id');
    }

    public function approved()
    {
        return $this->belongsTo('App\Admin', 'approved_by','id');
    }

    public function paid_by()
    {
        return $this->belongsTo('App\Admin', 'paid','id');
    }

    public function vehicle()
    {
        return $this->belongsTo('App\DriveNowVehicle', 'car_id','id');
    }

    public function expense()
    {
        return $this->belongsTo('App\ExpenseCategory', 'type','id');
    }
}
