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
        Schema::create('order_rework_requests', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->string('current_stage', 20);
            $table->enum('action', ['ulang', 'batal']);
            $table->string('target_stage', 20)->nullable();
            $table->string('reason', 255);
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('requested_at');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->index(['order_type', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_rework_requests');
    }
};
