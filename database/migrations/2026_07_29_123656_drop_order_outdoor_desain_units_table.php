<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-unit desain tracking for Order Outdoor turned out not to be
     * wanted — Antrian Desain went back to one row per order, so this table
     * has no more readers or writers.
     */
    public function up(): void
    {
        Schema::dropIfExists('order_outdoor_desain_units');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('order_outdoor_desain_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_outdoor_detail_id')->constrained('order_outdoor_detail')->cascadeOnDelete();
            $table->unsignedInteger('unit_index');
            $table->foreignId('desain_by')->constrained('users');
            $table->timestamp('desain_at');
            $table->timestamps();

            $table->unique(['order_outdoor_detail_id', 'unit_index'], 'cetak_units_detail_unit_unique');
        });
    }
};
