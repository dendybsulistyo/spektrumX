<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_documents', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10);
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('sequence')->default(1);
            $table->string('number', 80)->unique();
            $table->uuid('request_key')->nullable()->unique();
            $table->timestamp('issued_at');
            $table->unsignedBigInteger('issued_by')->nullable();
            $table->decimal('total', 18, 2)->default(0);
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['kind', 'order_type', 'order_id', 'sequence'], 'order_document_sequence_unique');
            $table->index(['kind', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_documents');
    }
};
