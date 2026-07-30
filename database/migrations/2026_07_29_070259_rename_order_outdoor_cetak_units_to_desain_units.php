<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-unit tracking for Order Outdoor turned out to belong to the Desain
     * stage, not Cetak — renaming rather than dropping/recreating so any
     * units already ticked off stay recorded.
     */
    public function up(): void
    {
        Schema::rename('order_outdoor_cetak_units', 'order_outdoor_desain_units');

        // MySQL 5.7 doesn't support the `RENAME COLUMN` shorthand (8.0+
        // only) — CHANGE COLUMN needs the full column definition restated.
        DB::statement('ALTER TABLE order_outdoor_desain_units CHANGE COLUMN cetak_by desain_by BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE order_outdoor_desain_units CHANGE COLUMN cetak_at desain_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE order_outdoor_desain_units CHANGE COLUMN desain_by cetak_by BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE order_outdoor_desain_units CHANGE COLUMN desain_at cetak_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');

        Schema::rename('order_outdoor_desain_units', 'order_outdoor_cetak_units');
    }
};
