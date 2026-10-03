<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_prints', function (Blueprint $table): void {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->unsignedInteger('print_count')->default(1);
            $table->foreignId('first_printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('first_printed_at');
            $table->foreignId('last_printed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('last_printed_at');
            $table->timestamps();

            $table->unique(['order_type', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_prints');
    }
};
