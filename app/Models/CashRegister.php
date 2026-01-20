<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Added

class CashRegister extends Model
{
    use HasFactory; // Moved inside the class

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
        // We use the static columns for performance, but the Service 
        // can use the relationships for live verification.
        return ($this->opening_amount + $this->total_cash_sales) - $this->total_cash_expenses;
    }

    /**
     * Relationship to Sales (Cash-In)
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relationship to Expenses (Cash-Out)
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
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