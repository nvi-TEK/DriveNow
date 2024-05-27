<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowBlockedHistory extends Model
{
    //
    protected $fillable = [
    'driver_id',
    'official_id',
    'engine_off_reason',
    'engine_off_by',
    'engine_off_on',
    'amount_due' ];

    public function provider()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function official()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }


    public function engine_off()
    {
        return $this->belongsTo('App\Admin', 'engine_off_by','id');
    }
}
