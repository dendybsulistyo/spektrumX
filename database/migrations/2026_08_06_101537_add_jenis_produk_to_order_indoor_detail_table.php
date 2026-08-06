<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order Indoor is being extended to also accept Artwork-catalog products
     * in the same order/nota (client wants one nota instead of two when a
     * customer orders both). Each line item now records which catalog it
     * was priced from — 'indoor' (produk_indoor/Produk) or 'artwork'
     * (harga_artwork/HargaArtwork) — so pricing/desain-queue logic can look
     * up the right table and formula per item. Default 'indoor' is correct
     * for every existing row (they're all indoor products already), so no
     * backfill is needed.
     */
    public function up(): void
    {
        Schema::table('order_indoor_detail', function (Blueprint $table) {
            $table->enum('jenis_produk', ['indoor', 'artwork'])->default('indoor')->after('KdProd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_indoor_detail', function (Blueprint $table) {
            $table->dropColumn('jenis_produk');
        });
    }
};
