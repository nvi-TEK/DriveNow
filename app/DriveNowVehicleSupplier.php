<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowVehicleSupplier extends Model
{
    protected $fillable = [
            'name',
            'contact_name',
            'image',
            'contact',
            'email',
            'address',
            'acc_no',
            'acc_name',
            'bank_name',
            'bank_code',
            'status',
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
