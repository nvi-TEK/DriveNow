<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriverComments extends Model
{
    //
    protected $fillable = ['comments', 'marketer_id', 'driver_id', 'id','reminder', 'attachment'];

    public function provider()
    {
        return $this->belongsTo('App\Provider','driver_id','id');
    }

    public function moderator()
    {
        return $this->belongsTo('App\Admin','marketer_id','id');
    }
}
