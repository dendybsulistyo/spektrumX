<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->enum('kelompok', ['bahan_baku', 'bahan_penolong', 'barang_jadi']);
            $table->string('satuan', 20)->default('pcs');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('accounting_inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('accounting_inventory_items')->restrictOnDelete();
            $table->date('tanggal')->index();
            $table->decimal('qty', 18, 3)->default(0);
            $table->decimal('harga_satuan', 18, 2)->default(0);
            $table->decimal('nilai', 18, 2)->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['inventory_item_id', 'tanggal'], 'inventory_count_per_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_inventory_counts');
        Schema::dropIfExists('accounting_inventory_items');
    }
};
