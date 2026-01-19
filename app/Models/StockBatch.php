<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'warehouse_id',
        'product_id',
        'purchase_id',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'received_at'
    ];

    protected $casts = [
        'received_at' => 'timestamp',
        'quantity_remaining' => 'float',
        'unit_cost' => 'float',
    ];
}