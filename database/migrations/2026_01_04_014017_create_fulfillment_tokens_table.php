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
    Schema::create('fulfillment_tokens', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('sale_id');
        $table->unsignedBigInteger('warehouse_id');

        $table->string('token', 100)->unique();
        $table->timestamp('expires_at');

        $table->boolean('used')->default(false);
        $table->timestamp('used_at')->nullable();

        $table->timestamps();

        $table->foreign('sale_id')
              ->references('id')
              ->on('sales')
              ->onDelete('cascade');

        $table->foreign('warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_tokens');
    }
};
