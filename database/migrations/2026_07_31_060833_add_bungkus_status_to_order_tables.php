<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_ENUM = "ENUM('baru','dibayar','desain','cetak','finishing','qc','siap_diambil','selesai','batal')";

    private const NEW_ENUM = "ENUM('baru','dibayar','desain','cetak','finishing','qc','bungkus','siap_diambil','selesai','batal')";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['order_indoor' => 'selesai', 'order_outdoor' => 'selesai', 'order_artwork' => 'baru'] as $table => $default) {
            DB::statement("ALTER TABLE {$table} MODIFY status ".self::NEW_ENUM." NOT NULL DEFAULT '{$default}'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['order_indoor' => 'selesai', 'order_outdoor' => 'selesai', 'order_artwork' => 'baru'] as $table => $default) {
            DB::statement("UPDATE {$table} SET status = 'qc' WHERE status = 'bungkus'");
            DB::statement("ALTER TABLE {$table} MODIFY status ".self::OLD_ENUM." NOT NULL DEFAULT '{$default}'");
        }
    }
};
