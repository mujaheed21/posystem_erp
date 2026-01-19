<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $fillable = [
    'business_id', 'warehouse_id', 'adjustment_number', 'type', 'notes', 'created_by'
];

public function items() {
    return $this->hasMany(StockAdjustmentItem::class);
}
}
