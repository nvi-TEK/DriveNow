<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OfficeExpense extends Model
{
    protected $fillable = ['car_id','paid_to','category','amount','description','added_by','status','approved_by','acc_no','bank_name','bank_name_id','bank_code'];


    public function added()
    {
        return $this->belongsTo('App\Admin', 'added_by','id');
    }

    public function approved()
    {
        return $this->belongsTo('App\Admin', 'approved_by','id');
    }

    public function paid()
    {
        return $this->belongsTo('App\Admin', 'paid_to','id');
    }

    public function vehicle()
    {
        return $this->belongsTo('App\DriveNowVehicle', 'car_id','id');
    }

    public function expense()
    {
        return $this->belongsTo('App\ExpenseCategory', 'category','id');
    }

}
