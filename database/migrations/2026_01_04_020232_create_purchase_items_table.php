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
    Schema::create('purchase_items', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('purchase_id');
        $table->unsignedBigInteger('product_id');

        $table->decimal('quantity', 15, 3);
        $table->decimal('unit_cost', 15, 2);
        $table->decimal('total_cost', 15, 2);

        $table->timestamps();

        $table->foreign('purchase_id')
              ->references('id')
              ->on('purchases')
              ->onDelete('cascade');

        $table->foreign('product_id')
              ->references('id')
              ->on('products')
              ->onDelete('restrict');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
