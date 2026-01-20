<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends Model
{
    use HasFactory; // <--- ADD THIS LINE HERE

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'ledger_account_id',
        'description'
    ];

    // ... (rest of your methods)
}