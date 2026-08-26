<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('accounting_suppliers')->restrictOnDelete();
            $table->date('tanggal')->index();
            $table->string('nomor_bukti', 50)->unique();
            $table->string('keterangan', 255);
            $table->string('akun_pembelian', 6);
            $table->decimal('dpp', 18, 2);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('total', 18, 2);
            $table->enum('status', ['tunai', 'hutang', 'lunas']);
            $table->enum('cara_bayar', ['tunai', 'qris', 'transfer'])->nullable();
            $table->string('no_referensi', 50)->nullable();
            $table->decimal('jumlah_dibayar', 18, 2)->default(0);
            $table->decimal('jumlah_hutang', 18, 2)->default(0)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('no_trans_jurnal', 14)->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('accounting_purchases')->cascadeOnDelete();
            $table->date('tanggal')->index();
            $table->decimal('jumlah', 18, 2);
            $table->enum('cara_bayar', ['tunai', 'qris', 'transfer']);
            $table->string('no_referensi', 50)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('no_trans_jurnal', 14)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_purchase_payments');
        Schema::dropIfExists('accounting_purchases');
    }
};
