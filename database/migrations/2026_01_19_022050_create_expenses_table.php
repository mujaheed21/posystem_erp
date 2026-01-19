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
    Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('business_id')->constrained();
        $table->foreignId('expense_category_id')->constrained();
        $table->foreignId('cash_register_id')->constrained();
        $table->foreignId('business_location_id')->constrained();
        
        $table->string('ref_no');
        $table->decimal('amount', 15, 2);
        $table->date('operation_date');
        $table->text('note')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
