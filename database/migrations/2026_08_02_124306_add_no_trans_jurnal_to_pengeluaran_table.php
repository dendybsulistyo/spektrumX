<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Links a pengeluaran row to the journal transaction it posted —
        // needed so update()/destroy() can reverse the right `am` rows
        // instead of leaving stale entries in the ledger (journal entries
        // are append-only, never edited in place).
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->string('no_trans_jurnal', 14)->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn('no_trans_jurnal');
        });
    }
};
