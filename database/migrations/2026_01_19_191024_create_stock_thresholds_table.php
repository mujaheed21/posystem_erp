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
        Schema::create('stock_thresholds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->decimal('min_level', 15, 3)->default(0); // The "Alert" trigger point
    $table->decimal('reorder_qty', 15, 3)->default(0); // Suggested restock amount
    $table->timestamps();
    
    // Ensure one threshold per product per location
    $table->unique(['warehouse_id', 'product_id']); 
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_thresholds');
    }
};
