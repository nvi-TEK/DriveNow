<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderProfile extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_id',
        'language',
        'address',
        'address_secondary',
        'city',
        'country',
        'postal_code',
        'acc_no',
        'acc_name',
        'bank_name',
        'bank_name_id',
        'bank_code',
        'dl_no',
        'dl_exp',
        'dl_city',
        'dl_state',
        'dl_city_id',
        'dl_state_id',
        'dl_country',
        'car_registration',
        'car_make',
        'car_model',
        'car_picture',
        'mileage',
        'car_make_year',
        'road_worthy_expire',
        'insurance_type',
        'insurance_expire',
        'notified'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at', 'deleted_at'
    ];
}
