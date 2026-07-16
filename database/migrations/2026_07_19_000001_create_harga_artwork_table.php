<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the z_produk_NamaProduk_INDOOR_CEK (Produk) schema — this table
     * holds the "artwork" categories (flatbed, finishing, cutting, DTG/DTF,
     * dye sub, ID card, produk jadi) carved out of the main indoor price list
     * into their own managed module.
     */
    public function up(): void
    {
        Schema::create('harga_artwork', function (Blueprint $table) {
            $table->id();
            $table->string('KdProd', 4)->unique();
            $table->string('KdDivs', 2)->nullable();
            $table->string('NmProd', 30);
            $table->unsignedInteger('NoUrut');
            $table->double('HargaStd');
            $table->double('HargaMin');
            $table->string('Satuan', 8);
            $table->tinyInteger('isPjLb');
            $table->tinyInteger('isHPilih');
            $table->timestamps();

            $table->index('KdDivs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_artwork');
    }
};
