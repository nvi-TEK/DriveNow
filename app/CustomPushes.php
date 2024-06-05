<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomPushes extends Model
{
    use SoftDeletes;

    protected $fillable = ['sender_id', 'receiver_count', 'message', 'group', 'type', 'range'];

    protected $hidden = [
        'password', 'remember_token','deleted_at'
    ];

    public function moderator()
    {
        return $this->belongsTo('App\Admin','sender_id','id');
    }
}
