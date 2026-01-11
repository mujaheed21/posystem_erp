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
    Schema::create('supervisor_overrides', function (Blueprint $table) {
        $table->uuid('id')->primary();

        $table->foreignId('supervisor_id')
            ->constrained('users')
            ->restrictOnDelete();

        $table->string('event_type');
        $table->string('target_type');
        $table->uuid('target_id');

        $table->string('reason_code');
        $table->text('reason_text');

        $table->json('auth_factors');

        $table->string('device_fingerprint');

        $table->string('payload_hash', 64);
        $table->string('prev_hash', 64)->nullable();
        $table->string('record_hash', 64);

        $table->timestamp('created_at')->useCurrent();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervisor_overrides');
    }
};
