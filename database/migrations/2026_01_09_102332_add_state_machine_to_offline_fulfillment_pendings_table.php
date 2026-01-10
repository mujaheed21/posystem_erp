<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offline_fulfillment_pendings', function (Blueprint $table) {

            $table->enum('state', [
                'pending',
                'approved',
                'reconciled',
                'rejected',
                'conflicted',
            ])->default('pending')->after('id');

            $table->unsignedInteger('version')->default(1)->after('state');
        });
    }

    public function down(): void
    {
        Schema::table('offline_fulfillment_pendings', function (Blueprint $table) {
            $table->dropColumn(['state', 'version']);
        });
    }
};
