<?php

namespace App\Http\Controllers;

use App\Models\OrderDocument;
use App\Models\PengaturanKeuangan;
use App\Services\OrderPricingService;
use App\Support\ResolvesOrderType;
use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Proforma Invoice tagihan VIP (per order) untuk menagih customer sebelum lunas.
 * Hanya tampilan: tidak menerbitkan nomor dokumen, tidak menjurnal, tidak mengubah plafon.
 */
class ProformaInvoiceController extends Controller
{
    use ResolvesOrderType;

    public function __construct(private readonly OrderPricingService $pricingService) {}

    public function show(string $type, int $id): View
    {
        $order = $this->resolveOrder($type, $id);
        $order->load('customer.limit');
        abort_unless(self::isAvailableFor($order), 404, 'Proforma hanya untuk tagihan customer VIP yang belum lunas.');

        $rawItems = match ($type) {
            'indoor' => $order->detailItems(),
            'outdoor' => $order->items()->with('hargaCetak')->get(),
            'artwork' => $order->items,
        };
        $items = $this->pricingService->detailedLineItems($type, $order, $rawItems);

        // Sama dengan invoice tagihan VIP: total = sudah dibayar + sisa piutang.
        $paid = max(0, (float) $order->jumlah_dibayar);
        $balance = max(0, (float) $order->jumlah_piutang);
        $total = Rupiah::bulatkan($paid + $balance);
        $gross = (float) $items->sum('subtotal');

        $invoice = OrderDocument::query()->where('kind', 'inv')->where('order_type', $type)
            ->where('order_id', $order->id)->orderBy('sequence')->first(['number', 'issued_at']);
        $archived = DB::table('sales_transaction_archives')->where('order_type', $type)->where('order_id', $order->id)->exists();

        return view('proforma-invoice.show', [
            'type' => $type,
            'order' => $order,
            'items' => $items,
            'gross' => $gross,
            'discount' => max(0, $gross - $total),
            'total' => $total,
            'paid' => $paid,
            'balance' => $balance,
            'invoiceNumber' => $invoice?->number,
            // Tanggal tetap (keputusan user 9 Nov 2026): sudah disimpan = tanggal invoice/simpan,
            // belum disimpan = tanggal order. Bukan hari ini, supaya tidak berubah tiap dibuka.
            'documentDate' => $archived && $invoice?->issued_at ? $invoice->issued_at : ($order->TglOrder ?? $order->created_at),
            'pengaturan' => PengaturanKeuangan::current(),
        ]);
    }

    /** Order hutang VIP yang masih punya sisa tagihan. */
    public static function isAvailableFor(Model $order): bool
    {
        return $order->status_bayar === 'hutang'
            && (float) $order->jumlah_piutang > 0
            && $order->status !== 'batal'
            && ! $order->invoice_voided_at
            && (bool) $order->customer?->isVip;
    }
}
