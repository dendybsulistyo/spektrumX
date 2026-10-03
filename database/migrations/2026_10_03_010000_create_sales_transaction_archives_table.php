<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_transaction_archives', function (Blueprint $table): void {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('archived_at');
            $table->timestamps();
            $table->unique(['order_type', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_transaction_archives');
    }
};
