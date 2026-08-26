<?php

namespace Database\Seeders;

use App\Models\AccountingFixedAsset;
use Illuminate\Database\Seeder;

class AccountingFixedAssetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $assets = [
            [
                'nama' => 'Gedung Toko & Workshop',
                'kelompok' => 'bangunan_semi',
                'tanggal_perolehan' => '2017-01-15',
                'harga_perolehan' => 150000000.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Gedung bangunan semi-permanen Bengkel',
            ],
            [
                'nama' => 'Komputer Desain (4 Unit)',
                'kelompok' => 'I',
                'tanggal_perolehan' => '2017-05-10',
                'harga_perolehan' => 11200000.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Komputer admin dan layout',
            ],
            [
                'nama' => 'PRINTER Epson L3110',
                'kelompok' => 'I',
                'tanggal_perolehan' => '2018-06-20',
                'harga_perolehan' => 1631818.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Printer inkjet kasir & laporan',
            ],
            [
                'nama' => 'UPS ICA 1200VA',
                'kelompok' => 'I',
                'tanggal_perolehan' => '2018-07-05',
                'harga_perolehan' => 6181818.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Penyimpan daya listrik mesin kasir & server',
            ],
            [
                'nama' => 'KOMPUTER Server Core i7',
                'kelompok' => 'I',
                'tanggal_perolehan' => '2018-09-12',
                'harga_perolehan' => 7500000.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Server database SpektrumX',
            ],
            [
                'nama' => 'Pendingin Ruangan AC Daikin 2 PK',
                'kelompok' => 'II',
                'tanggal_perolehan' => '2021-05-02',
                'harga_perolehan' => 9090909.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'AC Ruang Produksi Cetak',
            ],
            [
                'nama' => 'Mesin Potong Sugiyama',
                'kelompok' => 'III',
                'tanggal_perolehan' => '2017-06-18',
                'harga_perolehan' => 35000000.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Mesin potong kertas produksi indoor',
            ],
            [
                'nama' => 'Mesin Potong Wohlenberg',
                'kelompok' => 'III',
                'tanggal_perolehan' => '2017-06-25',
                'harga_perolehan' => 50000000.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Mesin potong kertas presisi berat',
            ],
            [
                'nama' => 'Mesin Cetak Oliver 58',
                'kelompok' => 'III',
                'tanggal_perolehan' => '2017-06-30',
                'harga_perolehan' => 45000000.00,
                'metode' => 'garis_lurus',
                'keterangan' => 'Mesin cetak offset brosur & poster',
            ],
        ];

        foreach ($assets as $asset) {
            AccountingFixedAsset::create($asset);
        }
    }
}
