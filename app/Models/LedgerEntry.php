<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
   protected $fillable = [
    'business_id', 
    'account_id', 
    'debit', 
    'credit', 
    'source_type', 
    'source_id', 
    'description', 
    'user_id', 
    'posted_at'
];
}
