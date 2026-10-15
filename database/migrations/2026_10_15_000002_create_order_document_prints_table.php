<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan cetak DO / Invoice (per dokumen), pasangan sales_order_prints untuk SO.
 * Cetak ulang hanya untuk role di App\Support\CetakUlang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_document_prints', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_document_id')->unique()->constrained('order_documents')->cascadeOnDelete();
            $table->unsignedInteger('print_count')->default(1);
            $table->foreignId('first_printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('first_printed_at');
            $table->foreignId('last_printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('last_printed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_document_prints');
    }
};
