<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MLMUserNetwork extends Model
{
    protected $fillable = ['user_id','l1','l2','l3','l4','l5'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
         'created_at', 'updated_at'
    ];

     public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
