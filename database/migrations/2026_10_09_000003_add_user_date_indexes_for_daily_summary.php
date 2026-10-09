<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks gabungan (user + waktu) agar "Ringkasan hari ini" & laporan per
 * user tetap cepat walau riwayat sudah bertahun-tahun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_status_notes', fn (Blueprint $table) => $table->index(['user_id', 'created_at'], 'osn_user_created_idx'));
        Schema::table('order_payments', fn (Blueprint $table) => $table->index(['user_id', 'created_at'], 'op_user_created_idx'));
    }

    public function down(): void
    {
        Schema::table('order_status_notes', fn (Blueprint $table) => $table->dropIndex('osn_user_created_idx'));
        Schema::table('order_payments', fn (Blueprint $table) => $table->dropIndex('op_user_created_idx'));
    }
};
