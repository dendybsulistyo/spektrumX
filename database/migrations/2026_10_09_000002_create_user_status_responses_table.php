<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reaksi (emoji, satu per user per status) & balasan teks ke status.
        Schema::create('user_status_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_status_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('emoji', 16)->nullable();
            $table->string('body', 300)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_status_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status_responses');
    }
};
