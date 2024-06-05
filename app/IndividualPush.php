<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IndividualPush extends Model
{
    protected $fillable = ['sender_id', 'user_id', 'message', 'driver_id', 'type'];

    public function moderator()
    {
        return $this->belongsTo('App\Admin','sender_id','id');
    }
    public function user()
    {
        return $this->belongsTo('App\Admin','user_id','id');
    }
    public function driver()
    {
        return $this->belongsTo('App\Admin','driver_id','id');
    }
}
