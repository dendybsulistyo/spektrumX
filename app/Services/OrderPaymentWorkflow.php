<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderPaymentWorkflow
{
    /**
     * Serialize cashier actions before validating amounts or posting money.
     * Status, payment rows, credit usage and journal entries commit together.
     */
    public function run(Model $order, string $expectedStatus, Closure $action): mixed
    {
        return DB::transaction(function () use ($order, $expectedStatus, $action) {
            $locked = $order->newQuery()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status_bayar !== $expectedStatus) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Status pembayaran sudah berubah atau transaksi sudah diproses. Muat ulang halaman sebelum melanjutkan.',
                ]);
            }

            if ($locked->status === 'batal' || $locked->invoice_voided_at || $locked->cancel_requested_at) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Order yang dibatalkan atau menunggu pembatalan tidak dapat menerima pembayaran.',
                ]);
            }

            // Different orders for the same VIP share one credit balance.
            // Lock it before the cashier reads the available credit limit.
            if (method_exists($locked, 'customer') && $locked->customer) {
                $limit = $locked->customer->limit()->lockForUpdate()->first();
                $locked->customer->setRelation('limit', $limit);
            }

            $result = $action($locked);
            app(OrderDocumentService::class)->issueInvoice($locked->fresh());

            return $result;
        });
    }
}
