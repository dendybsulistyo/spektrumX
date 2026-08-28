<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('laporan_ppn_final', function (Blueprint $table) {
            $table->id();
            $table->string('periode', 7)->unique();
            $table->decimal('tarif_ppn', 5, 2);
            $table->string('status', 10)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('laporan_ppn_final_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_ppn_final_id')->constrained('laporan_ppn_final')->cascadeOnDelete();
            $table->string('order_type', 20);
            $table->unsignedBigInteger('order_id');
            $table->dateTime('tanggal_lunas');
            $table->string('no_order', 50);
            $table->string('customer', 100)->nullable();
            $table->decimal('total', 18, 2);
            $table->decimal('dpp', 18, 2);
            $table->decimal('ppn', 18, 2);
            $table->timestamps();

            $table->unique(['laporan_ppn_final_id', 'order_type', 'order_id'], 'ppn_final_item_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_ppn_final_items');
        Schema::dropIfExists('laporan_ppn_final');
    }
};
