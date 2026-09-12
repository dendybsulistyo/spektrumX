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
                $table = 'order_'.$type;
                $detail = $table.'_detail';
                $foreignKey = $table.'_id';

                $ids = DB::table($detail)
                    ->where('stage_entered_at', '>=', self::START)
                    ->where('stage_entered_at', '<', self::END)
                    ->pluck($foreignKey);

                $headerDates = ['created_at', 'dibayar_at', 'desain_at', 'cetak_at',
                    'finishing_at', 'qc_at', 'bungkus_at', 'diambil_at'];
                $datedOrders = DB::table($table)->where(function ($query) use ($headerDates) {
                    foreach ($headerDates as $column) {
                        $query->orWhere(function ($q) use ($column) {
                            $q->where($column, '>=', self::START)->where($column, '<', self::END);
                        });
                    }
                })->pluck('id');

                foreach ($ids->merge($datedOrders)->filter()->unique()->chunk(500) as $chunk) {
                    foreach (['order_payments', 'order_documents', 'order_pickup_signatures',
                        'order_comments', 'order_comment_reads', 'order_status_notes',
                        'order_rework_requests', 'laporan_ppn_final_items'] as $related) {
                        if (Schema::hasTable($related)) {
                            DB::table($related)->where('order_type', $type)->whereIn('order_id', $chunk)->delete();
                        }
                    }

                    if ($type === 'outdoor' && Schema::hasTable('order_outdoor_desain_units')) {
                        $detailIds = DB::table($detail)->whereIn($foreignKey, $chunk)->pluck('id');
                        foreach ($detailIds->chunk(500) as $detailChunk) {
                            DB::table('order_outdoor_desain_units')
                                ->whereIn('order_outdoor_detail_id', $detailChunk)->delete();
                        }
                    }
                    DB::table($detail)->whereIn($foreignKey, $chunk)->delete();
                    DB::table($table)->whereIn('id', $chunk)->delete();
                }
            }

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
        throw new RuntimeException('Order yang dibersihkan tidak dapat dipulihkan tanpa backup database.');
    }
};
