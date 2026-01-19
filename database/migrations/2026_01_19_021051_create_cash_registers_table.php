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
    Schema::create('cash_registers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('business_location_id')->constrained()->onDelete('cascade');
        
        $table->enum('status', ['open', 'closed'])->default('open');
        
        // Financial Tracking
        $table->decimal('opening_amount', 15, 2)->default(0);
        $table->decimal('closing_amount', 15, 2)->nullable(); // Actual physical count at end
        $table->decimal('total_cash_sales', 15, 2)->default(0);
        $table->decimal('total_cash_expenses', 15, 2)->default(0);
        
        $table->text('closing_note')->nullable();
        $table->timestamp('closed_at')->nullable();
        $table->timestamps();

        // Indexing for quick retrieval of a user's active register
        $table->index(['user_id', 'status']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
