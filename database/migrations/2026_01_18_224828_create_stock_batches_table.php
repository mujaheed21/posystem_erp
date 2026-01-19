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
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('purchase_id')->constrained(); // Origin of the stock
            $table->decimal('quantity_received', 15, 3);
            $table->decimal('quantity_remaining', 15, 3); // Vital for FIFO
            $table->decimal('unit_cost', 15, 2);
            $table->timestamp('received_at');
            $table->timestamps();
            
            // Indexing for FIFO performance
            $table->index(['product_id', 'warehouse_id', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
