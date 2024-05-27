<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MLMDriverCommission extends Model
{
   protected $fillable = ['driver_id','request_id','l1_id','l2_id','l3_id','l4_id','l5_id','l1_com','l2_com','l3_com','l4_com','l5_com'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
         'created_at', 'updated_at'
    ];

    public function driver()
    {
        return $this->belongsTo('App\Provider','driver_id','id');
    }

    public function request()
    {
        return $this->belongsTo('App\UserRequests','request_id', 'id');
    }
}
