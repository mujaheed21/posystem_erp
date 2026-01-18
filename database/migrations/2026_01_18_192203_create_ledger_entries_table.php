<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('ledger_entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->onDelete('cascade');
        $table->foreignId('account_id')->constrained();
        
        // Double Entry columns
        $table->decimal('debit', 15, 2)->default(0);
        $table->decimal('credit', 15, 2)->default(0);
        
        // Polymorphic link to Sales, Purchases, etc.
        $table->nullableMorphs('source'); 
        
        $table->string('description')->nullable();
        $table->foreignId('user_id')->constrained(); // Audit Attribution
        
        $table->timestamp('posted_at')->useCurrent();
        $table->timestamps();

        $table->index(['business_id', 'posted_at']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
