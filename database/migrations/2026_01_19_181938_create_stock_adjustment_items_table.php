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
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_adjustment_id')->constrained()->onDelete('cascade');
    $table->foreignId('product_id')->constrained();
    $table->foreignId('stock_batch_id')->constrained(); // Links to the specific cost layer
    $table->decimal('quantity', 15, 3);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
