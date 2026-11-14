<?php

namespace App\Http\Controllers;

use App\Models\CsTransferMasuk;
use App\Models\Customer;
use App\Models\CustomerServiceJobSheet;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderStatusNote;
use App\Models\User;
use App\Services\CsPendingOrders;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
            ->when($keyword !== '', function ($query) use ($keyword): void {
                // Nama customer juga dicocokkan ke data master (nama terbaru), lewat kode customer.
                $customerCodes = Customer::query()->where('NmCust', 'like', "%{$keyword}%")->limit(200)->pluck('KdCust');
                $query->where(function ($search) use ($keyword, $customerCodes): void {
                    $search->where('customer_name', 'like', "%{$keyword}%")
                        ->when($customerCodes->isNotEmpty(), fn ($query) => $query->orWhereIn('customer_code', $customerCodes))
                        ->orWhere('customer_code', 'like', "%{$keyword}%")
                        ->orWhere('pc', 'like', "%{$keyword}%")
                        ->orWhere('folder_file', 'like', "%{$keyword}%")
                        ->orWhere('opf', 'like', "%{$keyword}%")
                        ->orWhere('notes', 'like', "%{$keyword}%")
                        ->orWhere('order_number', 'like', "%{$keyword}%")
                        ->orWhere('items', 'like', "%{$keyword}%")
                        ->orWhereHas('claimant', fn ($user) => $user->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->when($tab === 'claimed', fn ($query) => $query->latest('claimed_at'))
            // Order urgent (mis. order owner) selalu di atas antrean.
            ->when($tab === 'pending', fn ($query) => $query->orderByDesc('is_urgent')->latest('received_at'))
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

        $pendingCount = $tab === 'pending' && $keyword === ''
            ? $jobSheets->total()
            : CustomerServiceJobSheet::whereNull('claimed_at')->count();
        $claimedCount = $tab === 'claimed'
            ? $jobSheets->total()
            : CustomerServiceJobSheet::whereNotNull('claimed_at')
                ->whereDate('claimed_at', '>=', now()->subMonth()->toDateString())
                ->whereDate('claimed_at', '<=', now()->toDateString())
                ->count();
        $totalClaimedCount = CustomerServiceJobSheet::whereNotNull('claimed_at')->count();

        // Lembar yang diambil tapi belum jadi order.
        $stuckSheets = CustomerServiceJobSheet::stuck()
            ->with('claimant:id,name')
            // CS & admin melihat semua (untuk dihapus); operator hanya miliknya.
            ->when(! auth()->user()->hasPermission('roles.manage') && ! auth()->user()->hasPermission('customer-service.manage'),
                fn ($query) => $query->where('claimed_by', auth()->id()))
            ->oldest('claimed_at')
            ->get();

        return view('customer-service.job-sheets', compact(
            'stuckSheets',
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

    /** Buka lagi form order dari lembar yang sudah diambil tetapi ordernya belum tersimpan. */
    public function resumeJobSheet(CustomerServiceJobSheet $jobSheet): RedirectResponse
    {
        if (! $jobSheet->isStuck()) {
            return to_route('customer-service.job-sheets.index', ['tab' => 'claimed'])
                ->with('error', 'Lembar kerja ini sudah menjadi order '.($jobSheet->order_number ?: '').' atau belum diambil.');
        }
        abort_unless($jobSheet->canBeHandledBy(auth()->user()), 403, 'Lembar kerja ini diambil operator lain.');

        return redirect()->route($jobSheet->claimed_order_type === 'outdoor' ? 'order-outdoor.create' : 'order-indoor.create', [
            'job_sheet' => $jobSheet->id,
        ]);
    }

    /** Kembalikan lembar yang tersangkut ke antrean agar bisa diambil lagi (oleh siapa pun). */
    public function releaseJobSheet(CustomerServiceJobSheet $jobSheet): RedirectResponse
    {
        abort_unless($jobSheet->canBeHandledBy(auth()->user()), 403, 'Lembar kerja ini diambil operator lain.');

        $released = DB::transaction(function () use ($jobSheet): bool {
            $locked = CustomerServiceJobSheet::lockForUpdate()->findOrFail($jobSheet->id);
            if (! $locked->isStuck()) {
                return false;
            }

            return $locked->update(['claimed_by' => null, 'claimed_at' => null, 'claimed_order_type' => null]);
        });

        return to_route('customer-service.job-sheets.index', ['tab' => 'pending'])->with(
            $released ? 'status' : 'error',
            $released ? 'Lembar kerja dikembalikan ke antrean.' : 'Lembar kerja sudah menjadi order sehingga tidak dapat dikembalikan.'
        );
    }

    public function destroyJobSheet(CustomerServiceJobSheet $jobSheet): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($jobSheet): bool {
            $lockedSheet = CustomerServiceJobSheet::query()->lockForUpdate()->findOrFail($jobSheet->id);

            // Boleh dihapus selama belum diambil, atau sudah diambil tetapi tidak pernah jadi order (tersangkut).
            if ($lockedSheet->claimed_at && ! $lockedSheet->isStuck()) {
                return false;
            }

            return (bool) $lockedSheet->delete();
        });

        if (! $deleted) {
            return to_route('customer-service.job-sheets.index', ['tab' => 'pending'])
                ->with('error', 'Lembar kerja sudah menjadi order sehingga tidak dapat dihapus.');
        }

        return to_route('customer-service.job-sheets.index', ['tab' => 'pending'])
            ->with('status', 'Lembar kerja berhasil dihapus.');
    }

    public function paymentQueue(Request $request): View
    {
        // Dipisah tab Indoor / Outdoor seperti halaman Kasir.
        $initialTab = in_array($request->query('tab'), ['indoor', 'outdoor', 'transfer'], true) ? $request->query('tab') : 'indoor';
        // Filter tanggal order opsional; kosong = seluruh antrean (antrean tidak boleh terlewat).
        $dari = $request->date('dari')?->format('Y-m-d');
        $sampai = $request->date('sampai')?->format('Y-m-d');
        $byDate = fn ($query) => $query
            ->when($dari, fn ($q) => $q->whereDate('TglOrder', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('TglOrder', '<=', $sampai));

        $indoor = OrderIndoor::with(['customer.limit', 'items.produkArtwork'])
            ->where('payment_queue', 'cs')
            ->where('status_bayar', 'belum_bayar')
            ->tap($byDate)
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
            ->tap($byDate)
            ->get()
            ->each->setAttribute('order_type', 'outdoor');

        $orders = $indoor->concat($outdoor)
            ->sortByDesc(fn ($order) => ($order->TglOrder?->format('Y-m-d') ?? (string) $order->TglOrder).'-'.$order->id)
            ->values();

        $transfers = CsTransferMasuk::with('creator:id,name')->belumDipakai()->latest()->latest('id')->get();
        $transferOrders = CsPendingOrders::forCustomers($transfers->pluck('customer_code')->unique()->values()->all(), perCustomer: 20, flagCustomArtwork: true);

        $counts = ['indoor' => $indoor->count(), 'outdoor' => $outdoor->count(), 'transfer' => $transfers->count()];

        return view('customer-service.payment-queue', compact('orders', 'transfers', 'transferOrders', 'initialTab', 'counts', 'dari', 'sampai'));
    }

    public function storeTransferMasuk(Request $request): RedirectResponse
    {
        $request->merge(['amount' => preg_replace('/\D/', '', (string) $request->input('amount'))]);

        $data = $request->validate([
            'customer_code' => ['required', 'string', 'exists:customers,KdCust'],
            'amount' => ['required', 'integer', 'min:1'],
            'transfer_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'customer_code.required' => 'Pilih customer dari hasil pencarian.',
        ]);

        CsTransferMasuk::create([
            ...$data,
            'customer_name' => Customer::where('KdCust', $data['customer_code'])->value('NmCust'),
            'created_by' => auth()->id(),
        ]);

        return to_route('customer-service.payment-queue', ['tab' => 'transfer'])->with('status', 'Transfer masuk berhasil dicatat.');
    }

    public function destroyTransferMasuk(CsTransferMasuk $transfer): RedirectResponse
    {
        abort_if($transfer->used_at !== null, 422, 'Transfer yang sudah dipakai tidak bisa dihapus.');

        $transfer->delete();

        return to_route('customer-service.payment-queue', ['tab' => 'transfer'])->with('status', "Transfer {$transfer->customer_name} dihapus.");
    }

    /**
     * Tandai transfer sudah dipakai. Bila CS memilih order dari antrean CS, order itu
     * sekaligus diteruskan ke Kasir dengan nominal transfer ini (aturan sama dengan
     * tombol di tab Indoor/Outdoor). Tanpa order terpilih, nomor order diketik manual.
     */
    public function useTransferMasuk(Request $request, CsTransferMasuk $transfer): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['nullable', 'string', 'regex:/^(indoor|outdoor):\d+$/'],
            'used_order_number' => ['nullable', 'string', 'max:40'],
        ]);

        $forwarded = DB::transaction(function () use ($transfer, $data): ?string {
            $transfer = CsTransferMasuk::lockForUpdate()->findOrFail($transfer->id);
            abort_if($transfer->used_at !== null, 422, 'Transfer ini sudah ditandai dipakai.');

            $orderNumber = $data['used_order_number'] ?? null;
            $forwardedNumber = null;

            if (! empty($data['order'])) {
                [$type, $id] = explode(':', $data['order']);
                $model = $type === 'indoor' ? OrderIndoor::class : OrderOutdoor::class;
                $amount = (float) $transfer->amount;

                if (fmod($amount, 100) !== 0.0) {
                    throw ValidationException::withMessages(['order' => 'Nominal transfer harus kelipatan Rp100 untuk diteruskan ke Kasir.']);
                }
                abort_unless(trim((string) $model::whereKey($id)->value('KdCust')) === trim($transfer->customer_code), 422, 'Order tersebut bukan milik customer ini.');

                $order = $this->forwardToKasir($model, (int) $id, $type, ['cs_transfer_amount' => $amount], [], false, requiresFixedPrice: true);
                $orderNumber = $forwardedNumber = $order->NoOrder;
            }

            $transfer->update([
                'used_at' => now(),
                'used_order_number' => $orderNumber,
                'used_by' => auth()->id(),
            ]);

            return $forwardedNumber;
        });

        return to_route('customer-service.payment-queue', ['tab' => 'transfer'])->with('status', $forwarded
            ? "Transfer {$transfer->customer_name} dipakai untuk {$forwarded}; order diteruskan ke Kasir."
            : "Transfer {$transfer->customer_name} ditandai sudah dipakai.");
    }

    public function storeJobSheet(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_code' => ['required', 'string', 'exists:customers,KdCust'],
            'pc' => ['nullable', 'string', 'max:100'],
            'folder_file' => ['nullable', 'string', 'max:255'],
            'received_at' => ['required', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:received_at'],
            'progress_hari' => ['required', 'integer', 'between:1,10'],
            'opf' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'order_type' => ['required', 'in:indoor,outdoor'],
            'indoor_folder' => ['nullable', 'required_if:order_type,indoor', 'in:sublime,artwork,pod', 'prohibited_unless:order_type,indoor'],
            'is_urgent' => ['nullable', 'boolean'],
        ]);
        $data['is_urgent'] = $request->boolean('is_urgent');

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
        // Tombol "Proses di Kasir": customer bayar langsung di toko, CS tidak
        // mengisi nominal transfer. Kasir memproses seperti order biasa.
        $directToCashier = $request->boolean('direct_kasir');
        if ($directToCashier) {
            $request->merge(['cs_payment_type' => null, 'cs_transfer_amount' => null]);
        }
        $data = $request->validate([
            'cs_payment_type' => ['nullable', 'in:hutang'],
            'cs_transfer_amount' => [Rule::requiredIf(fn () => ! $directToCashier && $request->input('cs_payment_type') !== 'hutang'), 'nullable', 'numeric', 'min:100', 'multiple_of:100'],
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

        DB::transaction(fn () => $this->forwardToKasir($model, $id, $type, $data, $artworkPrices, $directToCashier));

        return to_route('customer-service.payment-queue', ['tab' => $type])->with('status', $directToCashier
            ? 'Order diteruskan ke Kasir untuk dibayar langsung di Kasir.'
            : 'Informasi transfer tersimpan dan order diteruskan ke Kasir.');
    }

    /**
     * Teruskan order dari antrean CS ke Kasir. Dipakai tombol di tab Indoor/Outdoor
     * dan tombol "Sudah dipakai" di tab Transfer Masuk; harus dipanggil di dalam transaksi.
     *
     * @param  class-string<OrderIndoor|OrderOutdoor>  $model
     * @param  array{cs_payment_type?: ?string, cs_transfer_amount?: mixed}  $data
     * @param  array<int, float>  $artworkPrices
     */
    private function forwardToKasir(string $model, int $id, string $type, array $data, array $artworkPrices, bool $directToCashier, bool $requiresFixedPrice = false): OrderIndoor|OrderOutdoor
    {
        $order = $model::lockForUpdate()->findOrFail($id);
        abort_unless($order->payment_queue === 'cs' && $order->status_bayar === 'belum_bayar', 422, 'Order ini sudah tidak berada di antrean CS.');
        $order->loadMissing('customer.limit');
        if ($type === 'indoor') {
            $order->loadMissing('items.produkArtwork');
            if ($requiresFixedPrice && $this->customArtworkItems($order)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'order' => "Order {$order->NoOrder} berisi Artwork custom; isi harganya dan teruskan lewat tab Indoor.",
                ]);
            }
            if ($this->customArtworkItems($order)->isNotEmpty()) {
                $this->applyArtworkPrices($order, $artworkPrices);
                $order->refresh()->loadMissing(['customer.limit', 'items']);
            }
        }
        $isCustomArtwork = $type === 'indoor'
            && $this->customArtworkItems($order)->isNotEmpty();

        if ($directToCashier) {
            $paymentType = 'bayar_kasir';
            $transferAmount = null;
        } elseif (($data['cs_payment_type'] ?? null) === 'hutang') {
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
            'catatan' => match ($paymentType) {
                'hutang' => 'HUTANG — diajukan oleh Customer Service',
                'bayar_kasir' => 'BAYAR DI KASIR — customer membayar langsung di Kasir (tanpa transfer)',
                default => strtoupper($paymentType).' — Transfer Rp '.number_format($transferAmount, 0, ',', '.'),
            },
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return $order;
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
        // Perbarui harga per baris agar SO/nota menampilkan harga custom baru.
        $this->pricing->snapshotLinePrices('indoor', $order);

        if ($changes || $oldTotal !== $newTotal) {
            OrderStatusNote::create([
                'order_type' => 'indoor', 'order_id' => $order->id,
                'stage' => 'customer_service', 'action' => 'harga_artwork_custom',
                'catatan' => 'Harga Artwork custom disimpan. Total Rp '.number_format($oldTotal, 0, ',', '.').' → Rp '.number_format($newTotal, 0, ',', '.').'. '.implode(' · ', $changes),
                'user_id' => auth()->id(), 'created_at' => now(),
            ]);
        }
    }

    public static function customArtworkItems(OrderIndoor $order): Collection
    {
        return $order->items->filter(fn ($item) => ($item->isArtwork()
            && ($item->produkArtwork === null || (float) $item->produkArtwork->HargaStd <= 0))
            || (! $item->isArtwork() && in_array((string) $item->KdProd, OrderPricingService::indoorCustomPriceCodes(), true)));
    }
}
