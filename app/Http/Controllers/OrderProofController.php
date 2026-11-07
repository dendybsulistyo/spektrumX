<?php

namespace App\Http\Controllers;

use App\Models\OrderProof;
use App\Services\OrderNumberService;
use App\Support\PageVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Order proof: contoh kecil tanpa tagihan yang langsung menjadi perintah Operator Layout.
 * Tidak memakai tabel order sehingga tidak masuk kasir, laporan keuangan, omzet, maupun piutang.
 */
class OrderProofController extends Controller
{
    private const NUMBER_TYPES = ['outdoor' => 'prf-out', 'indoor' => 'prf-in', 'artwork' => 'prf-art'];

    public function __construct(private OrderNumberService $orderNumbers) {}

    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status'), OrderProof::STATUS_LABELS) ? $request->query('status') : null;
        $search = trim((string) $request->query('q'));

        $proofs = OrderProof::query()->with('customer:id,KdCust,NmCust', 'createdBy:id,name', 'items')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search !== '', fn ($query) => $query->where(fn ($scope) => $scope
                ->where('NoOrder', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customer) => $customer->where('NmCust', 'like', "%{$search}%"))))
            ->latest()->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return view('order-proof.index', compact('proofs', 'status', 'search'));
    }

    public function create(): View
    {
        return view('order-proof.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_type' => ['required', Rule::in(array_keys(OrderProof::TYPE_LABELS))],
            'KdCust' => ['required', 'string', Rule::exists('customers', 'KdCust')],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.NmFile' => ['nullable', 'string', 'max:255'],
            'items.*.kode' => ['nullable', 'string', 'max:20'],
            'items.*.Panjang' => ['nullable', 'numeric', 'min:0'],
            'items.*.Lebar' => ['nullable', 'numeric', 'min:0'],
            'items.*.Qty' => ['required', 'integer', 'min:1'],
            'items.*.catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'KdCust.required' => 'Pilih customer dulu.',
            'items.required' => 'Isi minimal satu item.',
        ]);

        $proof = DB::transaction(function () use ($data) {
            $proof = OrderProof::create([
                'NoOrder' => $this->orderNumbers->next(self::NUMBER_TYPES[$data['order_type']], now()->toDateString()),
                'order_type' => $data['order_type'],
                'KdCust' => $data['KdCust'],
                'catatan' => $data['catatan'] ?? null,
                'status' => 'layout',
                'created_by' => auth()->id(),
            ]);

            foreach ($data['items'] as $item) {
                $proof->items()->create([
                    'NmFile' => $item['NmFile'] ?? null,
                    'kode' => $item['kode'] ?? null,
                    'jenis_produk' => $data['order_type'] === 'outdoor' ? null : $data['order_type'],
                    'Panjang' => $item['Panjang'] ?? null,
                    'Lebar' => $item['Lebar'] ?? null,
                    'Qty' => (int) $item['Qty'],
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            return $proof;
        }, attempts: 3);

        PageVersion::touch('order-desain');

        return redirect()->route('order-proof.show', $proof)
            ->with('status', "Proof {$proof->NoOrder} dibuat dan sudah masuk antrean Layout (tab Proof).");
    }

    public function show(OrderProof $orderProof): View
    {
        $orderProof->load('customer', 'items', 'createdBy:id,name', 'layoutDoneBy:id,name', 'convertedBy:id,name', 'closedBy:id,name');

        return view('order-proof.show', [
            'proof' => $orderProof,
            'convertedOrder' => $orderProof->convertedOrder(),
            'itemLabels' => OrderProof::itemOptions(),
        ]);
    }

    /** Operator Layout menandai proof selesai dibuat. */
    public function layoutDone(OrderProof $orderProof): RedirectResponse
    {
        $updated = OrderProof::query()->whereKey($orderProof->id)->where('status', 'layout')
            ->update(['status' => 'selesai', 'layout_done_at' => now(), 'layout_done_by' => auth()->id(), 'updated_at' => now()]);
        PageVersion::touch('order-desain');

        return redirect()->route('order-desain.index', ['tab' => 'proof'])->with($updated ? 'status' : 'error', $updated
            ? "Proof {$orderProof->NoOrder} selesai. CS/Operator File bisa membuatkan order dari daftar Ctrl+X."
            : "Proof {$orderProof->NoOrder} sudah tidak di antrean Layout.");
    }

    /** Customer tidak jadi order: proof ditutup dan hilang dari daftar Ctrl+X. */
    public function close(Request $request, OrderProof $orderProof): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['alasan' => ['required', 'string', 'max:255']], ['alasan.required' => 'Alasan menutup proof wajib diisi.']);

        $updated = OrderProof::query()->whereKey($orderProof->id)->whereIn('status', ['layout', 'selesai'])
            ->update(['status' => 'ditutup', 'closed_at' => now(), 'closed_by' => auth()->id(), 'close_reason' => trim($data['alasan']), 'updated_at' => now()]);
        PageVersion::touch('order-desain');

        if ($request->expectsJson()) {
            return $updated ? response()->json(['ok' => true]) : response()->json(['message' => 'Proof sudah tidak bisa ditutup.'], 422);
        }

        return back()->with($updated ? 'status' : 'error', $updated ? "Proof {$orderProof->NoOrder} ditutup." : 'Proof sudah tidak bisa ditutup.');
    }

    /** Daftar Ctrl+X: proof selesai yang menunggu dibuatkan order reguler. */
    public function ready(): JsonResponse
    {
        $labels = OrderProof::itemOptions();

        return response()->json([
            'proofs' => OrderProof::query()->with('customer:id,KdCust,NmCust', 'items', 'layoutDoneBy:id,name')
                ->where('status', 'selesai')->orderBy('layout_done_at')->limit(200)->get()
                ->map(fn (OrderProof $proof) => [
                    'id' => $proof->id,
                    'no_order' => $proof->NoOrder,
                    'type' => $proof->typeLabel(),
                    'customer' => $proof->customer?->NmCust ? ucwords(mb_strtolower($proof->customer->NmCust)) : ($proof->KdCust ?: '-'),
                    'done_label' => $proof->layout_done_at?->format('d M Y H:i').' · '.($proof->layoutDoneBy?->name ?? '-'),
                    'items' => $proof->items->map(fn ($item) => [
                        'file' => $item->NmFile ?: '-',
                        'produk' => $labels[$proof->order_type][$item->kode] ?? ($item->kode ?: '-'),
                        'ukuran' => $item->Panjang || $item->Lebar ? (float) $item->Panjang.' × '.(float) $item->Lebar : 'Belum pasti',
                        'qty' => $item->Qty,
                    ]),
                    'order_url' => route($proof->orderFormType() === 'outdoor' ? 'order-outdoor.create' : 'order-indoor.create', ['proof' => $proof->id]),
                    'show_url' => route('order-proof.show', $proof),
                    'close_url' => route('order-proof.close', $proof),
                ]),
        ]);
    }

    /** @return array<string, mixed> */
    private function formOptions(): array
    {
        $options = OrderProof::itemOptions();

        return ['outdoorOptions' => $options['outdoor'], 'indoorOptions' => $options['indoor'], 'artworkOptions' => $options['artwork']];
    }
}
