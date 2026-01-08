<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineFulfillmentPending extends Model
{
    protected $table = 'offline_fulfillment_pendings';

    protected $fillable = [
        'sale_id',
        'warehouse_id',
        'payload',
        'status',
        'approved_by',
        'approved_at',
        'fulfilled_at',
        'rejected_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'approved_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];
}
