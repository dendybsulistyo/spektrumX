<?php

namespace App\Services;

use App\Models\OrderPayment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Keuangan untuk "Batal Total" (approve-cancel indoor/outdoor/artwork).
 *
 * - Uang yang sudah dibayar dikembalikan sebesar nominal pilihan admin
 *   (default penuh, boleh kurang). Sisa yang tidak dikembalikan tetap
 *   menjadi pendapatan.
 * - Lunas : D Penjualan (+PPN) sebesar refund, K Kas/Bank.
 * - DP    : D Uang Muka sebesar DP, K Kas/Bank (refund), K Penjualan (sisa).
 * - Hutang: sisa piutang dihapus (D Penjualan +PPN, K Piutang Dagang) dan
 *   plafon customer dikembalikan; cicilan yang sudah masuk diperlakukan
 *   seperti order lunas.
 *
 * Refund dicatat sebagai OrderPayment jenis 'refund' (jumlah negatif) agar
 * tampil sekali di Rekap Kas Harian, Rekap Kasir per User dan Laporan Kasir
 * Harian. Dipanggil di dalam transaksi DB pemanggil (order sudah di-lock).
 */
class OrderCancellationRefund
{
    public const METHODS = ['tunai' => 'Tunai', 'transfer' => 'Transfer'];

    public function __construct(
        private AccountingService $accounting,
        private CustomerCreditService $creditService,
    ) {}

    /** Uang yang sudah diterima dari customer dan bisa dikembalikan. */
    public static function refundable(Model $order): float
    {
        return max(0, (float) ($order->jumlah_dibayar ?? 0));
    }

    /** Sisa piutang order hutang yang akan dihapus saat batal total. */
    public static function openReceivable(Model $order): float
    {
        return $order->status_bayar === 'hutang' ? max(0, (float) ($order->jumlah_piutang ?? 0)) : 0.0;
    }

    /** @return array{refund: float, cara_bayar: ?string, no_referensi: ?string} */
    public static function validated(array $input, Model $order): array
    {
        $paid = self::refundable($order);

        if ($paid <= 0) {
            return ['refund' => 0.0, 'cara_bayar' => null, 'no_referensi' => null];
        }

        $refund = round((float) preg_replace('/[^\d]/', '', (string) ($input['refund_amount'] ?? '')));
        $method = $input['refund_method'] ?? null;
        $reference = trim((string) ($input['refund_reference'] ?? '')) ?: null;
        $errors = [];

        if (! array_key_exists('refund_amount', $input) || $input['refund_amount'] === null || $input['refund_amount'] === '') {
            $errors['refund_amount'] = 'Isi nominal uang yang dikembalikan (boleh 0).';
        } elseif ($refund > $paid) {
            $errors['refund_amount'] = 'Nominal refund melebihi uang yang sudah dibayar (Rp '.number_format($paid, 0, ',', '.').').';
        }

        if ($refund > 0 && ! array_key_exists((string) $method, self::METHODS)) {
            $errors['refund_method'] = 'Pilih cara pengembalian: Tunai atau Transfer.';
        }

        if ($refund > 0 && $method === 'transfer' && ! $reference) {
            $errors['refund_reference'] = 'No. referensi transfer wajib diisi.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'refund' => $refund,
            'cara_bayar' => $refund > 0 ? $method : null,
            'no_referensi' => $refund > 0 && $method === 'transfer' ? mb_substr($reference, 0, 100) : null,
        ];
    }

    /**
     * @param  array{refund: float, cara_bayar: ?string, no_referensi: ?string}  $refund
     * @return string Catatan singkat untuk OrderStatusNote.
     */
    public function process(Model $order, string $type, array $refund): string
    {
        $paid = self::refundable($order);
        $receivable = self::openReceivable($order);
        $amount = min((float) $refund['refund'], $paid);
        $retained = $paid - $amount;
        $kdBantu = AccountingService::kodeBantuCustomer($order->customer?->KdCust);
        $date = now()->format('Y-m-d');
        $notes = [];

        try {
            if ($receivable > 0) {
                $this->accounting->post($date, $order->NoOrder, 'Pembatalan piutang '.$order->NoOrder, [
                    ...$this->accounting->salesDebitLines($receivable),
                    ['akun' => AccountingService::AKUN_PIUTANG_DAGANG, 'kredit' => $receivable, 'kd_bantu' => $kdBantu],
                ]);

                if ($order->customer) {
                    $order->customer->setRelation('limit', $order->customer->limit()->lockForUpdate()->first());
                }
                if ($order->customer?->limit) {
                    $this->creditService->reduceHutang($order->customer, $receivable);
                }

                $notes[] = 'piutang Rp '.$this->rupiah($receivable).' dihapus, plafon dikembalikan';
            }

            if ($order->status_bayar === 'dp' && $paid > 0) {
                $lines = [['akun' => AccountingService::AKUN_UANG_MUKA_PENJUALAN, 'debet' => $paid, 'kd_bantu' => $kdBantu]];
                if ($amount > 0) {
                    $lines[] = ['akun' => AccountingService::akunKasFor($refund['cara_bayar']), 'kredit' => $amount, 'kd_bantu' => $kdBantu];
                }
                if ($retained > 0) {
                    array_push($lines, ...$this->accounting->salesCreditLines($retained));
                }

                $this->accounting->post($date, $order->NoOrder, 'Pembatalan DP '.$order->NoOrder, $lines);
            } elseif ($amount > 0) {
                $this->accounting->post($date, $order->NoOrder, 'Refund pembatalan order '.$order->NoOrder, [
                    ...$this->accounting->salesDebitLines($amount),
                    ['akun' => AccountingService::akunKasFor($refund['cara_bayar']), 'kredit' => $amount, 'kd_bantu' => $kdBantu],
                ]);
            }
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['refund_amount' => 'Jurnal pembatalan gagal: '.$e->getMessage()]);
        }

        if ($amount > 0) {
            OrderPayment::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'jenis' => 'refund',
                'jumlah' => -$amount,
                'cara_bayar' => $refund['cara_bayar'],
                'no_referensi' => $refund['no_referensi'],
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);

            $notes[] = 'refund Rp '.$this->rupiah($amount).' ('.self::METHODS[$refund['cara_bayar']]
                .($refund['no_referensi'] ? ' '.$refund['no_referensi'] : '').')';
        }

        if ($retained > 0) {
            $notes[] = 'Rp '.$this->rupiah($retained).' tidak dikembalikan (tetap pendapatan)';
        }

        if ($paid > 0 || $receivable > 0) {
            $order->update(['jumlah_dibayar' => $retained, 'jumlah_piutang' => 0]);
        }

        return $notes ? ucfirst(implode('; ', $notes)).'.' : '';
    }

    private function rupiah(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
