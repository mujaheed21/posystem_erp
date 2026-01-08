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
    Schema::create('products', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('business_id');

        $table->string('name');
        $table->string('sku')->nullable();
        $table->string('barcode')->nullable();

        $table->enum('type', ['stock', 'service'])
              ->default('stock');

        $table->decimal('cost_price', 15, 2)->default(0);
        $table->decimal('selling_price', 15, 2)->default(0);

        $table->boolean('track_inventory')->default(true);
        $table->boolean('active')->default(true);

        $table->timestamps();

        $table->foreign('business_id')
              ->references('id')
              ->on('businesses')
              ->onDelete('cascade');

        $table->unique(['business_id', 'sku'], 'business_sku_unique');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
