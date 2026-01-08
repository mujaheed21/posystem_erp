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
    Schema::create('business_location_warehouse', function (Blueprint $table) {
        $table->id();

        $table->unsignedBigInteger('business_location_id');
        $table->unsignedBigInteger('warehouse_id');

        $table->enum('access_level', ['view', 'fulfill', 'transfer'])
              ->default('view');

        $table->boolean('is_default')->default(false);
        $table->boolean('active')->default(true);

        $table->timestamps();

       $table->unique(
    ['business_location_id', 'warehouse_id'],
    'bl_warehouse_unique'
);


        $table->foreign('business_location_id')
              ->references('id')
              ->on('business_locations')
              ->onDelete('cascade');

        $table->foreign('warehouse_id')
              ->references('id')
              ->on('warehouses')
              ->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_location_warehouse');
    }
};
