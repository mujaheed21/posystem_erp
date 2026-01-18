<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
   protected $fillable = [
    'business_id', 
    'name', 
    'code', 
    'type', 
    'is_system_account'
];
}
