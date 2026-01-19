<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'stock_batch_id',
        'quantity'
    ];

    /**
     * Relationship to the parent transfer.
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    /**
     * Relationship to the specific FIFO batch being moved.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    /**
     * Relationship to the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}