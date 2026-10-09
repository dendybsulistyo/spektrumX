<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item "Print Only" pada 3 order 8 Okt 2026 dipindah ke Print Only 1801
 * (Dye Sublimation, master Artwork). Kode lama disimpan di tabel backup
 * agar bisa dikembalikan lewat rollback. Harga/total order tidak diubah.
 */
return new class extends Migration
{
    private const BACKUP = 'order_item_print_only_fix_20261009';

    private const ORDERS = ['IND.2.26100800056', 'IND.2.26100800054', 'IND.2.26100800051'];

    public function up(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            Schema::create(self::BACKUP, function (Blueprint $table) {
                $table->unsignedBigInteger('detail_id')->primary();
                $table->string('no_order');
                $table->string('old_kd_prod')->nullable();
                $table->string('old_jenis_produk')->nullable();
                $table->string('old_nm_prod')->nullable();
            });
        }

        $items = DB::table('order_indoor_detail as d')
            ->join('order_indoor as o', 'o.id', '=', 'd.order_indoor_id')
            ->whereIn('o.NoOrder', self::ORDERS)
            ->where('d.NmProd', 'like', 'Print Only%')
            ->where(fn ($query) => $query->where('d.KdProd', '!=', '1801')->orWhere('d.jenis_produk', '!=', 'artwork'))
            ->get(['d.id', 'o.NoOrder', 'd.KdProd', 'd.jenis_produk', 'd.NmProd']);

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                DB::table(self::BACKUP)->insertOrIgnore([
                    'detail_id' => $item->id,
                    'no_order' => $item->NoOrder,
                    'old_kd_prod' => $item->KdProd,
                    'old_jenis_produk' => $item->jenis_produk,
                    'old_nm_prod' => $item->NmProd,
                ]);
                DB::table('order_indoor_detail')->where('id', $item->id)->update([
                    'KdProd' => '1801',
                    'jenis_produk' => 'artwork',
                    'NmProd' => 'Print Only',
                ]);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::BACKUP)) {
            return;
        }

        foreach (DB::table(self::BACKUP)->get() as $row) {
            DB::table('order_indoor_detail')->where('id', $row->detail_id)->update([
                'KdProd' => $row->old_kd_prod,
                'jenis_produk' => $row->old_jenis_produk,
                'NmProd' => $row->old_nm_prod,
            ]);
        }

        Schema::drop(self::BACKUP);
    }
};
