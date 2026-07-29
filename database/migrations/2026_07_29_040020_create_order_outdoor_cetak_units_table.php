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
        Schema::create('order_outdoor_cetak_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_outdoor_detail_id')->constrained('order_outdoor_detail')->cascadeOnDelete();
            $table->unsignedInteger('unit_index');
            $table->foreignId('cetak_by')->constrained('users');
            $table->timestamp('cetak_at');
            $table->timestamps();

            $table->unique(['order_outdoor_detail_id', 'unit_index'], 'cetak_units_detail_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_outdoor_cetak_units');
    }
};
