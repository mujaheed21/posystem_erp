<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfflineFulfillmentPending extends Model
{
    use HasFactory;
    protected $table = 'offline_fulfillment_pendings';

    protected $fillable = [
        'sale_id',
        'warehouse_id',
        'payload',
        'state',
        'approved_by',
        'approved_at',
        'fulfilled_at',
        'rejected_reason',
        'requires_override', // 🔒 REQUIRED
    ];

    protected $casts = [
        'payload'      => 'array',
        'approved_at'  => 'datetime',
        'fulfilled_at' => 'datetime',
        'requires_override' => 'boolean',
    ];

    /**
     * Guard against accidental use of `status`
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->isDirty('status')) {
                throw new \RuntimeException(
                    'OfflineFulfillmentPending must not use `status`; use `state`.'
                );
            }
        });
    }
}
