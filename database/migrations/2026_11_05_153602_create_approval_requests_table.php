<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat lengkap pengajuan Diskon & Hutang VIP (satu baris per pengajuan).
 * Kolom diskon_* dan hutang_* di tabel order hanya menyimpan pengajuan terakhir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10); // diskon | hutang
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->string('detail')->nullable();
            $table->string('reason')->nullable();
            $table->string('status', 10)->default('pending'); // pending | approved | rejected
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('requested_at');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->index(['kind', 'order_type', 'order_id', 'status']);
            $table->index('requested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
