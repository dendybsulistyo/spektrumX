<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_indoor', 'order_outdoor'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->decimal('cs_order_total', 15, 2)->nullable()->after('cs_transfer_amount');
            });

            DB::table($table)
                ->whereNotNull('cs_processed_at')
                ->whereNull('cs_order_total')
                ->update(['cs_order_total' => DB::raw('total')]);
        }
    }

    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('cs_order_total');
            });
        }
    }
};
