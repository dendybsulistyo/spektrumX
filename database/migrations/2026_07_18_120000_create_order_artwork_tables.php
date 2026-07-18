<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fresh tables (not a legacy rename) — mirrors order_outdoor's structure
     * (proper FK on the detail table, no legacy BrsOrder-prefix lookups) with
     * the same payment/production pipeline columns as order_indoor/order_outdoor
     * so this can be wired into Kasir/Desain/Cetak/QC/Pengambilan later without
     * another schema change. Line items reference harga_artwork via KdProd,
     * same shape as order_indoor_detail (KdProd/NmProd/Judul/Panjang/Lebar/Qty).
     */
    public function up(): void
    {
        Schema::create('order_artwork', function (Blueprint $table) {
            $table->id();
            $table->string('NoOrder', 20)->unique();
            $table->date('TglOrder');
            $table->string('KdCust', 6);
            $table->boolean('Cetak')->default(false);
            $table->decimal('total', 12, 2)->nullable();

            $table->enum('status_bayar', ['belum_bayar', 'lunas', 'hutang'])->default('belum_bayar');
            $table->enum('metode_bayar', ['tunai', 'hutang'])->nullable();
            $table->decimal('jumlah_dibayar', 12, 2)->default(0);
            $table->decimal('jumlah_piutang', 12, 2)->default(0);
            $table->foreignId('kasir_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibayar_at')->nullable();

            $table->enum('status', [
                'baru', 'dibayar', 'desain', 'cetak', 'qc', 'siap_diambil', 'selesai', 'batal',
            ])->default('baru');

            $table->foreignId('desain_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('desain_at')->nullable();
            $table->foreignId('cetak_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cetak_at')->nullable();
            $table->foreignId('qc_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('qc_at')->nullable();
            $table->timestamp('diambil_at')->nullable();

            $table->timestamps();

            $table->index('KdCust');
        });

        Schema::create('order_artwork_detail', function (Blueprint $table) {
            $table->id();
            $table->string('BrsOrder', 25)->unique();
            $table->foreignId('order_artwork_id')->constrained('order_artwork')->cascadeOnDelete();
            $table->string('KdProd', 4);
            $table->string('NmProd', 30);
            $table->string('Judul', 30);
            $table->double('Panjang')->default(0);
            $table->double('Lebar')->default(0);
            $table->integer('Qty');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_artwork_detail');
        Schema::dropIfExists('order_artwork');
    }
};
