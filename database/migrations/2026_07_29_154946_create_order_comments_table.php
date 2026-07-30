<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A running discussion thread per order, separate from
     * order_status_notes (which is a one-shot note attached to each stage
     * transition). Comments can be posted any time regardless of the
     * order's current status, so Desain and Cetak operators — and anyone
     * looking the order up later — see the same conversation.
     */
    public function up(): void
    {
        Schema::create('order_comments', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->foreignId('user_id')->constrained('users');
            $table->text('pesan');
            $table->timestamp('created_at');

            $table->index(['order_type', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_comments');
    }
};
