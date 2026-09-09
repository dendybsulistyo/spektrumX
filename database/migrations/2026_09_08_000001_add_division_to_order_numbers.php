<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convert(false);
    }

    public function down(): void
    {
        $this->convert(true);
    }

    private function convert(bool $reverse): void
    {
        DB::transaction(function () use ($reverse) {
            foreach (['outdoor' => ['OUT', 'OUT.1.'], 'indoor' => ['IND', 'IND.2.']] as $kind => [$old, $new]) {
                [$from, $to] = $reverse ? [$new, $old] : [$old, $new];
                $table = 'order_'.$kind;

                DB::table($table)->where('NoOrder', 'like', $from.'%')
                    ->orderBy('id')->chunkById(500, function ($orders) use ($table, $from, $to) {
                        foreach ($orders as $order) {
                            if (! preg_match('/^'.preg_quote($from, '/').'[0-9]{11}$/D', $order->NoOrder)) {
                                continue;
                            }

                            $number = $to.substr($order->NoOrder, strlen($from));
                            if (DB::table($table)->where('NoOrder', $number)->exists()) {
                                throw new RuntimeException("Nomor nota sudah ada: {$number}");
                            }

                            $details = DB::table($table.'_detail')
                                ->where('BrsOrder', 'like', $order->NoOrder.'%')->pluck('BrsOrder');
                            foreach ($details as $detail) {
                                DB::table($table.'_detail')->where('BrsOrder', $detail)
                                    ->update(['BrsOrder' => $number.substr($detail, strlen($order->NoOrder))]);
                            }

                            if (Schema::hasTable('laporan_ppn_final_items')) {
                                DB::table('laporan_ppn_final_items')->where('no_order', $order->NoOrder)
                                    ->update(['no_order' => $number]);
                            }

                            DB::table($table)->where('id', $order->id)->update(['NoOrder' => $number]);
                        }
                    });
            }
        });
    }
};
