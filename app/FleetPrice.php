<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FleetPrice extends Model
{
    protected $hidden = [
        'created_at', 'updated_at'
    ];

    public function fleet()
    {
        return $this->belongsTo('App\Fleet','fleet_id');
    }

    public function service()
    {
        return $this->belongsTo('App\ServiceType','service_id');
    }
}
