<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderPickupSignature;
use App\Models\PengaturanKeuangan;
use App\Services\OrderDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderDocumentController extends Controller
{
    private function authorizeAccess(): void
    {
        abort_unless(
            auth()->user()->hasPermission('keuangan.view'),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $search = trim($request->string('search')->toString());
        $documents = OrderDocument::query()->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('number', 'like', "%{$search}%")
                    ->orWhere('snapshot->sales_order', 'like', "%{$search}%")
                    ->orWhere('snapshot->customer', 'like', "%{$search}%");
            });
        })->latest('issued_at')->paginate(30)->withQueryString();

        $hutangOrders = collect();
        foreach (['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class] as $orderType => $model) {
            $model::query()
                ->with('customer')
                ->where('status_bayar', 'hutang')
                ->where('jumlah_piutang', '>', 0)
                ->where('status', '!=', 'batal')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('NoOrder', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($customer) => $customer
                                ->where('NmCust', 'like', "%{$search}%")
                                ->orWhere('KdCust', 'like', "%{$search}%"));
                    });
                })
                ->get()
                ->each(function ($order) use (&$hutangOrders, $orderType) {
                    $order->order_type = $orderType;
                    $hutangOrders->push($order);
                });
        }
        $hutangOrders = $hutangOrders->sortByDesc('TglOrder')->values();

        return view('order-documents.index', compact('documents', 'hutangOrders'));
    }

    public function show(OrderDocument $document)
    {
        $this->authorizeAccess();
        $document->load('issuedBy');
        $model = OrderDocumentService::MODELS[$document->order_type];
        $order = $model::find($document->order_id);
        $void = ! $order || $order->status === 'batal' || $order->invoice_voided_at;
        $signature = null;
        if ($path = $document->snapshot['signature_path'] ?? null) {
            $storedSignature = str_starts_with($path, 'pickup-signatures/')
                ? OrderPickupSignature::query()
                    ->where('order_type', $document->order_type)
                    ->where('order_id', $document->order_id)
                    ->where('signature_path', $path)
                    ->first(['signature_hash'])
                : null;

            if ($storedSignature && Storage::disk('local')->exists($path)) {
                $contents = Storage::disk('local')->get($path);
                if (is_string($contents) && hash_equals($storedSignature->signature_hash, hash('sha256', $contents))) {
                    $signature = 'data:image/svg+xml;base64,'.base64_encode($contents);
                }
            }
        }

        return view('order-documents.show', [
            'document' => $document,
            'void' => $void,
            'signature' => $signature,
            'pengaturan' => PengaturanKeuangan::current(),
        ]);
    }

    public function receipt(OrderDocument $document)
    {
        $this->authorizeAccess();
        abort_unless($document->kind === 'inv', 404);

        $document->load('issuedBy');
        $snapshot = $document->snapshot;
        $receiptNumber = preg_replace('/^INV\./', 'KWT.', $document->number);

        return view('order-documents.receipt', [
            'document' => $document,
            'snapshot' => $snapshot,
            'receiptNumber' => $receiptNumber,
        ]);
    }
}
