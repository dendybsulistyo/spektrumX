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
        // Hanya jurnal order: keterangan persis "<awalan><No. Order>" dengan Bukti = No. Order.
        DB::table('am')->where('KetMT', 'like', $from.'%')->whereRaw('KetMT = CONCAT(?, Bukti)', [$from])->update([
            'KetMT' => DB::raw('CONCAT('.DB::getPdo()->quote($to).', SUBSTRING(KetMT, '.(mb_strlen($from) + 1).'))'),
        ]);
    }
};
