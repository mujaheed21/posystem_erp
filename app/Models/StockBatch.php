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

    /**
     * The attributes that should be cast.
     * Use decimal casts to prevent floating-point math errors in COGS.
     */
    protected $casts = [
        'received_at' => 'datetime',
        'quantity_received' => 'decimal:3',
        'quantity_remaining' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Invariant: A batch is exhausted when quantity_remaining is 0.
     */
    public function isExhausted(): bool
    {
        return $this->quantity_remaining <= 0;
    }
}