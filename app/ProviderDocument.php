<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderDocument extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'provider_id',
        'document_id',
        'url',
        'unique_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'create_at', 'updated_at', 'deleted_at'
    ];

    /**
     * The services that belong to the user.
     */
    public function provider()
    {
        return $this->belongsTo('App\Provider');
    }
    /**
     * The services that belong to the user.
     */
    public function document()
    {
        return $this->belongsTo('App\Document');
    }
}
