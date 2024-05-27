<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UploadImages extends Model
{
    protected $fillable = ['tempId', 'url', 'user_id', 'request_id', 'driver_id'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
