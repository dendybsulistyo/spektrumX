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
        // Discount can now be requested either as a percentage (existing
        // diskon_persen/diskon_requested_persen) or a flat Rupiah amount —
        // diskon_tipe records which one is active so diskonNominal() knows
        // which pair of columns to read.
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->enum('diskon_tipe', ['persen', 'nominal'])->nullable()->after('diskon_alasan');
                $table->decimal('diskon_requested_nominal', 12, 2)->nullable()->after('diskon_requested_persen');
                $table->decimal('diskon_nominal_tetap', 12, 2)->nullable()->after('diskon_persen');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['diskon_tipe', 'diskon_requested_nominal', 'diskon_nominal_tetap']);
            });
        }
    }
};
