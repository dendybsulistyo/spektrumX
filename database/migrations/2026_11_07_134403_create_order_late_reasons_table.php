<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Alasan order telat dari operator (7 Nov 2026): boleh lebih dari satu per order, tidak diedit/dihapus. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_late_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 20);
            $table->unsignedBigInteger('order_id');
            $table->text('alasan');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['order_type', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_late_reasons');
    }
};
