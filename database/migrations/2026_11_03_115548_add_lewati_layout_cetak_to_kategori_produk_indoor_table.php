<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori yang item-nya tidak lewat Layout & Cetak (mis. stand banner):
 * item SO langsung masuk Finishing, lalu lanjut seperti biasa.
 * Default false → kategori yang sudah ada tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('kategori_produk_indoor', 'lewati_layout_cetak')) {
            return;
        }

        Schema::table('kategori_produk_indoor', function (Blueprint $table) {
            $table->boolean('lewati_layout_cetak')->default(false);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('kategori_produk_indoor', 'lewati_layout_cetak')) {
            Schema::table('kategori_produk_indoor', fn (Blueprint $table) => $table->dropColumn('lewati_layout_cetak'));
        }
    }
};
