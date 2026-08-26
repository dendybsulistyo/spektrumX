<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the trial chart of accounts and trial postings with the Chart
     * of Accounts used by "LEDGER SPEKTRA Jan 26". This is deliberately a
     * cut-over migration: the old am/am__ data was confirmed as test data.
     */
    public function up(): void
    {
        DB::transaction(function () {
            DB::table('am')->delete();
            DB::table('jurnal_manual')->delete();
            DB::table('pengeluaran')->update(['no_trans_jurnal' => null]);
            DB::table('am__')->delete();

            DB::table('am__')->insert([
                ['NoAkun' => '10000', 'NmAkun' => 'AKTIVA', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '11000', 'NmAkun' => 'AKTIVA LANCAR', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '11100', 'NmAkun' => 'Kas', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11101', 'NmAkun' => 'Bank', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11102', 'NmAkun' => 'Piutang Dagang', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11103', 'NmAkun' => 'Piutang Karyawan', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11200', 'NmAkun' => 'Persediaan Barang Jadi', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11300', 'NmAkun' => 'Persediaan Barang Proses', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11301', 'NmAkun' => 'Persediaan Bahan Baku', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11400', 'NmAkun' => 'Persediaan Bahan Penolong', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11401', 'NmAkun' => 'Persediaan Lain-lain', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11402', 'NmAkun' => 'PPN Dibayar Dimuka', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11403', 'NmAkun' => 'PPh Pasal 23', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11500', 'NmAkun' => 'PPh Pasal 25', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11600', 'NmAkun' => 'PPN Masukan', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11700', 'NmAkun' => 'Uang Muka Pembelian', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '11800', 'NmAkun' => 'Uang Muka Sewa', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '12000', 'NmAkun' => 'AKTIVA TETAP', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '12100', 'NmAkun' => 'Gedung dan Listrik', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '12200', 'NmAkun' => 'Peralatan Pabrik', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '12300', 'NmAkun' => 'Peralatan Kantor', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '12400', 'NmAkun' => 'Kendaraan', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '12500', 'NmAkun' => 'Inventaris Kantor', 'TipeDK' => 'D', 'TipeNL' => 'N'],
                ['NoAkun' => '12601', 'NmAkun' => 'Akm. Penyusutan Bangunan', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '12602', 'NmAkun' => 'Akm. Penyusutan Mesin', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '12603', 'NmAkun' => 'Akm. Penyusutan Peralatan Kantor', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '12604', 'NmAkun' => 'Akm. Penyusutan Kendaraan', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '12605', 'NmAkun' => 'Akm. Penyusutan Inventaris', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '20000', 'NmAkun' => 'KEWAJIBAN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '21100', 'NmAkun' => 'Hutang Dagang', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '22101', 'NmAkun' => 'Hutang PPh Final PP 46', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '22102', 'NmAkun' => 'Hutang PPh Pasal 23', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '22103', 'NmAkun' => 'Hutang PPh Pasal 4(2)', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '22104', 'NmAkun' => 'Hutang PPh Pasal 25/29', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '22105', 'NmAkun' => 'PPN Keluaran', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '23100', 'NmAkun' => 'Hutang Bank', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '24100', 'NmAkun' => 'Hutang Biaya', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '25100', 'NmAkun' => 'Hutang Lain-lain', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '26100', 'NmAkun' => 'Hutang Pemegang Saham', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '27100', 'NmAkun' => 'Uang Muka Penjualan', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '30000', 'NmAkun' => 'EKUITAS', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '31000', 'NmAkun' => 'Modal', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '32000', 'NmAkun' => 'Laba Ditahan Amnesty', 'TipeDK' => 'K', 'TipeNL' => 'N'],
                ['NoAkun' => '33000', 'NmAkun' => 'Prive', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '34000', 'NmAkun' => 'Pajak Penghasilan', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '40000', 'NmAkun' => 'PENDAPATAN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '41000', 'NmAkun' => 'Penjualan Barang', 'TipeDK' => 'K', 'TipeNL' => 'L'],
                ['NoAkun' => '41001', 'NmAkun' => 'Retur Penjualan Barang', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '41002', 'NmAkun' => 'Pendapatan Lain-lain', 'TipeDK' => 'K', 'TipeNL' => 'L'],
                ['NoAkun' => '50000', 'NmAkun' => 'HARGA POKOK PENJUALAN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '51000', 'NmAkun' => 'Persediaan Bahan Baku Awal', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '52000', 'NmAkun' => 'Persediaan Bahan Baku Akhir', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '53000', 'NmAkun' => 'Pembelian Bahan Baku', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '53001', 'NmAkun' => 'Persediaan Bahan Penolong Awal', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '53002', 'NmAkun' => 'Persediaan Bahan Penolong Akhir', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '53003', 'NmAkun' => 'Pembelian Bahan Penolong', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '53004', 'NmAkun' => 'Tenaga Kerja Langsung', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '53005', 'NmAkun' => 'Biaya Listrik', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '60000', 'NmAkun' => 'BIAYA OPERASIONAL', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '61000', 'NmAkun' => 'BIAYA MANAJEMEN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '61001', 'NmAkun' => 'Gaji & Tunjangan', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '61002', 'NmAkun' => 'Asuransi Tenaga Kerja', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '61003', 'NmAkun' => 'Perjalanan Dinas', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '61004', 'NmAkun' => 'Biaya Konsultan Pajak', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '62000', 'NmAkun' => 'BIAYA PENJUALAN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '62001', 'NmAkun' => 'Ongkos Kirim', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '62002', 'NmAkun' => 'BBM dan Parkir', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '62003', 'NmAkun' => 'Pamflet & Brosur', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63000', 'NmAkun' => 'BIAYA UMUM & ADMINISTRASI', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '63001', 'NmAkun' => 'Penyusutan', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63002', 'NmAkun' => 'Telepon', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63003', 'NmAkun' => 'Internet', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63004', 'NmAkun' => 'Listrik & Air', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63005', 'NmAkun' => 'Rapat/Pelatihan Karyawan', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63006', 'NmAkun' => 'Perlengkapan Kantor', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63007', 'NmAkun' => 'Administrasi Kantor (ATK)', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63008', 'NmAkun' => 'Biaya Brosur dan Pamflet', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63009', 'NmAkun' => 'Pajak Kendaraan & Retribusi', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63010', 'NmAkun' => 'Paket/Kurir', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63011', 'NmAkun' => 'Materai & Benda Pos', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63012', 'NmAkun' => 'Biaya Sparepart Inventaris', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63013', 'NmAkun' => 'Sanksi Pajak', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63014', 'NmAkun' => 'Biaya Lain-lain, Sumbangan', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '63015', 'NmAkun' => 'Biaya Rumah Tangga Perusahaan', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '64000', 'NmAkun' => 'BIAYA KEUANGAN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '64001', 'NmAkun' => 'Biaya Bunga Bank', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '64002', 'NmAkun' => 'Biaya Administrasi Bank', 'TipeDK' => 'D', 'TipeNL' => 'L'],
                ['NoAkun' => '70000', 'NmAkun' => 'PENDAPATAN & BIAYA LAIN-LAIN', 'TipeDK' => '-', 'TipeNL' => '-'],
                ['NoAkun' => '71000', 'NmAkun' => 'Penghasilan Klaim Asuransi', 'TipeDK' => 'K', 'TipeNL' => 'L'],
                ['NoAkun' => '72000', 'NmAkun' => 'Bunga Bank/Jasa Giro', 'TipeDK' => 'K', 'TipeNL' => 'L'],
                ['NoAkun' => '73000', 'NmAkun' => 'Selisih Kurs', 'TipeDK' => 'K', 'TipeNL' => 'L'],
                ['NoAkun' => '74000', 'NmAkun' => 'Biaya Lain-lain di Luar Usaha', 'TipeDK' => 'D', 'TipeNL' => 'L'],
            ]);
        });
    }

    public function down(): void
    {
        // This migration intentionally has no rollback: it replaces data
        // explicitly confirmed as trial accounting data.
    }
};
