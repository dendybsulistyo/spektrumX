<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->decimal('diskon_akhir_nominal', 12, 2)->default(0)->after('diskon_nominal_tetap');
            });
        }

        Schema::create('final_sales_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('order_type', 10);
            $table->unsignedBigInteger('order_id');
            $table->string('order_number', 30);
            $table->string('customer_code', 20)->nullable();
            $table->date('transaction_date');
            $table->decimal('initial_amount', 12, 2);
            $table->decimal('discount_before', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2);
            $table->decimal('final_amount', 12, 2);
            $table->decimal('dpp_adjustment', 12, 2);
            $table->decimal('tax_adjustment', 12, 2)->default(0);
            $table->decimal('receivable_offset', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);
            $table->enum('refund_method', ['tunai', 'qris', 'transfer'])->nullable();
            $table->string('reference_number', 50)->nullable();
            $table->string('reason', 255);
            $table->foreignId('user_id')->constrained('users');
            $table->string('journal_transaction_number', 14)->nullable();
            $table->timestamps();

            $table->index(['order_type', 'order_id']);
            $table->index('transaction_date');
        });

        DB::table('am__')->updateOrInsert(
            ['NoAkun' => '41003'],
            ['NmAkun' => 'Potongan Penjualan', 'TipeDK' => 'D', 'TipeNL' => 'L']
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('final_sales_discounts');

        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('diskon_akhir_nominal');
            });
        }

        DB::table('am__')->where('NoAkun', '41003')->delete();
    }
};
