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
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('kategori', [
                'bahan_baku', 'gaji', 'listrik_air', 'sewa', 'transportasi', 'perawatan_alat', 'lain_lain',
            ]);
            $table->string('keterangan', 255);
            $table->decimal('jumlah', 12, 2);
            $table->enum('cara_bayar', ['tunai', 'qris', 'transfer']);
            $table->string('no_referensi', 50)->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
