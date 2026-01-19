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
    Schema::table('stock_transfers', function (Blueprint $table) {
        // We use 64 chars to support high-entropy random strings
        $table->string('verification_token', 64)->nullable()->unique()->after('transfer_number');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            //
        });
    }
};
