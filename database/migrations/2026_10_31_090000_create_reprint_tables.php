<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cetak ulang SO / DO / Invoice: log setiap cetak ulang (beserta alasannya)
 * dan permintaan approval bila lewat batas waktu cetak ulang bebas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reprint_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 3); // so | do | inv
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_document_id')->nullable();
            $table->string('reason', 255);
            $table->string('status', 10)->default('pending'); // pending | approved | rejected | used
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['kind', 'order_type', 'order_id', 'status']);
        });

        Schema::create('reprint_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('kind', 3);
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_document_id')->nullable();
            $table->string('document_number', 40);
            $table->unsignedInteger('print_no');
            $table->string('reason', 255);
            $table->string('mode', 12); // bebas | hak_khusus | approval
            $table->foreignId('reprint_request_id')->nullable()->constrained('reprint_requests');
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at')->nullable();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reprint_logs');
        Schema::dropIfExists('reprint_requests');
    }
};
