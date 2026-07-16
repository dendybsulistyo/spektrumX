<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * indm never had a real creation timestamp (legacy table, $timestamps = false).
     * Add one nullable column so the new order pipeline can record entry time —
     * left null for historical rows (their true entry time isn't recoverable);
     * the dashboard only counts rows where this is set as "new pipeline" orders.
     */
    public function up(): void
    {
        Schema::table('indm', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable()->after('diambil_at');
        });
    }

    public function down(): void
    {
        Schema::table('indm', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
};
