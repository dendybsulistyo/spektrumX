<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arsip order yang dihapus lewat spektrum:hapus-order: salinan order, item,
 * pembayaran, dokumen & jurnalnya, beserta alasan dan siapa yang menghapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('deleted_order_logs')) {
            return;
        }

        Schema::create('deleted_order_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->string('no_order', 40)->index();
            $table->string('kd_cust', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->date('tgl_order')->nullable();
            $table->string('status', 20)->nullable();
            $table->string('status_bayar', 20)->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('kredit_dikembalikan', 15, 2)->default(0);
            $table->string('alasan');
            $table->string('dihapus_oleh', 100)->nullable();
            $table->json('snapshot');
            $table->timestamp('deleted_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_order_logs');
    }
};
