<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan order yang pernah telat (> 24 jam sejak dibuat belum Siap Diambil).
 * Tabel baru saja; tabel order tidak diubah. Satu baris per order.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_late_logs')) {
            return;
        }

        Schema::create('order_late_logs', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->timestamp('order_created_at')->nullable();
            $table->timestamp('late_at')->index();
            $table->string('stage', 20)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_status', 20)->nullable();
            $table->boolean('is_estimated')->default(false);
            $table->timestamps();

            $table->unique(['order_type', 'order_id']);
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_late_logs');
    }
};
