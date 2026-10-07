<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_method_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_payment_id')->constrained('order_payments');
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->string('order_number', 30);
            $table->string('invoice_number', 80)->nullable();
            $table->date('correction_date');
            $table->decimal('amount', 12, 2);
            $table->enum('old_method', ['tunai', 'qris', 'transfer']);
            $table->enum('new_method', ['tunai', 'qris', 'transfer']);
            $table->string('old_reference', 50)->nullable();
            $table->string('new_reference', 50)->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->string('journal_transaction_number', 14)->nullable();
            $table->timestamps();

            $table->index(['order_type', 'order_id']);
            $table->index('correction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_corrections');
    }
};
