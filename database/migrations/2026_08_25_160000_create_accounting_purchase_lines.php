<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('accounting_purchases')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('accounting_inventory_items')->nullOnDelete();
            $table->string('deskripsi', 255);
            $table->enum('klasifikasi', ['bahan_baku', 'bahan_penolong', 'aset', 'biaya']);
            $table->string('akun', 6);
            $table->decimal('qty', 18, 3)->default(1);
            $table->string('satuan', 20)->default('pcs');
            $table->decimal('harga_satuan', 18, 2);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('accounting_purchase_lines'); }
};
