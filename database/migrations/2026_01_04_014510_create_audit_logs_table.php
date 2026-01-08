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
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('business_id')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();

        $table->string('action');
        $table->string('module')->nullable();

        $table->string('auditable_type')->nullable();
        $table->unsignedBigInteger('auditable_id')->nullable();

        $table->ipAddress('ip_address')->nullable();
        $table->string('user_agent')->nullable();

        $table->json('metadata')->nullable();

        $table->timestamps();

        $table->foreign('business_id')
              ->references('id')
              ->on('businesses')
              ->onDelete('set null');

        $table->foreign('user_id')
              ->references('id')
              ->on('users')
              ->onDelete('set null');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
