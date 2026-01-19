<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'business_id',
        'name',
        'code',
        'ledger_account_id',
        'description'
    ];

    /**
     * The Ledger Account associated with this category.
     * Every expense under this category will post to this account.
     */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ledger_account_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}