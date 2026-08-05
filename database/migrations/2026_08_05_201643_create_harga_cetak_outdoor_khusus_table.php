<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-customer price override for Outdoor's Printer × Bahan matrix
     * (harga_cetak_outdoor) — for VIP customers who get a special rate on
     * specific bahan/printer combos instead of the shop-wide standard.
     * Same KdCtk shape (KdPrn + NoCetak) as harga_cetak_outdoor, just scoped
     * to one KdCust. Missing row = falls back to the standard price.
     *
     * KdCust is a plain indexed column (not a formal FK) — same convention
     * as order_indoor/order_outdoor/order_artwork's KdCust, since customers
     * is a legacy table with no FK constraints referencing it anywhere.
     */
    public function up(): void
    {
        Schema::create('harga_cetak_outdoor_khusus', function (Blueprint $table) {
            $table->id();
            $table->string('KdCust', 6);
            $table->string('KdCtk', 4);
            $table->decimal('HargaStd', 12, 2);
            $table->decimal('HargaMin', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['KdCust', 'KdCtk']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_cetak_outdoor_khusus');
    }
};
