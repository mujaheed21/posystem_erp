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
    Schema::table('expenses', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->after('note')->constrained('users');
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved')->after('user_id');
        $table->foreignId('approved_by')->nullable()->after('status')->constrained('users');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            //
        });
    }
};
