<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    // This explicitly maps to your 'businesses' table
    protected $table = 'businesses'; 
    
    protected $guarded = ['id'];

    // Relationship: A Business has many Locations
    public function locations(): HasMany
    {
        return $this->hasMany(BusinessLocation::class);
    }

    // Relationship: A Business has many Warehouses
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }
}