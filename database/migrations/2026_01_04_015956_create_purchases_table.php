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
    Schema::create('purchases', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('business_id');
        $table->unsignedBigInteger('warehouse_id');
        $table->unsignedBigInteger('supplier_id'); // parties.id

        $table->string('purchase_number')->unique();

        $table->decimal('subtotal', 15, 2)->default(0);
        $table->decimal('tax', 15, 2)->default(0);
        $table->decimal('total', 15, 2)->default(0);

        $table->enum('status', ['draft', 'received', 'cancelled'])
              ->default('received');

        $table->unsignedBigInteger('created_by');
        $table->timestamps();

        $table->foreign('business_id')
              ->references('id')
              ->on('businesses')
              ->onDelete('cascade');

        $table->foreign('warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('cascade');

        $table->foreign('supplier_id')
              ->references('id')
              ->on('parties')
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
        Schema::dropIfExists('purchases');
    }
};
