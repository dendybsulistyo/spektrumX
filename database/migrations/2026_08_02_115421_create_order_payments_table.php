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
        // A log of every moment cash actually changed hands — the full
        // "lunas" payment, a DP, or a DP settlement (pelunasan). This is the
        // foundation for daily cash reconciliation and later finance
        // reporting; order_indoor/outdoor/artwork only carry the order's
        // *current* payment state, not a history of each cash-in event.
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->enum('jenis', ['lunas', 'dp', 'pelunasan_dp', 'nota_pengganti']);
            $table->decimal('jumlah', 12, 2);
            $table->enum('cara_bayar', ['tunai', 'qris', 'transfer']);
            $table->string('no_referensi', 50)->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at');

            $table->index(['order_type', 'order_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};
