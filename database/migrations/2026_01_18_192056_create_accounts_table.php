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
    Schema::create('accounts', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->onDelete('cascade');
        $table->string('name'); // e.g., "Cash at Hand", "Accounts Receivable"
        $table->string('code')->index(); // e.g., "1001", "2001"
        
        // asset, liability, equity, revenue, expense
        $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
        
        $table->boolean('is_system_account')->default(false); 
        $table->timestamps();
        
        $table->unique(['business_id', 'code']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
