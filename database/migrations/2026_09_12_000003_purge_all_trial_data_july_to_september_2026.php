<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const START = '2026-07-01';
    private const END = '2026-10-01';

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (['indoor', 'outdoor', 'artwork'] as $type) {
                $this->purgeOrders($type);
            }

            $this->purgePurchases();
            $this->purgeDatedTransactions();
            $this->rebuildCustomerCreditUsage();
        });
    }

    private function purgeOrders(string $type): void
    {
        $orderTable = 'order_'.$type;
        $detailTable = $orderTable.'_detail';
        $foreignKey = $orderTable.'_id';

        $ids = DB::table($orderTable)
            ->where('TglOrder', '>=', self::START)->where('TglOrder', '<', self::END)
            ->pluck('id');

        foreach (['created_at', 'dibayar_at', 'desain_at', 'cetak_at', 'finishing_at',
            'qc_at', 'bungkus_at', 'diambil_at'] as $column) {
            $ids = $ids->merge(DB::table($orderTable)
                ->where($column, '>=', self::START)->where($column, '<', self::END)
                ->pluck('id'));
        }

        $ids = $ids->merge(DB::table($detailTable)
            ->where('stage_entered_at', '>=', self::START)->where('stage_entered_at', '<', self::END)
            ->pluck($foreignKey));

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

        foreach ($ids->filter()->unique()->values()->chunk(500) as $chunk) {
            foreach (['order_payments', 'order_documents', 'order_pickup_signatures',
                'order_comments', 'order_comment_reads', 'order_status_notes',
                'order_rework_requests', 'laporan_ppn_final_items'] as $related) {
                if (Schema::hasTable($related)) {
                    DB::table($related)->where('order_type', $type)->whereIn('order_id', $chunk)->delete();
                }
            }

            if ($type === 'outdoor' && Schema::hasTable('order_outdoor_desain_units')) {
                $detailIds = DB::table($detailTable)->whereIn($foreignKey, $chunk)->pluck('id');
                foreach ($detailIds->chunk(500) as $detailChunk) {
                    DB::table('order_outdoor_desain_units')
                        ->whereIn('order_outdoor_detail_id', $detailChunk)->delete();
                }
            }

            DB::table($detailTable)->whereIn($foreignKey, $chunk)->delete();
            DB::table($orderTable)->whereIn('id', $chunk)->delete();
        }
    }

    private function purgePurchases(): void
    {
        if (! Schema::hasTable('accounting_purchases')) {
            return;
        }

        $ids = DB::table('accounting_purchases')
            ->where('tanggal', '>=', self::START)->where('tanggal', '<', self::END)
            ->pluck('id');

        foreach (['accounting_purchase_payments', 'accounting_purchase_returns'] as $related) {
            if (Schema::hasTable($related)) {
                $ids = $ids->merge(DB::table($related)
                    ->where('tanggal', '>=', self::START)->where('tanggal', '<', self::END)
                    ->pluck('purchase_id'));
            }
        }

        foreach ($ids->filter()->unique()->values()->chunk(500) as $chunk) {
            foreach (['accounting_purchase_returns', 'accounting_purchase_payments',
                'accounting_purchase_lines'] as $related) {
                if (Schema::hasTable($related)) {
                    DB::table($related)->whereIn('purchase_id', $chunk)->delete();
                }
            }
            DB::table('accounting_purchases')->whereIn('id', $chunk)->delete();
        }
    }

    private function purgeDatedTransactions(): void
    {
        foreach ([
            ['pengeluaran', 'tanggal'],
            ['jurnal_manual', 'tanggal'],
            ['accounting_inventory_counts', 'tanggal'],
            ['accounting_fixed_assets', 'tanggal_perolehan'],
        ] as [$table, $column]) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where($column, '>=', self::START)
                    ->where($column, '<', self::END)->delete();
            }
        }

        foreach (['2026-07', '2026-08', '2026-09'] as $period) {
            foreach (['slip_gaji', 'periode_tutup_buku', 'accounting_opening_balances'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where('periode', $period)->delete();
                }
            }
            if (Schema::hasTable('laporan_ppn_final')) {
                DB::table('laporan_ppn_final')->where('periode', $period)->delete();
            }
        }

        if (Schema::hasTable('am')) {
            DB::table('am')->where('TgTrans', '>=', self::START)
                ->where('TgTrans', '<', self::END)->delete();
        }
    }

    private function rebuildCustomerCreditUsage(): void
    {
        if (! Schema::hasTable('customer_limits')) {
            return;
        }

        foreach (DB::table('customer_limits')->pluck('KdCust') as $customerCode) {
            $used = 0;
            foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
                $used += (float) DB::table($table)->where('KdCust', $customerCode)
                    ->where('status_bayar', 'hutang')->sum('jumlah_piutang');
            }
            DB::table('customer_limits')->where('KdCust', $customerCode)->update(['Total' => $used]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Data percobaan yang dihapus hanya dapat dipulihkan dari backup database.');
    }
};
