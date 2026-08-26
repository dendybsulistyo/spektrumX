<?php

namespace Database\Seeders;

use App\Models\AccountingPurchase;
use App\Models\AccountingPurchasePayment;
use App\Models\AccountingPurchaseReturn;
use App\Models\AccountingPurchaseLine;
use App\Models\AccountingSupplier;
use App\Services\AccountingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Optional, idempotent demo for validating the new purchase/payable flow.
 * It is deliberately not called by DatabaseSeeder: run it only when a demo
 * environment needs traceable examples with the new PB-YYMMDD-xxxx format.
 */
class AccountingPurchaseDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $accounting = app(AccountingService::class);
            $date = '2026-08-25';
            $firstPurchase = $this->purchase($accounting, 'H-001', 'PB-260825-0001', $date, 'Contoh pembelian bahan cetak — hutang supplier', 11100000, 'hutang', 'transfer');
            $secondPurchase = $this->purchase($accounting, 'H-005', 'PB-260825-0002', $date, 'Contoh pembelian bahan cetak — tunai', 2775000, 'tunai', 'transfer');
            $third = $this->purchase($accounting, 'H-008', 'PB-260825-0003', $date, 'Contoh pembelian tinta — hutang supplier', 8880000, 'hutang', 'transfer');
            $this->ensureLine($firstPurchase, 'Contoh bahan flexi', 'bahan_baku', 10, 1000000);
            $this->ensureLine($secondPurchase, 'Contoh bahan penolong', 'bahan_penolong', 5, 500000);
            $this->ensureLine($third, 'Contoh tinta solvent', 'bahan_baku', 8, 1000000);

            $this->payment($accounting, $third, '2026-08-26', 2220000, 'transfer', 'TRF-DEMO-0003');
            $this->returnPartial($accounting, $third, '2026-08-25', 1110000);
            $first = AccountingPurchase::where('nomor_bukti', 'PB-260825-0001')->firstOrFail();
            $this->payment($accounting, $first, '2026-08-27', 4000000, 'transfer', 'TRF-DEMO-0001');

            $firstPurchase->update(['termin_hari' => 30, 'tanggal_terima_invoice' => '2026-08-26']);
            $secondPurchase->update(['termin_hari' => 0, 'tanggal_terima_invoice' => '2026-08-25']);
            $third->update(['termin_hari' => 14, 'tanggal_terima_invoice' => '2026-08-26']);
        });
    }

    private function purchase(AccountingService $accounting, string $supplierCode, string $bukti, string $date, string $description, float $total, string $method, string $paymentMethod): AccountingPurchase
    {
        $existing = AccountingPurchase::where('nomor_bukti', $bukti)->first();
        if ($existing) return $existing;

        $supplier = AccountingSupplier::where('kode_bantu', $supplierCode)->firstOrFail();
        $dpp = round($total / 1.11);
        $ppn = $total - $dpp;
        $purchase = AccountingPurchase::create([
            'supplier_id' => $supplier->id, 'tanggal' => $date, 'nomor_bukti' => $bukti,
            'keterangan' => 'DEMO — '.$description, 'akun_pembelian' => '53000', 'dpp' => $dpp, 'ppn' => $ppn, 'total' => $total,
            'status' => $method === 'tunai' ? 'tunai' : 'hutang', 'cara_bayar' => $method === 'tunai' ? $paymentMethod : null,
            'no_referensi' => 'DEMO-'.$bukti, 'jumlah_dibayar' => $method === 'tunai' ? $total : 0, 'jumlah_hutang' => $method === 'hutang' ? $total : 0,
        ]);
        $lines = [['akun' => '53000', 'debet' => $dpp], ['akun' => AccountingService::AKUN_PPN_MASUKAN, 'debet' => $ppn]];
        $lines[] = $method === 'tunai'
            ? ['akun' => AccountingService::akunKasFor($paymentMethod), 'kredit' => $total]
            : ['akun' => AccountingService::AKUN_HUTANG_DAGANG, 'kredit' => $total, 'kd_bantu' => $supplier->kode_bantu];
        $purchase->update(['no_trans_jurnal' => $accounting->post($date, $bukti, $purchase->keterangan, $lines)]);
        return $purchase;
    }

    private function payment(AccountingService $accounting, AccountingPurchase $purchase, string $date, float $amount, string $method, string $reference): void
    {
        if (AccountingPurchasePayment::where('purchase_id', $purchase->id)->where('no_referensi', $reference)->exists()) return;
        $purchase->refresh()->load('supplier');
        $payment = AccountingPurchasePayment::create(['purchase_id' => $purchase->id, 'tanggal' => $date, 'jumlah' => $amount, 'cara_bayar' => $method, 'no_referensi' => $reference]);
        $payment->update(['no_trans_jurnal' => $accounting->post($date, 'BYR-'.$purchase->nomor_bukti, 'DEMO pelunasan '.$purchase->nomor_bukti, [
            ['akun' => AccountingService::AKUN_HUTANG_DAGANG, 'debet' => $amount, 'kd_bantu' => $purchase->supplier->kode_bantu],
            ['akun' => AccountingService::akunKasFor($method), 'kredit' => $amount],
        ])]);
        $remaining = $purchase->jumlah_hutang - $amount;
        $purchase->update(['jumlah_dibayar' => $purchase->jumlah_dibayar + $amount, 'jumlah_hutang' => $remaining, 'status' => $remaining <= 0 ? 'lunas' : 'hutang']);
    }

    private function returnPartial(AccountingService $accounting, AccountingPurchase $purchase, string $date, float $total): void
    {
        if (AccountingPurchaseReturn::where('nomor_bukti', 'RT-DEMO-0001')->exists()) return;
        $purchase->refresh()->load('supplier');
        $dpp = round($total * $purchase->dpp / $purchase->total);
        $ppn = $total - $dpp;
        $return = AccountingPurchaseReturn::create([
            'purchase_id' => $purchase->id, 'tanggal' => $date, 'nomor_bukti' => 'RT-DEMO-0001',
            'jenis' => 'retur', 'keterangan' => 'DEMO — retur sebagian tinta', 'dpp' => $dpp, 'ppn' => $ppn,
            'total' => $total, 'offset_hutang' => $total, 'refund' => 0,
        ]);
        $return->update(['no_trans_jurnal' => $accounting->post($date, 'RT-DEMO-0001', $return->keterangan, [
            ['akun' => AccountingService::AKUN_HUTANG_DAGANG, 'debet' => $total, 'kd_bantu' => $purchase->supplier->kode_bantu],
            ['akun' => $purchase->akun_pembelian, 'kredit' => $dpp],
            ['akun' => AccountingService::AKUN_PPN_MASUKAN, 'kredit' => $ppn],
        ])]);
        $purchase->update(['jumlah_retur' => $purchase->jumlah_retur + $total, 'jumlah_hutang' => $purchase->jumlah_hutang - $total]);
    }

    private function ensureLine(AccountingPurchase $purchase, string $description, string $classification, float $qty, float $price): void
    {
        if ($purchase->lines()->exists()) return;
        AccountingPurchaseLine::create(['purchase_id' => $purchase->id, 'deskripsi' => $description, 'klasifikasi' => $classification, 'akun' => $classification === 'bahan_penolong' ? '53003' : '53000', 'qty' => $qty, 'satuan' => 'unit', 'harga_satuan' => $price, 'subtotal' => $purchase->dpp]);
    }
}
