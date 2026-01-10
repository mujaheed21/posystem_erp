<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    public function items()
{
    return $this->hasMany(PurchaseItem::class);
}

public function warehouse()
{
    return $this->belongsTo(Warehouse::class);
}

public function supplier()
{
    return $this->belongsTo(Party::class);
}
}
