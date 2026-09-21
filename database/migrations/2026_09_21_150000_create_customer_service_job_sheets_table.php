<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_service_job_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name', 150);
            $table->string('pc', 100)->nullable();
            $table->string('folder_file', 255)->nullable();
            $table->date('received_at');
            $table->date('deadline')->nullable();
            $table->string('opf', 100)->nullable();
            $table->text('notes')->nullable();
            $table->json('items');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['received_at', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_job_sheets');
    }
};
