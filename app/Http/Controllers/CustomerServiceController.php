<?php

namespace App\Http\Controllers;

use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerServiceController extends Controller
{
    public function index(): View
    {
        $indoor = OrderIndoor::with('customer')
            ->where('payment_queue', 'cs')->where('status_bayar', 'belum_bayar')->get()
            ->each->setAttribute('order_type', 'indoor');
        $outdoor = OrderOutdoor::with('customer')
            ->where('payment_queue', 'cs')->where('status_bayar', 'belum_bayar')->get()
            ->each->setAttribute('order_type', 'outdoor');

        $orders = $indoor->concat($outdoor)->sortByDesc(fn ($order) => ($order->TglOrder?->format('Y-m-d') ?? (string) $order->TglOrder).'-'.$order->id)->values();

        return view('customer-service.index', compact('orders'));
    }

    public function forward(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate([
            'cs_transfer_amount' => ['required', 'numeric', 'min:100', 'multiple_of:100'],
        ], [
            'cs_transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'cs_transfer_amount.multiple_of' => 'Nominal transfer harus kelipatan Rp100.',
        ]);

        $model = match ($type) {
            'indoor' => OrderIndoor::class,
            'outdoor' => OrderOutdoor::class,
            default => abort(404),
        };

        DB::transaction(function () use ($model, $id, $type, $data): void {
            $order = $model::lockForUpdate()->findOrFail($id);
            abort_unless($order->payment_queue === 'cs' && $order->status_bayar === 'belum_bayar', 422, 'Order ini sudah tidak berada di antrean CS.');

            if ((float) $data['cs_transfer_amount'] > (float) $order->total) {
                throw ValidationException::withMessages([
                    'cs_transfer_amount' => 'Nominal transfer tidak boleh melebihi total order Rp '.number_format((float) $order->total, 0, ',', '.').'.',
                ]);
            }

            $minimumTransfer = (float) (ceil(((float) $order->total * 0.5) / 100) * 100);
            if ((float) $data['cs_transfer_amount'] < $minimumTransfer) {
                throw ValidationException::withMessages([
                    'cs_transfer_amount' => 'Nominal transfer minimal 50% dari total order, yaitu Rp '.number_format($minimumTransfer, 0, ',', '.').'.',
                ]);
            }

            $paymentType = (float) $data['cs_transfer_amount'] < (float) $order->total
                ? 'dp'
                : 'pelunasan';

            $order->update([
                'cs_transfer_amount' => $data['cs_transfer_amount'],
                'cs_payment_type' => $paymentType,
                'cs_processed_by' => auth()->id(),
                'cs_processed_at' => now(),
                'payment_queue' => 'kasir',
            ]);

            OrderStatusNote::create([
                'order_type' => $type,
                'order_id' => $order->id,
                'stage' => 'customer_service',
                'action' => 'diteruskan_ke_kasir',
                'catatan' => strtoupper($paymentType).' — Transfer Rp '.number_format((float) $data['cs_transfer_amount'], 0, ',', '.'),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return to_route('customer-service.index')->with('status', 'Informasi transfer tersimpan dan order diteruskan ke Kasir.');
    }
}
