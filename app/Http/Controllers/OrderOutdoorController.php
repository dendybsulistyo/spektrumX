<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderOutdoorRequest;
use App\Models\BahanCetakOutdoor;
use App\Models\Customer;
use App\Models\HargaCetakOutdoor;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\OrderStatusNote;
use App\Models\PrinterOutdoor;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OrderOutdoorController extends Controller
{
    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function index(Request $request): View
    {
        $orders = OrderOutdoor::query()
            ->with('customer')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('NoOrder', 'like', "%{$search}%")
                    ->orWhere('KdCust', 'like', "%{$search}%");
            })
            ->orderByDesc('TglOrder')
            ->orderByDesc('NoOrder')
            ->paginate(15)
            ->withQueryString();

        return view('order-outdoor.index', compact('orders'));
    }

    public function create(): View
    {
        return view('order-outdoor.create', [
            'selectedCustomer' => old('KdCust') ? Customer::where('KdCust', old('KdCust'))->first() : null,
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'printerOutdoorList' => PrinterOutdoor::orderBy('NoUrut')->get(),
            'bahanCetakOutdoorList' => BahanCetakOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    public function store(StoreOrderOutdoorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $noOrder = $this->generateNoOrder($data['TglOrder']);

            $order = OrderOutdoor::create([
                'TglOrder' => $data['TglOrder'],
                'NoOrder' => $noOrder,
                'KdCust' => $data['KdCust'],
                'created_by' => auth()->id(),
                'Cetak' => false,
                'status' => 'baru',
                'status_bayar' => 'belum_bayar',
            ]);

            $this->saveItems($order, $data['items']);

            $order->update(['total' => $this->pricingService->totalOutdoor($order->fresh())]);
        });

        return redirect()->route('order-outdoor.index')->with('status', 'Order outdoor berhasil dibuat.');
    }

    public function edit(OrderOutdoor $orderOutdoor): View
    {
        return view('order-outdoor.edit', [
            'order' => $orderOutdoor,
            'items' => $orderOutdoor->items,
            'selectedCustomer' => $orderOutdoor->customer,
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'printerOutdoorList' => PrinterOutdoor::orderBy('NoUrut')->get(),
            'bahanCetakOutdoorList' => BahanCetakOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    public function update(StoreOrderOutdoorRequest $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderOutdoor) {
            $orderOutdoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
            ]);

            $orderOutdoor->items()->delete();

            $this->saveItems($orderOutdoor, $data['items']);

            $orderOutdoor->update(['total' => $this->pricingService->totalOutdoor($orderOutdoor->fresh())]);
        });

        return redirect()->route('order-outdoor.index')->with('status', 'Order outdoor berhasil diperbarui.');
    }

    public function destroy(OrderOutdoor $orderOutdoor): RedirectResponse
    {
        $orderOutdoor->delete();

        return redirect()->route('order-outdoor.index')->with('status', 'Order outdoor berhasil dihapus.');
    }

    /**
     * Operator/staff working the cetak queue flags an order for
     * cancellation. This doesn't cancel it yet — it just freezes the order
     * (no further "Update Status" progress) until Admin/Admin Kasir
     * approves or rejects the request.
     */
    public function requestCancel(Request $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        if ($orderOutdoor->cancel_requested_at) {
            return back()->with('error', 'Order ini sudah punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        $orderOutdoor->update([
            'cancel_requested_at' => now(),
            'cancel_requested_by' => auth()->id(),
            'cancel_reason' => $data['cancel_reason'],
        ]);

        OrderStatusNote::create([
            'order_type' => 'outdoor',
            'order_id' => $orderOutdoor->id,
            'stage' => 'pembatalan',
            'action' => 'diajukan',
            'catatan' => $data['cancel_reason'],
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-cetak.index')->with('status', 'Pengajuan pembatalan order dikirim, menunggu persetujuan Admin/Admin Kasir.');
    }

    public function approveCancel(OrderOutdoor $orderOutdoor): RedirectResponse
    {
        if (! $orderOutdoor->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $orderOutdoor->update([
            'cancel_approved_at' => now(),
            'cancel_approved_by' => auth()->id(),
            'status' => 'batal',
        ]);

        OrderStatusNote::create([
            'order_type' => 'outdoor',
            'order_id' => $orderOutdoor->id,
            'stage' => 'pembatalan',
            'action' => 'disetujui',
            'catatan' => null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-cetak.index')->with('status', 'Pembatalan order disetujui.');
    }

    public function rejectCancel(OrderOutdoor $orderOutdoor): RedirectResponse
    {
        if (! $orderOutdoor->cancel_requested_at) {
            return back()->with('error', 'Order ini tidak punya pengajuan pembatalan yang menunggu persetujuan.');
        }

        $orderOutdoor->update([
            'cancel_requested_at' => null,
            'cancel_requested_by' => null,
            'cancel_reason' => null,
        ]);

        OrderStatusNote::create([
            'order_type' => 'outdoor',
            'order_id' => $orderOutdoor->id,
            'stage' => 'pembatalan',
            'action' => 'ditolak',
            'catatan' => null,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('order-cetak.index')->with('status', 'Pengajuan pembatalan ditolak, order lanjut diproses normal.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderOutdoor $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            // Update deletes and recreates all detail rows on every save, so
            // an item that isn't re-uploading a file needs its previous
            // file_path carried forward via the hidden existing_file_path
            // field — otherwise the reference would be lost even though the
            // physical file on disk is untouched.
            $filePath = $item['existing_file_path'] ?? null;

            if (! empty($item['file']) && $item['file'] instanceof \Illuminate\Http\UploadedFile) {
                if ($filePath) {
                    Storage::disk('public')->delete($filePath);
                }

                $filePath = $item['file']->store('order-outdoor-files', 'public');
            }

            OrderOutdoorDetail::create([
                'order_outdoor_id' => $order->id,
                'BrsOrder' => $brsOrder,
                'NmFile' => $item['NmFile'],
                'file_path' => $filePath,
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'KdCtk' => $item['KdCtk'] ?? null,
                'ada_finishing' => isset($item['ada_finishing']) ? $item['ada_finishing'] === 'ya' : null,
                'jenis_finishing' => $item['jenis_finishing'] ?? null,
            ]);
        }
    }

    private function generateNoOrder(string $tglOrder): string
    {
        $prefix = 'O'.date('ymd', strtotime($tglOrder));

        $last = OrderOutdoor::where('NoOrder', 'like', $prefix.'%')
            ->orderByDesc('NoOrder')
            ->value('NoOrder');

        $nextSeq = $last ? ((int) substr($last, 7, 5)) + 1 : 1;

        return $prefix.str_pad((string) $nextSeq, 5, '0', STR_PAD_LEFT);
    }
}
