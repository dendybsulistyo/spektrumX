<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderOutdoorRequest;
use App\Models\BahanOutdoor;
use App\Models\Customer;
use App\Models\HargaCetakOutdoor;
use App\Models\OrderOutdoor;
use App\Models\OrderOutdoorDetail;
use App\Models\Operator;
use App\Services\OrderPricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'operators' => Operator::orderBy('NmOpr')->get(),
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'bahanList' => BahanOutdoor::orderBy('NoUrut')->get(),
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
                'KdOpr' => $data['KdOpr'],
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
            'operators' => Operator::orderBy('NmOpr')->get(),
            'hargaCetakList' => HargaCetakOutdoor::orderBy('KdCtk')->get(),
            'bahanList' => BahanOutdoor::orderBy('NoUrut')->get(),
        ]);
    }

    public function update(StoreOrderOutdoorRequest $request, OrderOutdoor $orderOutdoor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $orderOutdoor) {
            $orderOutdoor->update([
                'TglOrder' => $data['TglOrder'],
                'KdCust' => $data['KdCust'],
                'KdOpr' => $data['KdOpr'],
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
     * @param  array<int, array<string, mixed>>  $items
     */
    private function saveItems(OrderOutdoor $order, array $items): void
    {
        foreach ($items as $index => $item) {
            $seq = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $brsOrder = $order->NoOrder.$seq;

            OrderOutdoorDetail::create([
                'order_outdoor_id' => $order->id,
                'BrsOrder' => $brsOrder,
                'NmFile' => $item['NmFile'],
                'Panjang' => $item['Panjang'],
                'Lebar' => $item['Lebar'],
                'Qty' => $item['Qty'],
                'KdCtk' => $item['KdCtk'] ?? null,
                'KdBrgs' => $item['KdBrgs'] ?? null,
                'Fins' => $item['Fins'] ?? null,
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
