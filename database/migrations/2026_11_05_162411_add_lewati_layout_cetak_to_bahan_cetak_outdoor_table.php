<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Bahan outdoor (mis. laminasi) yang langsung masuk Finishing, seperti stand banner di Indoor. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bahan_cetak_outdoor', function (Blueprint $table) {
            $table->boolean('lewati_layout_cetak')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('bahan_cetak_outdoor', function (Blueprint $table) {
            $table->dropColumn('lewati_layout_cetak');
        });
    }
};
