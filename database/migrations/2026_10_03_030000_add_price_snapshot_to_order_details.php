<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['order_indoor_detail', 'order_outdoor_detail', 'order_artwork_detail'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Price actually charged when the order total was computed.
                // Null for orders saved before this column existed.
                $table->decimal('harga_satuan_snapshot', 15, 2)->nullable();
                $table->decimal('subtotal_snapshot', 15, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['harga_satuan_snapshot', 'subtotal_snapshot']);
            });
        }
    }
};
