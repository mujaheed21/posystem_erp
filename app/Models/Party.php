<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'active'
    ];
}