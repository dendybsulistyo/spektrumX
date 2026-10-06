<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerServiceJobSheet;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Models\User;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerServiceController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricing) {}

    public function index(): View
    {
        $selectedCustomer = old('customer_code')
            ? Customer::where('KdCust', old('customer_code'))->first()
            : null;

        return view('customer-service.index', compact('selectedCustomer'));
    }

    public function jobSheets(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'history' => ['nullable', 'in:all'],
        ]);
        $tab = $request->query('tab') === 'claimed' ? 'claimed' : 'pending';
        $showAllHistory = $tab === 'claimed'
            && ($filters['history'] ?? null) === 'all'
            && blank($filters['from'] ?? null)
            && blank($filters['to'] ?? null);
        $from = $tab === 'claimed' && ! $showAllHistory
            ? ($filters['from'] ?? now()->subMonth()->toDateString())
            : null;
        $to = $tab === 'claimed' && ! $showAllHistory
            ? ($filters['to'] ?? now()->toDateString())
            : null;
        $keyword = trim($filters['q'] ?? '');

        $jobSheets = CustomerServiceJobSheet::query()
            ->when($tab === 'claimed', fn ($query) => $query->whereNotNull('claimed_at'))
            ->when($tab === 'pending', fn ($query) => $query->whereNull('claimed_at'))
            ->when($tab === 'claimed' && $from, fn ($query) => $query->whereDate('claimed_at', '>=', $from))
            ->when($tab === 'claimed' && $to, fn ($query) => $query->whereDate('claimed_at', '<=', $to))
            ->when($tab === 'claimed' && $keyword !== '', function ($query) use ($keyword): void {
                $query->where(function ($search) use ($keyword): void {
                    $search->where('customer_name', 'like', "%{$keyword}%")
                        ->orWhere('customer_code', 'like', "%{$keyword}%")
                        ->orWhere('pc', 'like', "%{$keyword}%")
                        ->orWhere('folder_file', 'like', "%{$keyword}%")
                        ->orWhere('opf', 'like', "%{$keyword}%")
                        ->orWhere('items', 'like', "%{$keyword}%")
                        ->orWhereHas('claimant', fn ($user) => $user->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($tab === 'claimed', fn ($query) => $query->latest('claimed_at'))
            ->when($tab === 'pending', fn ($query) => $query->latest('received_at'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $userIds = $jobSheets->getCollection()
            ->flatMap(fn (CustomerServiceJobSheet $sheet) => [$sheet->created_by, $sheet->claimed_by])
            ->filter()
            ->unique();
        $usersById = $userIds->isEmpty()
            ? collect()
            : User::query()
                ->select(['id', 'name'])
                ->whereKey($userIds)
                ->get()
                ->keyBy('id');
        $jobSheets->getCollection()->each(function (CustomerServiceJobSheet $sheet) use ($usersById): void {
            $sheet->setRelation('creator', $usersById->get($sheet->created_by));
            $sheet->setRelation('claimant', $usersById->get($sheet->claimed_by));
        });

        $pendingCount = $tab === 'pending'
            ? $jobSheets->total()
            : CustomerServiceJobSheet::whereNull('claimed_at')->count();
        $claimedCount = $tab === 'claimed'
            ? $jobSheets->total()
            : CustomerServiceJobSheet::whereNotNull('claimed_at')
                ->whereDate('claimed_at', '>=', now()->subMonth()->toDateString())
                ->whereDate('claimed_at', '<=', now()->toDateString())
                ->count();
        $totalClaimedCount = CustomerServiceJobSheet::whereNotNull('claimed_at')->count();

        return view('customer-service.job-sheets', compact(
            'jobSheets',
            'tab',
            'pendingCount',
            'claimedCount',
            'totalClaimedCount',
            'from',
            'to',
            'keyword',
            'showAllHistory'
        ));
    }

    public function claimJobSheet(CustomerServiceJobSheet $jobSheet): RedirectResponse
    {
        abort_unless(in_array($jobSheet->order_type, ['indoor', 'outdoor'], true), 422, 'Tujuan order pada lembar kerja belum ditentukan.');

        $target = $jobSheet->order_type;

        $alreadyClaimed = DB::transaction(function () use ($jobSheet, $target): ?CustomerServiceJobSheet {
            $lockedSheet = CustomerServiceJobSheet::lockForUpdate()->findOrFail($jobSheet->id);

            if ($lockedSheet->claimed_at) {
                return $lockedSheet->load('claimant');
            }

            $lockedSheet->update([
                'claimed_by' => auth()->id(),
                'claimed_at' => now(),
                'claimed_order_type' => $target,
            ]);

            return null;
        });

        if ($alreadyClaimed) {
            $operator = $alreadyClaimed->claimant?->name
                ? ucwords(mb_strtolower($alreadyClaimed->claimant->name))
                : 'operator lain';
            $claimedAt = $alreadyClaimed->claimed_at->format('d/m/Y H:i');

            return to_route('customer-service.job-sheets.index', ['tab' => 'claimed'])
                ->with('error', "Lembar kerja ini sudah diambil oleh {$operator} pada {$claimedAt} dan tidak dapat diambil kembali.");
        }

        return redirect()->route($target === 'outdoor' ? 'order-outdoor.create' : 'order-indoor.create', [
            'job_sheet' => $jobSheet->id,
        ]);
    }

    public function destroyJobSheet(CustomerServiceJobSheet $jobSheet): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($jobSheet): bool {
            $lockedSheet = CustomerServiceJobSheet::query()->lockForUpdate()->findOrFail($jobSheet->id);

            if ($lockedSheet->claimed_at) {
                return false;
            }

            return (bool) $lockedSheet->delete();
        });

        if (! $deleted) {
            return to_route('customer-service.job-sheets.index', ['tab' => 'pending'])
                ->with('error', 'Lembar kerja sudah diambil operator sehingga tidak dapat dihapus.');
        }

        return to_route('customer-service.job-sheets.index', ['tab' => 'pending'])
            ->with('status', 'Lembar kerja yang belum diambil berhasil dihapus.');
    }

    public function paymentQueue(): View
    {
        $indoor = OrderIndoor::with(['customer.limit', 'items.produkArtwork'])
            ->where('payment_queue', 'cs')
            ->where('status_bayar', 'belum_bayar')
            ->get()
            ->each(function (OrderIndoor $order): void {
                $order->setAttribute('order_type', 'indoor');
                $customItems = $this->customArtworkItems($order);
                $customItems->each->setAttribute('requires_custom_price', true);
                $order->setAttribute('is_custom_artwork', $customItems->isNotEmpty());
            });
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
            'order_type' => ['required', 'in:indoor,outdoor'],
            'indoor_folder' => ['nullable', 'required_if:order_type,indoor', 'in:sublime,artwork,pod', 'prohibited_unless:order_type,indoor'],
        ]);

        $customer = Customer::where('KdCust', $data['customer_code'])->firstOrFail();

        CustomerServiceJobSheet::create([
            ...$data,
            'items' => [],
            'customer_name' => $customer->NmCust,
            'created_by' => auth()->id(),
        ]);

        return to_route('customer-service.job-sheets.index')->with('status', 'Lembar kerja Customer Service berhasil disimpan.');
    }

    public function saveArtworkPrices(Request $request, int $id): RedirectResponse
    {
        $prices = $this->validatedArtworkPrices($request);

        DB::transaction(function () use ($id, $prices): void {
            $order = OrderIndoor::query()->lockForUpdate()->findOrFail($id);
            abort_unless($order->payment_queue === 'cs' && $order->status_bayar === 'belum_bayar', 422, 'Order ini sudah tidak berada di antrean CS.');
            $this->applyArtworkPrices($order, $prices);
        }, attempts: 3);

        return redirect()->route('invoice.show', [
            'type' => 'indoor', 'id' => $id, 'source' => 'cs', 'draft' => 1,
        ]);
    }

    public function forward(Request $request, string $type, int $id): RedirectResponse
    {
        $artworkPrices = $type === 'indoor' ? $this->validatedArtworkPrices($request, required: false) : [];
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

        DB::transaction(function () use ($model, $id, $type, $data, $artworkPrices): void {
            $order = $model::lockForUpdate()->findOrFail($id);
            abort_unless($order->payment_queue === 'cs' && $order->status_bayar === 'belum_bayar', 422, 'Order ini sudah tidak berada di antrean CS.');
            $order->loadMissing('customer.limit');
            if ($type === 'indoor') {
                $order->loadMissing('items.produkArtwork');
                if ($this->customArtworkItems($order)->isNotEmpty()) {
                    $this->applyArtworkPrices($order, $artworkPrices);
                    $order->refresh()->loadMissing(['customer.limit', 'items']);
                }
            }
            $isCustomArtwork = $type === 'indoor'
                && $this->customArtworkItems($order)->isNotEmpty();

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

                if (! $isCustomArtwork && $transferAmount > (float) $order->total) {
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

                $paymentType = $isCustomArtwork && $transferAmount > (float) $order->total
                    ? 'dp'
                    : ($transferAmount < (float) $order->total ? 'dp' : 'pelunasan');
            }

            $order->update([
                'cs_transfer_amount' => $transferAmount,
                'cs_order_total' => (float) $order->total,
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

    /** @return array<int, float> */
    private function validatedArtworkPrices(Request $request, bool $required = true): array
    {
        $prices = collect($request->input('artwork_prices', []))
            ->mapWithKeys(fn ($value, $key) => [(int) $key => preg_replace('/\D/', '', (string) $value)])
            ->all();
        $request->merge(['artwork_prices' => $prices]);

        $validated = $request->validate([
            'artwork_prices' => [$required ? 'required' : 'nullable', 'array'],
            'artwork_prices.*' => ['required', 'numeric', 'min:100', 'multiple_of:100'],
        ], [
            'artwork_prices.required' => 'Harga custom Artwork wajib diisi.',
            'artwork_prices.*.required' => 'Semua harga custom Artwork wajib diisi.',
            'artwork_prices.*.multiple_of' => 'Harga custom Artwork harus kelipatan Rp100.',
        ]);

        return collect($validated['artwork_prices'] ?? [])->map(fn ($value) => (float) $value)->all();
    }

    /** @param array<int, float> $prices */
    private function applyArtworkPrices(OrderIndoor $order, array $prices): void
    {
        $order->loadMissing('items.produkArtwork');
        $artworkItems = $this->customArtworkItems($order);
        abort_if($artworkItems->isEmpty(), 422, 'Order ini tidak memiliki item Artwork.');

        $missing = $artworkItems->first(fn ($item) => ! isset($prices[$item->id]) && ! ((float) $item->harga_satuan_kasir > 0));
        if ($missing) {
            throw ValidationException::withMessages([
                "artwork_prices.{$missing->id}" => 'Harga custom untuk '.$missing->Judul.' wajib diisi.',
            ]);
        }

        $changes = [];
        foreach ($artworkItems as $item) {
            $newPrice = $prices[$item->id] ?? (float) $item->harga_satuan_kasir;
            $oldPrice = (float) ($item->harga_satuan_kasir ?? 0);
            if ($newPrice !== $oldPrice) {
                $item->update(['harga_satuan_kasir' => $newPrice]);
                $changes[] = ($item->Judul ?: $item->NmProd).': Rp '.number_format($oldPrice, 0, ',', '.').' → Rp '.number_format($newPrice, 0, ',', '.');
            }
        }

        $oldTotal = (float) $order->total;
        $newTotal = $this->pricing->totalIndoor($order->fresh());
        $order->update(['total' => $newTotal, 'cs_order_total' => $newTotal]);

        if ($changes || $oldTotal !== $newTotal) {
            OrderStatusNote::create([
                'order_type' => 'indoor', 'order_id' => $order->id,
                'stage' => 'customer_service', 'action' => 'harga_artwork_custom',
                'catatan' => 'Harga Artwork custom disimpan. Total Rp '.number_format($oldTotal, 0, ',', '.').' → Rp '.number_format($newTotal, 0, ',', '.').'. '.implode(' · ', $changes),
                'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }
    }

    private function customArtworkItems(OrderIndoor $order): \Illuminate\Support\Collection
    {
        return $order->items->filter(fn ($item) => $item->isArtwork()
            && ($item->produkArtwork === null || (float) $item->produkArtwork->HargaStd <= 0));
    }
}
