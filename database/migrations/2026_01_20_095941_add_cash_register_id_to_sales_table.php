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
    Schema::table('sales', function (Blueprint $table) {
        // We add the column after business_id for logical grouping
        $table->foreignId('cash_register_id')
              ->nullable()
              ->after('business_id')
              ->constrained('cash_registers');
    });
}

public function down(): void
{
    Schema::table('sales', function (Blueprint $table) {
        $table->dropForeign(['cash_register_id']);
        $table->dropColumn('cash_register_id');
    });
}
};
