<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseFulfillment extends Model
{
    protected $table = 'warehouse_fulfillments';

    protected $fillable = [
        'sale_id',
        'warehouse_id',
        'state',
        'version',
        'verified_by',
        'verified_at',
    ];
}
