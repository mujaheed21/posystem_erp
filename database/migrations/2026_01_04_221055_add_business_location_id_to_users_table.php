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
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('business_location_id')
              ->nullable()
              ->constrained('business_locations')
              ->nullOnDelete()
              ->after('business_id');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['business_location_id']);
        $table->dropColumn('business_location_id');
    });
}
};
