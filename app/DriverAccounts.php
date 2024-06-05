<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverAccounts extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'acc_no',
		'acc_name',
		'bank_name',
		'bank_name_id',
		'bank_code',
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
