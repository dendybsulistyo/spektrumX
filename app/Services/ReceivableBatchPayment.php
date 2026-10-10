<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Models\OrderStatusNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Pembayaran piutang beberapa nota sekaligus untuk satu customer, dengan
 * satu cara bayar (Tunai / QRIS / Transfer).
 *
 * Setiap nota diproses persis seperti tombol Lunasi/cicilan di
 * KasirController (lunasiHutangLocked / cicilHutangLocked):
 * - OrderPayment jenis 'pelunasan_hutang' → tampil di Rekap Kas Harian,
 *   Rekap Kasir per User dan Laporan Kasir Harian;
 * - jurnal per nota: D Kas/Bank, K Piutang Dagang (Bukti = No. Order);
 * - plafon customer berkurang; nota lunas bila sisa piutang habis.
 *
 * Semua nota dalam satu transaksi: bila satu gagal, tidak ada yang tersimpan.
 */
class ReceivableBatchPayment
{
    public const METHODS = ['tunai' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'];

    private const MODELS = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

    public function __construct(
        private AccountingService $accounting,
        private OrderPaymentWorkflow $workflow,
    ) {}

    /**
     * @param  array<int, array{type: string, id: int, amount: float}>  $lines
     * @return array{count: int, total: float, settled: int}
     */
    public function pay(string $customerCode, array $lines, string $method, ?string $reference, ?string $note): array
    {
        $result = ['count' => 0, 'total' => 0.0, 'settled' => 0];

        try {
            DB::transaction(function () use ($customerCode, $lines, $method, $reference, $note, &$result) {
                foreach ($lines as $line) {
                    $model = self::MODELS[$line['type']] ?? null;
                    $order = $model ? $model::query()->find($line['id']) : null;

                    if (! $order || $order->KdCust !== $customerCode) {
                        throw ValidationException::withMessages(['pembayaran' => 'Nota tidak ditemukan untuk customer ini. Muat ulang halaman.']);
                    }

                    $this->workflow->run($order, 'hutang', function (Model $locked) use ($line, $method, $reference, $note, &$result) {
                        $settled = $this->payOne($locked, $line['type'], (float) $line['amount'], $method, $reference, $note);
                        $result['count']++;
                        $result['total'] += (float) $line['amount'];
                        $result['settled'] += $settled ? 1 : 0;
                    });
                }
            }, attempts: 3);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['pembayaran' => 'Pembayaran tidak disimpan: '.$e->getMessage()]);
        }

        return $result;
    }

    /** @return bool true bila nota menjadi lunas. */
    private function payOne(Model $order, string $type, float $amount, string $method, ?string $reference, ?string $note): bool
    {
        $remaining = (float) $order->jumlah_piutang;

        if ($remaining <= 0 || $amount <= 0 || $amount > $remaining + 0.5) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Nominal untuk nota '.$order->NoOrder.' melebihi sisa piutang atau piutang sudah berubah. Muat ulang halaman.',
            ]);
        }

        $settled = $amount + 0.5 >= $remaining;

        if (! $settled && ($amount < 100 || fmod($amount, 100) != 0)) {
            throw ValidationException::withMessages([
                'pembayaran' => 'Nominal cicilan nota '.$order->NoOrder.' harus kelipatan Rp 100 (minimal Rp 100).',
            ]);
        }
        $amount = $settled ? $remaining : $amount;
        $kdBantu = AccountingService::kodeBantuCustomer($order->customer?->KdCust);
        $label = self::METHODS[$method].($reference ? " (Ref: {$reference})" : '');

        $order->update($settled ? [
            'status_bayar' => 'lunas',
            'dibayar_at' => now(),
            'kasir_user_id' => auth()->id(),
            'cara_bayar' => $method,
            'no_referensi' => $reference,
            'jumlah_dibayar' => (float) $order->jumlah_dibayar + $amount,
            'jumlah_piutang' => 0,
        ] : [
            'jumlah_dibayar' => (float) $order->jumlah_dibayar + $amount,
            'jumlah_piutang' => $remaining - $amount,
        ]);

        if ($order->customer?->limit) {
            $order->customer->limit->decrement('Total', $amount);
        }

        OrderStatusNote::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'stage' => 'kasir',
            'action' => $settled ? 'selesai' : 'cicilan_hutang',
            'catatan' => ($settled ? 'Pelunasan hutang — ' : 'Cicilan hutang Rp '.number_format($amount, 0, ',', '.').' — ').$label
                .' · Pembayaran Piutang per Customer'.($note ? ' · '.$note : ''),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        OrderPayment::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'jenis' => 'pelunasan_hutang',
            'jumlah' => $amount,
            'cara_bayar' => $method,
            'no_referensi' => $reference,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->accounting->post(
            now()->format('Y-m-d'), $order->NoOrder, ($settled ? 'Pelunasan hutang ' : 'Cicilan hutang ').$order->NoOrder,
            [
                ['akun' => AccountingService::akunKasFor($method), 'debet' => $amount, 'kd_bantu' => $kdBantu],
                ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $amount, 'kd_bantu' => $kdBantu],
            ]
        );

        return $settled;
    }
}
