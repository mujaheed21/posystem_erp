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
    Schema::create('stock_transfers', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('business_id');

        $table->unsignedBigInteger('from_warehouse_id');
        $table->unsignedBigInteger('to_warehouse_id');

        $table->string('transfer_number')->unique();

        $table->enum('status', ['draft', 'in_transit', 'completed', 'cancelled'])
              ->default('completed');

        $table->unsignedBigInteger('created_by');
        $table->timestamps();

        $table->foreign('business_id')
              ->references('id')
              ->on('businesses')
              ->onDelete('cascade');

        $table->foreign('from_warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('restrict');

        $table->foreign('to_warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('restrict');

        $table->foreign('created_by')
              ->references('id')
              ->on('users')
              ->onDelete('restrict');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
