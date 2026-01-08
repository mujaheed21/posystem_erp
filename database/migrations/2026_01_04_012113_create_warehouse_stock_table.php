<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('warehouse_stock', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('warehouse_id');
        $table->unsignedBigInteger('product_id');

        $table->decimal('quantity', 15, 3)->default(0);
        $table->decimal('reserved_quantity', 15, 3)->default(0);

        $table->timestamps();

        $table->unique(
            ['warehouse_id', 'product_id'],
            'warehouse_product_unique'
        );

        $table->foreign('warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('cascade');

        $table->foreign('product_id')
              ->references('id')
              ->on('products')
              ->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock');
    }
};
