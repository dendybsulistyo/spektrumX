<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPayment;
use App\Services\OrderPricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Rekap Transaksi Bulanan (menu Analitik). Aturan hitung sama dengan Rekap
 * Transaksi Harian (nota Lunas + Tagihan VIP), tetapi Total/Tunai/Kredit
 * ditulis sekali per nota. Order diproses per 200 agar memori tetap kecil.
 */
class MonthlyTransactionController extends Controller
{
    private const MODELS = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

    public function __construct(private readonly OrderPricingService $pricing) {}

    public function index(Request $request): View
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('bulan'))
            ? CarbonImmutable::createFromFormat('Y-m-d', $request->query('bulan').'-01')
            : CarbonImmutable::now()->startOfMonth();
        $from = $month->startOfMonth()->toDateString();
        $to = $month->endOfMonth()->toDateString();

        $notes = collect();
        foreach (self::MODELS as $type => $model) {
            $relations = $type === 'outdoor' ? ['customer:id,KdCust,NmCust', 'customer.limit', 'items.hargaCetak'] : ['customer:id,KdCust,NmCust', 'customer.limit', 'items'];
            $model::query()
                ->with($relations)
                ->whereBetween('TglOrder', [$from, $to])
                ->where('status', '!=', 'batal')
                ->where(fn ($query) => $query->where('status_bayar', 'lunas')
                    ->orWhere(fn ($vipDebt) => $vipDebt->where('status_bayar', 'hutang')->whereHas('customer.limit')))
                ->chunkById(200, function (Collection $orders) use ($type, $notes) {
                    $this->collectNotes($type, $orders, $notes);
                });
        }

        $notes = $notes->sortBy(fn ($note) => $note->date.'|'.$note->number)->values();

        return view('reports.monthly-transactions', [
            'month' => $month,
            'notes' => $notes,
            'totals' => (object) [
                'subtotal' => (float) $notes->sum('subtotal'),
                'discount' => (float) $notes->sum('discount'),
                'total' => (float) $notes->sum('total'),
                'cash' => (float) $notes->sum('cash'),
                'credit' => (float) $notes->sum('credit'),
            ],
        ]);
    }

    private function collectNotes(string $type, Collection $orders, Collection $notes): void
    {
        $ids = $orders->pluck('id');
        $invoices = DB::table('order_documents')->where('order_type', $type)->where('kind', 'inv')
            ->whereIn('order_id', $ids)->orderBy('sequence')->get(['order_id', 'number'])
            ->groupBy('order_id');
        $payments = OrderPayment::query()->where('order_type', $type)->whereIn('order_id', $ids)
            ->selectRaw('order_id, SUM(jumlah) AS total')->groupBy('order_id')->pluck('total', 'order_id');

        foreach ($orders as $order) {
            $items = $this->pricing->detailedLineItems($type, $order, $order->items);
            $gross = (float) $items->sum('subtotal');
            $net = $order->diskonStatus() === 'approved' ? $order->totalSetelahDiskon() : (float) $order->total;
            $recorded = (float) ($payments[$order->id] ?? 0);
            $paid = $order->status_bayar === 'lunas' && $recorded <= 0 ? $net : max(0, min($net, $recorded));

            $notes->push((object) [
                'date' => $order->TglOrder,
                'number' => $order->NoOrder,
                'invoices' => ($invoices[$order->id] ?? collect())->pluck('number')->values()->whenEmpty(fn () => collect([$order->NoOrder])),
                'customer' => $order->customer?->NmCust ?? '-',
                'lines' => $items->map(fn ($item) => (object) [
                    'product' => collect([$item->printer, $item->bahan])->filter()->implode(' / ') ?: '-',
                    'description' => collect([$item->name, $item->breakdown])->filter()->implode(' — '),
                    'length' => $item->panjang, 'width' => $item->lebar, 'qty' => $item->qty,
                    'price' => $item->harga_satuan, 'subtotal' => (float) $item->subtotal,
                ])->values(),
                'subtotal' => $gross,
                'discount' => max(0, $gross - $net),
                'total' => (float) $net,
                'cash' => (float) $paid,
                'credit' => max(0, (float) $net - $paid),
            ]);
        }
    }
}
