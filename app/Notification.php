<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id','driver_id','status','title','message','request_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
      'updated_at', 'deleted_at'
    ];

    public function driver()
    {
        return $this->belongsTo('App\Provider','driver_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function request()
    {
        return $this->belongsTo('App\UserRequests', 'request_id', 'id');
    }
}
