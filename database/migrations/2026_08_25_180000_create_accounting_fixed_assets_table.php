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
        Schema::create('accounting_fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->enum('kelompok', ['I', 'II', 'III', 'IV', 'bangunan_permanen', 'bangunan_semi']);
            $table->date('tanggal_perolehan');
            $table->decimal('harga_perolehan', 15, 2);
            $table->enum('metode', ['garis_lurus', 'saldo_menurun'])->default('garis_lurus');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_fixed_assets');
    }
};
