<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; // 1. Add this

class Account extends Model
{
    use HasFactory; // 2. Add this inside the class

    protected $fillable = [
        'business_id', 
        'name', 
        'code', 
        'type', 
        'is_system_account'
    ];
}