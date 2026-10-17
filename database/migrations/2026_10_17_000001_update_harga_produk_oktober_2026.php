<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Update harga master sesuai file produk.sql terbaru dari spektrum (17 Okt 2026).
 *
 * Hanya baris yang berubah. Tiap baris diperbarui HANYA jika nilainya di server
 * masih sama dengan nilai lama, supaya perubahan yang dibuat lewat aplikasi
 * sesudah file ini dibandingkan tidak tertimpa. Baris yang dilewati dicetak.
 *
 * Format: KdProd => [[kolom => nilai lama], [kolom => nilai baru]]
 */
return new class extends Migration
{
    private const PERUBAHAN = [
        'produk_indoor' => [
            '0501' => [['HargaStd' => 32900], ['HargaStd' => 38300]],
            '0502' => [['HargaStd' => 49500], ['HargaStd' => 44750]],
            '0503' => [['HargaStd' => 39300], ['HargaStd' => 48550]],
            '0504' => [['HargaStd' => 56200], ['HargaStd' => 53350]],
            '0511' => [['HargaStd' => 68600], ['HargaStd' => 38900]],
            '0512' => [['HargaStd' => 50850], ['HargaStd' => 45100]],
            '0513' => [['HargaStd' => 39850], ['HargaStd' => 49600]],
            '0514' => [['HargaStd' => 57250], ['HargaStd' => 54400]],
            '0521' => [['HargaStd' => 34700], ['HargaStd' => 40000]],
            '0522' => [['HargaStd' => 41100], ['HargaStd' => 51950]],
            '0523' => [['HargaStd' => 35150], ['HargaStd' => 40100]],
            '0524' => [['HargaStd' => 41550], ['HargaStd' => 51950]],
            '0531' => [['HargaStd' => 27900], ['HargaStd' => 22000]],
            '0532' => [['HargaStd' => 44800], ['HargaStd' => 28100]],
            '0533' => [['HargaStd' => 34800], ['HargaStd' => 25100]],
            '0534' => [['HargaStd' => 51200], ['HargaStd' => 34200]],
            '0535' => [['HargaStd' => 63600], ['HargaStd' => 43200]],
            '0541' => [['HargaStd' => 28450], ['HargaStd' => 23800]],
            '0542' => [['HargaStd' => 45850], ['HargaStd' => 28600]],
            '0543' => [['HargaStd' => 34850], ['HargaStd' => 25600]],
            '0544' => [['HargaStd' => 52250], ['HargaStd' => 36000]],
            '0545' => [['HargaStd' => 64650], ['HargaStd' => 44200]],
            '0551' => [['HargaStd' => 29500], ['HargaStd' => 24800]],
            '0552' => [['HargaStd' => 48000], ['HargaStd' => 29600]],
            '0561' => [['HargaStd' => 29700], ['HargaStd' => 25000]],
            '0562' => [['HargaStd' => 36100], ['HargaStd' => 37000]],
            '0563' => [['HargaStd' => 30150], ['HargaStd' => 25000]],
            '0564' => [['HargaStd' => 36550], ['HargaStd' => 36950]],
            '0565' => [['HargaStd' => 28500], ['HargaStd' => 26100]],
            '0566' => [['HargaStd' => 34850], ['HargaStd' => 36700]],
        ],
        'harga_artwork' => [
            '1317' => [['NmProd' => 'Acrylic Bening 25 mm (cm)', 'NoUrut' => 1317, 'HargaStd' => 0, 'HargaMin' => 0], ['NmProd' => 'Acrylic Putih Susu 10 mm (cm)', 'NoUrut' => 1325, 'HargaStd' => 118, 'HargaMin' => 10000]],
            '1318' => [['NmProd' => 'Acrylic Bening 30 mm (cm)', 'NoUrut' => 1318, 'HargaStd' => 0, 'Satuan' => 'sqcm'], ['NmProd' => 'Acrylic Putih Susu 10 mm (lb)', 'NoUrut' => 1325, 'HargaStd' => 3540000, 'Satuan' => 'lbr']],
            '1319' => [['HargaStd' => 22.5], ['HargaStd' => 28]],
            '1320' => [['HargaStd' => 485000], ['HargaStd' => 671000]],
            '1321' => [['HargaStd' => 31], ['HargaStd' => 38]],
            '1322' => [['HargaStd' => 675000], ['HargaStd' => 924000]],
            '1323' => [['HargaStd' => 51], ['HargaStd' => 59]],
            '1324' => [['HargaStd' => 1100000], ['HargaStd' => 1510000]],
            '1325' => [['HargaStd' => 24], ['HargaStd' => 29]],
            '1326' => [['HargaStd' => 522000], ['HargaStd' => 675000]],
            '1327' => [['HargaStd' => 33], ['HargaStd' => 40]],
            '1328' => [['HargaStd' => 715000], ['HargaStd' => 962500]],
            '1329' => [['HargaStd' => 54], ['HargaStd' => 63]],
            '1330' => [['HargaStd' => 1175000], ['HargaStd' => 1576000]],
            '1801' => [['HargaStd' => 3], ['HargaStd' => 2.5]],
            '2701' => [['HargaStd' => 400, 'HargaMin' => 40000], ['HargaStd' => 250, 'HargaMin' => 25000]],
        ],    ];

    public function up(): void
    {
        $this->terapkan(0, 1);
    }

    public function down(): void
    {
        $this->terapkan(1, 0);
    }

    private function terapkan(int $dari, int $ke): void
    {
        foreach (self::PERUBAHAN as $tabel => $baris) {
            $diubah = 0;
            $stempel = Schema::hasColumn($tabel, 'updated_at') ? ['updated_at' => now()] : [];
            foreach ($baris as $kode => $nilai) {
                $query = DB::table($tabel)->where('KdProd', (string) $kode);
                foreach ($nilai[$dari] as $kolom => $isi) {
                    $query->where($kolom, $isi);
                }
                if ($query->update($nilai[$ke] + $stempel) === 1) {
                    $diubah++;
                } else {
                    echo "  [{$tabel}] {$kode} dilewati: nilai di database sudah berbeda dari yang diharapkan\n";
                }
            }
            echo "  [{$tabel}] {$diubah} dari ".count($baris)." baris diperbarui\n";
        }
    }
};
