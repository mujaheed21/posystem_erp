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
    Schema::table('fulfillment_tokens', function (Blueprint $table) {
        $table->string('token_hash', 64)->unique()->after('id');
        $table->string('items_hash', 64)->after('token_hash');

        // Optional but recommended
        $table->index(['token_hash', 'used']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('fulfillment_tokens', function (Blueprint $table) {
        $table->dropIndex(['token_hash', 'used']);
        $table->dropColumn(['token_hash', 'items_hash']);
    });
}
};
