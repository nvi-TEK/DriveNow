<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverCars extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'car_registration',
        'car_make',
        'car_model',
        'car_picture',
        'mileage',
        'car_make_year',
        'road_worthy_expire',
        'insurance_type',
        'insurance_expire',
        'insurance_file',
		'road_worthy_file',
		'is_active',
        'status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    public function provider()
    {
        return $this->belongsTo('App\Provider','driver_id','id');
    }
}
