<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Arsip dokumen mencari dokumen per order (order_type + order_id); index unik
    // yang ada diawali kolom kind sehingga tidak terpakai untuk pencarian ini.
    public function up(): void
    {
        Schema::table('order_documents', function (Blueprint $table) {
            $table->index(['order_type', 'order_id', 'kind'], 'order_documents_order_lookup_index');
        });
    }

    public function down(): void
    {
        Schema::table('order_documents', function (Blueprint $table) {
            $table->dropIndex('order_documents_order_lookup_index');
        });
    }
};
