<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Transfer dari customer yang masuk ke CS tapi belum jelas untuk order mana.
     */
    public function up(): void
    {
        Schema::create('cs_transfer_masuk', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 20);
            $table->string('customer_name');
            $table->decimal('amount', 15, 2);
            $table->date('transfer_date');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable()->index();
            $table->string('used_order_number', 40)->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_transfer_masuk');
    }
};
