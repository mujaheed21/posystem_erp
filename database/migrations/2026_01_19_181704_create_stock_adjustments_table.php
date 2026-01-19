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
        Schema::create('stock_adjustments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->string('adjustment_number')->unique(); // e.g., ADJ-1737310599
    $table->enum('type', ['damage', 'leakage', 'expired', 'correction', 'theft']);
    $table->text('notes')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
