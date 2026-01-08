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
    Schema::create('offline_fulfillment_pendings', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('sale_id');
        $table->unsignedBigInteger('warehouse_id');

        $table->json('payload');

        $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])
              ->default('pending');

        $table->unsignedBigInteger('approved_by')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->timestamp('fulfilled_at')->nullable();
        $table->text('rejected_reason')->nullable();

        $table->timestamps();

        $table->index(['warehouse_id', 'status']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offline_fulfillment_pendings');
    }
};
