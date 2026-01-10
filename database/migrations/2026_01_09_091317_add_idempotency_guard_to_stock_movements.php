<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            // Prevent duplicate stock movements per sale
            $table->unique(
                ['reference_type', 'reference_id', 'product_id'],
                'stock_idempotency_guard'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('stock_idempotency_guard');
        });
    }
};
