<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_pickup_signatures', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 20);
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('order_detail_id');
            $table->unsignedInteger('qty');
            $table->string('nama_penerima', 100);
            $table->string('kontak_penerima', 50);
            $table->string('signature_path');
            $table->char('signature_hash', 64);
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamps();
            $table->index(['order_type', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_pickup_signatures');
    }
};
