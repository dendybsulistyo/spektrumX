<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerServiceJobSheet;
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
        $selectedCustomer = old('customer_code')
            ? Customer::where('KdCust', old('customer_code'))->first()
            : null;

        return view('customer-service.index', compact('selectedCustomer'));
    }

    public function jobSheets(Request $request): View
    {
        $tab = $request->query('tab') === 'claimed' ? 'claimed' : 'pending';
        $jobSheets = CustomerServiceJobSheet::with(['creator', 'customer', 'claimant'])
            ->when($tab === 'claimed', fn ($query) => $query->whereNotNull('claimed_at'))
            ->when($tab === 'pending', fn ($query) => $query->whereNull('claimed_at'))
            ->latest('received_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();
        $pendingCount = CustomerServiceJobSheet::whereNull('claimed_at')->count();
        $claimedCount = CustomerServiceJobSheet::whereNotNull('claimed_at')->count();

        return view('customer-service.job-sheets', compact('jobSheets', 'tab', 'pendingCount', 'claimedCount'));
    }

    public function claimJobSheet(CustomerServiceJobSheet $jobSheet, string $target): RedirectResponse
    {
        abort_unless(in_array($target, ['indoor', 'outdoor'], true), 404);

        DB::transaction(function () use ($jobSheet, $target): void {
            $lockedSheet = CustomerServiceJobSheet::lockForUpdate()->findOrFail($jobSheet->id);
            abort_if($lockedSheet->claimed_at, 422, 'Lembar kerja ini sudah diambil oleh Penerima File lain.');
            $lockedSheet->update([
                'claimed_by' => auth()->id(),
                'claimed_at' => now(),
                'claimed_order_type' => $target,
            ]);
        });

        return redirect()->route($target === 'outdoor' ? 'order-outdoor.create' : 'order-indoor.create', [
            'job_sheet' => $jobSheet->id,
        ]);
    }

    public function paymentQueue(): View
    {
        $indoor = OrderIndoor::with('customer.limit')
            ->where('payment_queue', 'cs')
            ->where('status_bayar', 'belum_bayar')
            ->get()
            ->each->setAttribute('order_type', 'indoor');
        $outdoor = OrderOutdoor::with('customer.limit')
            ->where('payment_queue', 'cs')
            ->where('status_bayar', 'belum_bayar')
            ->get()
            ->each->setAttribute('order_type', 'outdoor');

        $orders = $indoor->concat($outdoor)
            ->sortByDesc(fn ($order) => ($order->TglOrder?->format('Y-m-d') ?? (string) $order->TglOrder).'-'.$order->id)
            ->values();

        return view('customer-service.payment-queue', compact('orders'));
    }

    public function storeJobSheet(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_code' => ['required', 'string', 'exists:customers,KdCust'],
            'pc' => ['nullable', 'string', 'max:100'],
            'folder_file' => ['nullable', 'string', 'max:255'],
            'received_at' => ['required', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:received_at'],
            'opf' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_type' => ['required', 'in:OD,ID,AW,SB'],
            'items.*.width' => ['required', 'numeric', 'min:0.01'],
            'items.*.height' => ['required', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.material' => ['required', 'string', 'max:150'],
            'items.*.printer' => ['nullable', 'string', 'max:150'],
            'items.*.finishing' => ['nullable', 'string', 'max:150'],
        ]);

        $customer = Customer::where('KdCust', $data['customer_code'])->firstOrFail();

        CustomerServiceJobSheet::create([
            ...$data,
            'customer_name' => $customer->NmCust,
            'created_by' => auth()->id(),
        ]);

        return to_route('customer-service.job-sheets.index')->with('status', 'Lembar kerja Customer Service berhasil disimpan.');
    }

    public function forward(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate([
            'cs_payment_type' => ['nullable', 'in:hutang'],
            'cs_transfer_amount' => ['required_unless:cs_payment_type,hutang', 'nullable', 'numeric', 'min:100', 'multiple_of:100'],
        ], [
            'cs_transfer_amount.required' => 'Nominal transfer wajib diisi.',
            'cs_transfer_amount.required_unless' => 'Nominal transfer wajib diisi untuk pembayaran DP atau Pelunasan.',
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
            $order->loadMissing('customer.limit');

            if (($data['cs_payment_type'] ?? null) === 'hutang') {
                if (! $order->customer?->isVip) {
                    throw ValidationException::withMessages([
                        'cs_payment_type' => 'Hutang hanya dapat dipilih untuk customer VIP.',
                    ]);
                }

                $paymentType = 'hutang';
                $transferAmount = null;
            } else {
                $transferAmount = (float) $data['cs_transfer_amount'];

                if ($transferAmount > (float) $order->total) {
                    throw ValidationException::withMessages([
                        'cs_transfer_amount' => 'Nominal transfer tidak boleh melebihi total order Rp '.number_format((float) $order->total, 0, ',', '.').'.',
                    ]);
                }

                $minimumTransfer = (float) (ceil(((float) $order->total * 0.5) / 100) * 100);
                if ($transferAmount < $minimumTransfer) {
                    throw ValidationException::withMessages([
                        'cs_transfer_amount' => 'Nominal transfer minimal 50% dari total order, yaitu Rp '.number_format($minimumTransfer, 0, ',', '.').'.',
                    ]);
                }

                $paymentType = $transferAmount < (float) $order->total ? 'dp' : 'pelunasan';
            }

            $order->update([
                'cs_transfer_amount' => $transferAmount,
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
                'catatan' => $paymentType === 'hutang'
                    ? 'HUTANG — diajukan oleh Customer Service'
                    : strtoupper($paymentType).' — Transfer Rp '.number_format($transferAmount, 0, ',', '.'),
                'user_id' => auth()->id(),
                'created_at' => now(),
            ]);
        });

        return to_route('customer-service.payment-queue')->with('status', 'Informasi transfer tersimpan dan order diteruskan ke Kasir.');
    }
}
