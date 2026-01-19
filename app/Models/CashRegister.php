<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegister extends Model
{
    protected $fillable = [
        'business_id',
        'user_id',
        'business_location_id',
        'status',
        'opening_amount',
        'closing_amount',
        'total_cash_sales',
        'total_cash_expenses',
        'closing_note',
        'closed_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    /**
     * Audit Logic: What the drawer SHOULD have.
     * Opening + Sales - Expenses
     */
    public function getExpectedBalanceAttribute(): float
    {
        return ($this->opening_amount + $this->total_cash_sales) - $this->total_cash_expenses;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'business_location_id');
    }
}