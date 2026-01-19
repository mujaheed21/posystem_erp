<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'from_warehouse_id', 'to_warehouse_id', 
        'transfer_number', 'status', 'created_by', 'received_by', 
        'dispatched_at', 'received_at', 'notes', 'verification_token'
    ];

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}