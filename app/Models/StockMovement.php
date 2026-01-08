<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'business_id',
        'warehouse_id',
        'product_id',
        'type',           // This is the ENUM column
        'quantity',
        'reference_type', 
        'reference_id',
        'created_by'
    ];
}