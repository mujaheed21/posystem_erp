<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_fulfillments', function (Blueprint $table) {
            // State machine authority
            $table->enum('state', [
                'pending',
                'approved',
                'released',
                'reconciled',
                'conflicted',
            ])->default('pending')->after('id');

            // Optimistic locking version
            $table->unsignedInteger('version')->default(1)->after('state');

            // Enforce one fulfillment per sale
            $table->unique('sale_id', 'wf_sale_unique');
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_fulfillments', function (Blueprint $table) {
            $table->dropUnique('wf_sale_unique');
            $table->dropColumn(['state', 'version']);
        });
    }
};
