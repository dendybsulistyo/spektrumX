<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Order proof (7 Nov 2026): contoh kecil sebelum ukuran akhir pasti. Tagihan 0, hanya lewat Layout,
 * lalu dibuatkan satu order reguler. Disimpan terpisah dari tabel order agar tidak masuk kasir/laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_proofs', function (Blueprint $table) {
            $table->id();
            $table->string('NoOrder', 30)->unique();
            $table->string('order_type', 10);
            $table->string('KdCust', 20)->nullable();
            $table->text('catatan')->nullable();
            $table->string('status', 15)->default('layout');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('layout_done_at')->nullable();
            $table->foreignId('layout_done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('converted_order_type', 10)->nullable();
            $table->unsignedBigInteger('converted_order_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('close_reason', 255)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['converted_order_type', 'converted_order_id']);
        });

        Schema::create('order_proof_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_proof_id')->constrained()->cascadeOnDelete();
            $table->string('NmFile', 255)->nullable();
            $table->string('kode', 20)->nullable();
            $table->string('jenis_produk', 10)->nullable();
            $table->decimal('Panjang', 10, 2)->nullable();
            $table->decimal('Lebar', 10, 2)->nullable();
            $table->unsignedInteger('Qty')->default(1);
            $table->string('catatan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_proof_items');
        Schema::dropIfExists('order_proofs');
    }
};
