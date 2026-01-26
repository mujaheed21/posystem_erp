<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class OfflineFulfillmentPending extends Model
{
    use HasFactory;

    protected $table = 'offline_fulfillment_pendings';

    protected $fillable = [
        'sale_id',
        'warehouse_id',
        'business_id',
        'payload',
        'state',
        'token',
        'approved_by',
        'approved_at',
        'fulfilled_at',
        'rejected_reason',
        'requires_override',
    ];

    protected $casts = [
        'payload'           => 'array',
        'approved_at'       => 'datetime',
        'fulfilled_at'      => 'datetime',
        'requires_override' => 'boolean',
    ];

    /**
     * Guard against accidental use of `status` and handle token generation.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            // Automatically generate a secure, unique token if none is set
            if (empty($model->token)) {
                $model->token = 'FLF-' . strtoupper(Str::random(12));
            }
        });

        static::saving(function ($model) {
            if ($model->isDirty('status')) {
                throw new \RuntimeException(
                    'OfflineFulfillmentPending must not use `status`; use `state`.'
                );
            }
        });
    }
}