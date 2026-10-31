<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Produk "tidak diproses operator" (komponen harga: kresek, CTcP, dsb.):
 * item SO-nya langsung selesai sejak SO dibuat, tidak masuk antrean
 * Layout s/d Pengambilan, dan ikut tercantum di DO (lihat TanpaProsesService).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['produk_indoor', 'harga_artwork'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('diproses')->default(true);
            });
        }
        foreach (['order_indoor_detail', 'order_artwork_detail'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('tanpa_proses')->default(false);
            });
        }

        // Keputusan 31 Okt 2026: kresek & CTcP (divisi 09) tidak diproses operator.
        DB::table('produk_indoor')->whereIn('KdProd', ['2804', '2106'])->update(['diproses' => false]);
        DB::table('produk_indoor')->where(fn ($q) => $q->where('KdDivs', '09')->orWhere('KdProd', 'like', '09%'))->update(['diproses' => false]);
        DB::table('harga_artwork')->where(fn ($q) => $q->where('KdDivs', '09')->orWhere('KdProd', 'like', '09%'))->update(['diproses' => false]);
    }

    public function down(): void
    {
        foreach (['order_indoor_detail', 'order_artwork_detail'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('tanpa_proses'));
        }
        foreach (['produk_indoor', 'harga_artwork'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('diproses'));
        }
    }
};
