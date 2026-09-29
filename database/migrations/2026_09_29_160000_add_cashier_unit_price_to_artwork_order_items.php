<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_indoor_detail', function (Blueprint $table): void {
            $table->decimal('harga_satuan_kasir', 18, 2)->nullable()->after('Qty');
        });

        Schema::table('order_artwork_detail', function (Blueprint $table): void {
            $table->decimal('harga_satuan_kasir', 18, 2)->nullable()->after('Qty');
        });
    }

    public function down(): void
    {
        Schema::table('order_indoor_detail', function (Blueprint $table): void {
            $table->dropColumn('harga_satuan_kasir');
        });

        Schema::table('order_artwork_detail', function (Blueprint $table): void {
            $table->dropColumn('harga_satuan_kasir');
        });
    }
};
