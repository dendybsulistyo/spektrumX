<?php

namespace App\Services;

use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Support\Facades\DB;

/**
 * Invoice Hutang/Plafon yang sudah dicetak dianggap sudah diserahkan ke customer,
 * jadi transaksinya otomatis tersimpan (hilang dari Transaksi Penjualan di Pengambilan)
 * supaya nota tagihan tidak dicetak berulang. Keputusan user 9 Nov 2026.
 */
class PlafonInvoiceArchiver
{
    /**
     * Dipanggil di dalam transaksi pencatatan cetak pertama invoice.
     */
    public function archiveOnFirstPrint(OrderDocument $document): bool
    {
        if ($document->kind !== 'inv') {
            return false;
        }

        $model = $document->order_type === 'indoor' ? OrderIndoor::class : OrderOutdoor::class;
        $order = $model::query()->lockForUpdate()->find($document->order_id);
        if (! $order || $order->status_bayar !== 'hutang' || $order->status === 'batal' || $order->invoice_voided_at) {
            return false;
        }

        $alreadyArchived = DB::table('sales_transaction_archives')
            ->where('order_type', $document->order_type)->where('order_id', $document->order_id)->exists();
        if ($alreadyArchived) {
            return false;
        }

        // Tanggal invoice = saat transaksi disimpan, sama dengan tombol Simpan di Pengambilan.
        $document->forceFill(['issued_at' => now()])->save();
        DB::table('sales_transaction_archives')->insert([
            'order_type' => $document->order_type,
            'order_id' => $document->order_id,
            'archived_by' => auth()->id(),
            'archived_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}
