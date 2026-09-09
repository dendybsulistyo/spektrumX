<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (['indoor' => 'order_indoor', 'outdoor' => 'order_outdoor', 'artwork' => 'order_artwork'] as $type => $table) {
                DB::table('order_documents as document')
                    ->join($table.' as orders', 'orders.id', '=', 'document.order_id')
                    ->where('document.kind', 'inv')
                    ->where('document.order_type', $type)
                    ->where('document.snapshot->origin', 'historical_demo')
                    ->whereNotNull('orders.dibayar_at')
                    ->update(['document.issued_at' => DB::raw('orders.dibayar_at')]);
            }
        });
    }

    public function down(): void
    {
        // The incorrect import timestamp was not business data and cannot be
        // reconstructed safely. Rolling back keeps the corrected dates.
    }
};
