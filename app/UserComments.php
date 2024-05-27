<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserComments extends Model
{
    protected $fillable = ['comments', 'marketer_id', 'user_id', 'id','reminder', 'attachment'];

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }

    public function moderator()
    {
        return $this->belongsTo('App\Admin','marketer_id','id');
    }
}
