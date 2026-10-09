<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->string('background', 20);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });

        Schema::create('user_status_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_status_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->unique(['user_status_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_status_views');
        Schema::dropIfExists('user_statuses');
    }
};
