<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Token acak per order untuk QR tracking customer (tidak bisa ditebak dari
 * nomor order), plus penanda status terakhir yang sudah terkirim ke Worker.
 * Token diisi untuk order mulai 14 Okt 2026; order baru mendapat token saat dibuat.
 */
return new class extends Migration
{
    private const TABEL = ['order_indoor', 'order_outdoor', 'order_artwork'];

    private const MULAI = '2026-10-14';

    public function up(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->string('tracking_token', 32)->nullable()->unique();
                $table->string('tracking_pushed_status', 30)->nullable();
                $table->timestamp('tracking_pushed_at')->nullable();
            });

            DB::table($tabel)
                ->where('TglOrder', '>=', self::MULAI)
                ->whereNull('tracking_token')
                ->orderBy('id')
                ->pluck('id')
                ->each(fn ($id) => DB::table($tabel)->where('id', $id)->update(['tracking_token' => Str::random(22)]));
        }
    }

    public function down(): void
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropUnique(['tracking_token']);
                $table->dropColumn(['tracking_token', 'tracking_pushed_status', 'tracking_pushed_at']);
            });
        }
    }
};
