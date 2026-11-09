<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Sapu sekali: invoice Hutang/Plafon yang sudah dicetak tetapi belum disimpan
 * dipindahkan dari Transaksi Penjualan, sama seperti cetak invoice mulai sekarang
 * (App\Services\PlafonInvoiceArchiver). Tanggal invoice & simpan = saat cetak pertama.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['indoor' => 'order_indoor', 'outdoor' => 'order_outdoor'] as $type => $table) {
            $rows = DB::table('order_documents as d')
                ->join('order_document_prints as p', 'p.order_document_id', '=', 'd.id')
                ->join("{$table} as o", 'o.id', '=', 'd.order_id')
                ->where('d.kind', 'inv')->where('d.order_type', $type)
                ->where('o.status_bayar', 'hutang')
                ->where(fn ($q) => $q->whereNull('o.status')->orWhere('o.status', '!=', 'batal'))
                ->whereNull('o.invoice_voided_at')
                ->whereNotExists(fn ($q) => $q->from('sales_transaction_archives as a')
                    ->whereColumn('a.order_id', 'd.order_id')->where('a.order_type', $type))
                ->get(['d.id as document_id', 'd.order_id', 'p.first_printed_at', 'p.first_printed_by']);

            foreach ($rows as $row) {
                $printedAt = $row->first_printed_at ?? now();
                DB::table('order_documents')->where('id', $row->document_id)->update(['issued_at' => $printedAt, 'updated_at' => now()]);
                DB::table('sales_transaction_archives')->insert([
                    'order_type' => $type,
                    'order_id' => $row->order_id,
                    'archived_by' => $row->first_printed_by,
                    'archived_at' => $printedAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
