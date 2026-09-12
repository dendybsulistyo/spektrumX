<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const START = '2026-09-01';
    private const END = '2026-10-01';

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['indoor', 'outdoor', 'artwork'] as $type) {
                $orderTable = 'order_'.$type;
                $ids = DB::table($orderTable)->whereBetween('TglOrder', [self::START, '2026-09-30'])
                    ->pluck('id');

                foreach ([
                    ['order_payments', 'created_at'],
                    ['order_documents', 'issued_at'],
                    ['order_pickup_signatures', 'received_at'],
                    ['order_status_notes', 'created_at'],
                    ['order_comments', 'created_at'],
                ] as [$related, $dateColumn]) {
                    if (Schema::hasTable($related)) {
                        $ids = $ids->merge(DB::table($related)->where('order_type', $type)
                            ->where($dateColumn, '>=', self::START)->where($dateColumn, '<', self::END)
                            ->pluck('order_id'));
                    }
                }

                $ids = $ids->unique()->values();
                foreach ($ids->chunk(500) as $chunk) {
                    foreach (['order_payments', 'order_documents', 'order_pickup_signatures',
                        'order_comments', 'order_comment_reads', 'order_status_notes',
                        'order_rework_requests', 'laporan_ppn_final_items'] as $related) {
                        if (Schema::hasTable($related)) {
                            DB::table($related)->where('order_type', $type)->whereIn('order_id', $chunk)->delete();
                        }
                    }

                    $detailTable = 'order_'.$type.'_detail';
                    $foreignKey = 'order_'.$type.'_id';
                    if ($type === 'outdoor' && Schema::hasTable('order_outdoor_desain_units')) {
                        $detailIds = DB::table($detailTable)->whereIn($foreignKey, $chunk)->pluck('id');
                        foreach ($detailIds->chunk(500) as $detailChunk) {
                            DB::table('order_outdoor_desain_units')->whereIn('order_outdoor_detail_id', $detailChunk)->delete();
                        }
                    }
                    DB::table($detailTable)->whereIn($foreignKey, $chunk)->delete();
                    DB::table($orderTable)->whereIn('id', $chunk)->delete();
                }
            }

            if (Schema::hasTable('laporan_ppn_final')) {
                DB::table('laporan_ppn_final')->where('periode', '2026-09')->delete();
            }

            if (Schema::hasTable('accounting_purchases')) {
                $purchaseIds = DB::table('accounting_purchases')
                    ->where('tanggal', '>=', self::START)->where('tanggal', '<', self::END)->pluck('id');
                foreach (['accounting_purchase_payments', 'accounting_purchase_returns'] as $related) {
                    if (Schema::hasTable($related)) {
                        $purchaseIds = $purchaseIds->merge(DB::table($related)
                            ->where('tanggal', '>=', self::START)->where('tanggal', '<', self::END)
                            ->pluck('purchase_id'));
                    }
                }
                foreach ($purchaseIds->unique()->chunk(500) as $chunk) {
                    foreach (['accounting_purchase_returns', 'accounting_purchase_payments', 'accounting_purchase_lines'] as $related) {
                        if (Schema::hasTable($related)) {
                            DB::table($related)->whereIn('purchase_id', $chunk)->delete();
                        }
                    }
                    DB::table('accounting_purchases')->whereIn('id', $chunk)->delete();
                }
            }

            foreach ([
                ['pengeluaran', 'tanggal'],
                ['jurnal_manual', 'tanggal'],
                ['accounting_inventory_counts', 'tanggal'],
                ['accounting_fixed_assets', 'tanggal_perolehan'],
            ] as [$table, $column]) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where($column, '>=', self::START)->where($column, '<', self::END)->delete();
                }
            }
            if (Schema::hasTable('slip_gaji')) {
                DB::table('slip_gaji')->where('periode', '2026-09')->delete();
            }
            if (Schema::hasTable('periode_tutup_buku')) {
                DB::table('periode_tutup_buku')->where('periode', '2026-09')->delete();
            }
            if (Schema::hasTable('accounting_opening_balances')) {
                DB::table('accounting_opening_balances')->where('periode', '2026-09')->delete();
            }
            if (Schema::hasTable('am')) {
                DB::table('am')->where('TgTrans', '>=', self::START)->where('TgTrans', '<', self::END)->delete();
            }

            // Rebuild used VIP credit from the orders that still exist.
            if (Schema::hasTable('customer_limits')) {
                foreach (DB::table('customer_limits')->pluck('KdCust') as $customerCode) {
                    $used = 0;
                    foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
                        $used += (float) DB::table($table)->where('KdCust', $customerCode)
                            ->where('status_bayar', 'hutang')->sum('jumlah_piutang');
                    }
                    DB::table('customer_limits')->where('KdCust', $customerCode)->update(['Total' => $used]);
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Pembersihan transaksi September 2026 hanya dapat dipulihkan dari backup database.');
    }
};
