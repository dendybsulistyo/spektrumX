<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_daily_entries', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_key')->unique();
            $table->string('no_nota', 80)->nullable();
            $table->string('keterangan');
            $table->decimal('debet', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['tanggal', 'user_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_daily_entries');
    }
};
