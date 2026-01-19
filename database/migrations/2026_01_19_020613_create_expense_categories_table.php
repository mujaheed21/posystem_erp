<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // The name the user sees (e.g., "Electricity Bills")
            $table->string('name');
            $table->string('code')->nullable(); // Optional: For quick entry (e.g., 'ELEC')

            // MANDATORY MAPPING: Links this category to a specific Ledger Account
            // e.g., Maps to "Account 6001: Utilities Expense"
            $table->foreignId('ledger_account_id')
                  ->constrained('accounts')
                  ->onDelete('restrict'); 

            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexing for performance in high-volume environments like Singer Market
            $table->index(['business_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};