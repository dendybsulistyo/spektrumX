<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A single payment can now be split across multiple cara_bayar (e.g. DP
     * 50rb paid as 25rb QRIS + 25rb transfer) — each split still lands as
     * its own OrderPayment row, but the order's own single cara_bayar
     * column can no longer represent it accurately, so 'campuran' is the
     * value stored whenever more than one distinct method was used.
     */
    public function up(): void
    {
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `cara_bayar` ENUM('tunai', 'qris', 'transfer', 'campuran') NULL");
        }
    }

    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            DB::statement("UPDATE `{$table}` SET `cara_bayar` = 'tunai' WHERE `cara_bayar` = 'campuran'");
            DB::statement("ALTER TABLE `{$table}` MODIFY `cara_bayar` ENUM('tunai', 'qris', 'transfer') NULL");
        }
    }
};
