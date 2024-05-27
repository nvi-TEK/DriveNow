<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MLMDriverNetwork extends Model
{
    protected $fillable = ['driver_id','l1','l2','l3','l4','l5'];

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
}
