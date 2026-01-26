<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FulfillmentToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_hash',
        'items_hash',
        'sale_id',
        'warehouse_id',
        'expires_at',
        'used',
        'used_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'used'       => 'boolean'
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}