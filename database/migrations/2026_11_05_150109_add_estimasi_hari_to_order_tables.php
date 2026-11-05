<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = ['order_indoor', 'order_outdoor', 'order_artwork'];

    /**
     * Estimasi pengerjaan (hari) yang diisi Operator File; kosong = 1 hari.
     * Progress Hari dari Lembar Kerja CS tetap didahulukan.
     */
    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedTinyInteger('estimasi_hari')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('estimasi_hari');
            });
        }
    }
};
