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
        Schema::create('supplier_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_id')->constrained();
    $table->foreignId('party_id')->constrained(); // The Supplier
    $table->foreignId('purchase_id')->nullable()->constrained(); // Optional: Link to specific invoice
    $table->decimal('amount', 15, 2);
    $table->string('payment_method'); // cash, bank_transfer, cheque
    $table->string('reference_no')->nullable();
    $table->date('paid_at');
    $table->text('notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
