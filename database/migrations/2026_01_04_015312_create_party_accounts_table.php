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
    Schema::create('party_accounts', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('party_id');

        $table->string('account_code')->nullable();

        $table->decimal('opening_balance', 15, 2)->default(0);
        $table->decimal('balance', 15, 2)->default(0);

        $table->timestamps();

        $table->unique('party_id');

        $table->foreign('party_id')
              ->references('id')
              ->on('parties')
              ->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_accounts');
    }
};
