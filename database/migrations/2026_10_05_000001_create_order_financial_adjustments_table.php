<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_financial_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->string('order_number', 30);
            $table->string('customer_code', 20)->nullable();
            $table->date('transaction_date');
            $table->enum('adjustment_type', ['dp_tambah', 'dp_refund']);
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_before', 12, 2);
            $table->decimal('paid_after', 12, 2);
            $table->decimal('receivable_before', 12, 2);
            $table->decimal('receivable_after', 12, 2);
            $table->enum('payment_method', ['tunai', 'qris', 'transfer']);
            $table->string('reference_number', 50)->nullable();
            $table->string('reason', 255);
            $table->foreignId('user_id')->constrained('users');
            $table->string('journal_transaction_number', 14)->nullable();
            $table->timestamps();

            $table->index(['order_type', 'order_id']);
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_financial_adjustments');
    }
};
