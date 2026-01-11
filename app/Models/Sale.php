<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Sale extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function items()
{
    return $this->hasMany(SaleItem::class);
}

public function warehouse()
{
    return $this->belongsTo(Warehouse::class);
}
}
