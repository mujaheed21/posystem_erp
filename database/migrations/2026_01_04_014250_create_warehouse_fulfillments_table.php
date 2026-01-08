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
    Schema::create('warehouse_fulfillments', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('sale_id');
        $table->unsignedBigInteger('warehouse_id');
        $table->unsignedBigInteger('verified_by'); // warehouse staff user

        $table->timestamp('verified_at');
        $table->text('remarks')->nullable();

        $table->timestamps();

        $table->foreign('sale_id')
              ->references('id')
              ->on('sales')
              ->onDelete('cascade');

        $table->foreign('warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('cascade');

        $table->foreign('verified_by')
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
        Schema::dropIfExists('warehouse_fulfillments');
    }
};
