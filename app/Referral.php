<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
   protected $fillable = [
	'name',
	'mobile',
	'email',
	'type',
	'referred'
	];
}
