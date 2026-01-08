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
    Schema::create('stock_movements', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('business_id');
        $table->unsignedBigInteger('warehouse_id');
        $table->unsignedBigInteger('product_id');

        $table->enum('type', [
            'opening',
            'purchase',
            'sale',
            'transfer_in',
            'transfer_out',
            'adjustment',
            'return'
        ]);

        $table->decimal('quantity', 15, 3);

        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();

        $table->unsignedBigInteger('created_by')->nullable();
        $table->timestamps();

        $table->foreign('business_id')
              ->references('id')
              ->on('businesses')
              ->onDelete('cascade');

        $table->foreign('warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('cascade');

        $table->foreign('product_id')
              ->references('id')
              ->on('products')
              ->onDelete('cascade');

        $table->foreign('created_by')
              ->references('id')
              ->on('users')
              ->onDelete('set null');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
