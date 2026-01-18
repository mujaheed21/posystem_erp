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
        Schema::create('payments', function (Illuminate\Database\Schema\Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('business_id');
    $table->unsignedBigInteger('party_id'); // The Supplier or Customer
    $table->unsignedBigInteger('account_id'); // Cash/Bank account used
    $table->decimal('amount', 15, 2);
    $table->dateTime('payment_date');
    $table->string('reference')->nullable();
    $table->text('description')->nullable();
    $table->unsignedBigInteger('created_by');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
