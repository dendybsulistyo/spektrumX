<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Keterangan jurnal penjualan VIP memakai istilah kasir "hutang"; dari sisi
 * pembukuan spektrum yang benar "piutang". Hanya kolom keterangan (KetMT)
 * yang diubah — akun, nominal, dan tanggal tidak tersentuh.
 */
return new class extends Migration
{
    private const PAIRS = [
        'Penjualan hutang ' => 'Penjualan piutang ',
        'Pelunasan hutang ' => 'Pelunasan piutang ',
        'Cicilan hutang ' => 'Cicilan piutang ',
    ];

    public function up(): void
    {
        foreach (self::PAIRS as $old => $new) {
            $this->rename($old, $new);
        }
    }

    public function down(): void
    {
        foreach (self::PAIRS as $old => $new) {
            $this->rename($new, $old);
        }
    }

    private function rename(string $from, string $to): void
    {
        // Dibandingkan di PHP, bukan CONCAT di SQL: kolom am berkolasi latin1 di server
        // sehingga CONCAT dengan teks utf8mb4 ditolak MySQL ("Illegal mix of collations").
        // Hanya jurnal order: keterangan persis "<awalan><No. Order>" dengan Bukti = No. Order.
        DB::table('am')->where('KetMT', 'like', $from.'%')
            ->select('KetMT', 'Bukti')->distinct()->get()
            ->filter(fn ($row) => $row->KetMT === $from.$row->Bukti)
            ->each(fn ($row) => DB::table('am')
                ->where('KetMT', $row->KetMT)->where('Bukti', $row->Bukti)
                ->update(['KetMT' => $to.$row->Bukti]));
    }
};
