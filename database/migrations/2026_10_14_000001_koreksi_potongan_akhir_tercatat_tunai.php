<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Dua Potongan Penjualan Akhir (data percobaan) tercatat "refund tunai"
 * padahal nota dibayar via transfer dan tidak ada uang yang dikembalikan.
 * Akibatnya Kas Tunai 13 & 14 Okt 2026 berkurang tanpa uang fisik keluar.
 * Koreksi: potongan mengurangi penerimaan Bank (11101), bukan Kas Tunai
 * (11100) — mengikuti cara bayar penerimaan nota ("By system").
 *
 * Hanya baris yang masih persis dalam kondisi salah yang diubah, jadi aman
 * dijalankan ulang dan tidak menyentuh data lain.
 */
return new class extends Migration
{
    private const KOREKSI = [
        ['order_number' => 'IND.2.26101200033', 'jurnal' => '261013J0000004', 'nominal' => 24900],
        ['order_number' => 'IND.2.26101300004', 'jurnal' => '261014J0000008', 'nominal' => 5300],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (self::KOREKSI as $k) {
                $potongan = DB::table('final_sales_discounts')
                    ->where('order_number', $k['order_number'])
                    ->where('refund_method', 'tunai')
                    ->where('refund_amount', $k['nominal'])
                    ->where('journal_transaction_number', $k['jurnal'])
                    ->first();

                if (! $potongan) {
                    continue;
                }

                DB::table('final_sales_discounts')->where('id', $potongan->id)->update([
                    'refund_method' => 'transfer',
                    'reference_number' => null,
                    'updated_at' => now(),
                ]);

                DB::table('order_payments')
                    ->where('order_type', $potongan->order_type)
                    ->where('order_id', $potongan->order_id)
                    ->where('jenis', 'refund')
                    ->where('cara_bayar', 'tunai')
                    ->where('jumlah', -$k['nominal'])
                    ->update(['cara_bayar' => 'transfer', 'no_referensi' => null]);

                DB::table('am')
                    ->where('NoTrans', $k['jurnal'])
                    ->where('NoAkun', '11100')
                    ->where('Kredit', $k['nominal'])
                    ->update(['NoAkun' => '11101']);
            }
        });
    }

    public function down(): void
    {
        // Koreksi data: tidak dibalik otomatis.
    }
};
