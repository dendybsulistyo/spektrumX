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
            // September test payments may have settled genuine older orders.
            // Restore those orders from the payment/journal history that remains.
            foreach (['indoor', 'outdoor', 'artwork'] as $type) {
                $orderTable = 'order_'.$type;
                $olderOrderIds = DB::table('order_payments as p')
                    ->join($orderTable.' as o', 'o.id', '=', 'p.order_id')
                    ->where('p.order_type', $type)
                    ->where('p.created_at', '>=', self::START)
                    ->where('p.created_at', '<', self::END)
                    ->where('o.TglOrder', '<', self::START)
                    ->distinct()->pluck('o.id');

                foreach ($olderOrderIds as $id) {
                    $order = DB::table($orderTable)->where('id', $id)->lockForUpdate()->first();
                    $priorPayments = DB::table('order_payments')
                        ->where('order_type', $type)->where('order_id', $id)
                        ->where('created_at', '<', self::START)
                        ->orderBy('created_at')->orderBy('id')->get();
                    $paid = max(0, (float) $priorPayments->sum('jumlah'));
                    $total = (float) $order->total;
                    $hadCreditSale = DB::table('am')->where('Bukti', $order->NoOrder)
                        ->where('NoAkun', '11102')->where('Debet', '>', 0)
                        ->where('TgTrans', '<', self::START)->exists();
                    $lastPayment = $priorPayments->last();
                    $lastMethod = $priorPayments->where('jenis', '!=', 'refund')->pluck('cara_bayar')->unique()->values();

                    if ($paid >= $total && $total > 0) {
                        $status = 'lunas';
                        $method = 'tunai';
                    } elseif ($hadCreditSale) {
                        $status = 'hutang';
                        $method = 'hutang';
                    } elseif ($paid > 0) {
                        $status = 'dp';
                        $method = 'dp';
                    } else {
                        $status = 'belum_bayar';
                        $method = null;
                    }

                    $oldCredit = $order->status_bayar === 'hutang' ? (float) $order->jumlah_piutang : 0;
                    $newCredit = $status === 'hutang' ? max(0, $total - $paid) : 0;
                    $creditDelta = $newCredit - $oldCredit;
                    if ($creditDelta != 0 && Schema::hasTable('customer_limits')) {
                        DB::table('customer_limits')->where('KdCust', $order->KdCust)->increment('Total', $creditDelta);
                    }

                    DB::table('order_payments')->where('order_type', $type)->where('order_id', $id)
                        ->where('created_at', '>=', self::START)->where('created_at', '<', self::END)->delete();
                    DB::table('order_documents')->where('order_type', $type)->where('order_id', $id)
                        ->where('issued_at', '>=', self::START)->where('issued_at', '<', self::END)->delete();
                    DB::table('order_status_notes')->where('order_type', $type)->where('order_id', $id)
                        ->where('created_at', '>=', self::START)->where('created_at', '<', self::END)->delete();
                    DB::table($orderTable)->where('id', $id)->update([
                        'status_bayar' => $status,
                        'metode_bayar' => $method,
                        'jumlah_dibayar' => $paid,
                        'jumlah_piutang' => $status === 'belum_bayar' ? 0 : max(0, $total - $paid),
                        'cara_bayar' => $lastMethod->count() > 1 ? 'campuran' : $lastMethod->first(),
                        'no_referensi' => $lastPayment?->no_referensi,
                        'dibayar_at' => $lastPayment?->created_at ?? ($status === 'hutang' ? DB::table('am')
                            ->where('Bukti', $order->NoOrder)->where('NoAkun', '11102')
                            ->where('Debet', '>', 0)->where('TgTrans', '<', self::START)
                            ->min('TgTrans') : null),
                    ]);
                }
            }

            foreach (['indoor', 'outdoor', 'artwork'] as $type) {
                $table = 'order_'.$type;
                $ids = DB::table($table)->where('TglOrder', '>=', self::START)
                    ->where('TglOrder', '<', self::END)->pluck('id');

                foreach ($ids->chunk(500) as $chunk) {
                    foreach (['order_payments', 'order_documents', 'order_pickup_signatures',
                        'order_comments', 'order_comment_reads', 'order_status_notes',
                        'order_rework_requests', 'laporan_ppn_final_items'] as $related) {
                        if (Schema::hasTable($related)) {
                            DB::table($related)->where('order_type', $type)->whereIn('order_id', $chunk)->delete();
                        }
                    }

                    if ($type === 'indoor') {
                        DB::table('order_indoor_detail')->whereIn('order_indoor_id', $chunk)->delete();
                    } else {
                        $detailTable = 'order_'.$type.'_detail';
                        if ($type === 'outdoor' && Schema::hasTable('order_outdoor_desain_units')) {
                            $detailIds = DB::table($detailTable)->whereIn('order_outdoor_id', $chunk)->pluck('id');
                            foreach ($detailIds->chunk(500) as $detailChunk) {
                                DB::table('order_outdoor_desain_units')->whereIn('order_outdoor_detail_id', $detailChunk)->delete();
                            }
                        }
                        DB::table($detailTable)->whereIn('order_'.$type.'_id', $chunk)->delete();
                    }

                    DB::table($table)->whereIn('id', $chunk)->delete();
                }
            }

            if (Schema::hasTable('laporan_ppn_final')) {
                DB::table('laporan_ppn_final')->where('periode', '2026-09')->delete();
            }

            if (Schema::hasTable('accounting_purchases')) {
                foreach (['accounting_purchase_payments', 'accounting_purchase_returns'] as $related) {
                    $hasOlderPurchase = DB::table($related.' as r')
                        ->join('accounting_purchases as p', 'p.id', '=', 'r.purchase_id')
                        ->where('r.tanggal', '>=', self::START)
                        ->where('r.tanggal', '<', self::END)
                        ->where('p.tanggal', '<', self::START)->exists();
                    if ($hasOlderPurchase) {
                        throw new RuntimeException("Ada {$related} September untuk pembelian sebelum September; migrasi dibatalkan.");
                    }
                }
                $purchaseIds = DB::table('accounting_purchases')->where('tanggal', '>=', self::START)
                    ->where('tanggal', '<', self::END)->pluck('id');
                foreach ($purchaseIds->chunk(500) as $chunk) {
                    DB::table('accounting_purchase_returns')->whereIn('purchase_id', $chunk)->delete();
                    DB::table('accounting_purchase_payments')->whereIn('purchase_id', $chunk)->delete();
                    DB::table('accounting_purchase_lines')->whereIn('purchase_id', $chunk)->delete();
                    DB::table('accounting_purchases')->whereIn('id', $chunk)->delete();
                }
                DB::table('accounting_purchase_returns')->where('tanggal', '>=', self::START)
                    ->where('tanggal', '<', self::END)->delete();
                DB::table('accounting_purchase_payments')->where('tanggal', '>=', self::START)
                    ->where('tanggal', '<', self::END)->delete();
            }

            foreach (['pengeluaran', 'jurnal_manual'] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where('tanggal', '>=', self::START)
                        ->where('tanggal', '<', self::END)->delete();
                }
            }
            if (Schema::hasTable('slip_gaji')) {
                DB::table('slip_gaji')->where('periode', '2026-09')->delete();
            }
            if (Schema::hasTable('accounting_inventory_counts')) {
                DB::table('accounting_inventory_counts')->where('tanggal', '>=', self::START)
                    ->where('tanggal', '<', self::END)->delete();
            }
            if (Schema::hasTable('accounting_fixed_assets')) {
                DB::table('accounting_fixed_assets')->where('tanggal_perolehan', '>=', self::START)
                    ->where('tanggal_perolehan', '<', self::END)->delete();
            }

            // The legacy ledger stores one debit/credit row per account.
            // Delete the whole September period, never just one side of an entry.
            DB::table('am')->where('TgTrans', '>=', self::START)
                ->where('TgTrans', '<', self::END)->delete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Transaksi yang dihapus tidak dapat dipulihkan lewat rollback; pulihkan dari backup database sebelum deploy.');
    }
};
