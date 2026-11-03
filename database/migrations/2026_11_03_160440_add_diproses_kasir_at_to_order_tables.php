<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Waktu order pertama kali diproses kasir (keputusan user 3 Nov 2026: jam
 * estimasi/telat berjalan sejak dibayar). Berbeda dengan dibayar_at yang
 * ditimpa saat pelunasan, kolom ini diisi sekali dan tidak berubah.
 * Order lama diisi dari catatan status kasir paling awal, lalu dibayar_at.
 */
return new class extends Migration
{
    private const TABLES = ['indoor' => 'order_indoor', 'outdoor' => 'order_outdoor', 'artwork' => 'order_artwork'];

    public function up(): void
    {
        foreach (self::TABLES as $type => $table) {
            if (! Schema::hasColumn($table, 'diproses_kasir_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->timestamp('diproses_kasir_at')->nullable()->index();
                });
            }

            if (Schema::hasTable('order_status_notes')) {
                $firstNotes = DB::table('order_status_notes')->where('order_type', $type)->where('stage', 'kasir')
                    ->groupBy('order_id')->selectRaw('order_id, MIN(created_at) AS first_at');
                DB::table($table)->joinSub($firstNotes, 'first_note', 'first_note.order_id', '=', $table.'.id')
                    ->whereNull($table.'.diproses_kasir_at')
                    ->update([$table.'.diproses_kasir_at' => DB::raw('first_note.first_at')]);
            }

            DB::table($table)->whereNull('diproses_kasir_at')->whereNotNull('dibayar_at')
                ->where('status_bayar', '!=', 'belum_bayar')
                ->update(['diproses_kasir_at' => DB::raw('dibayar_at')]);
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasColumn($table, 'diproses_kasir_at')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropIndex(['diproses_kasir_at']);
                    $blueprint->dropColumn('diproses_kasir_at');
                });
            }
        }
    }
};
