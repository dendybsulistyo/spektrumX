<?php

namespace App\Http\Controllers;

use App\Models\OrderDocument;
use App\Models\PengaturanKeuangan;
use App\Services\OrderDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderDocumentController extends Controller
{
    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()->hasPermission('kasir.view') || auth()->user()->hasPermission('pengambilan.view') || auth()->user()->hasPermission('keuangan.view'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $documents = OrderDocument::query()->when($request->filled('search'), function ($query) use ($request) {
            $query->where('number', 'like', '%'.$request->string('search').'%');
        })->latest('issued_at')->paginate(30)->withQueryString();

        return view('order-documents.index', compact('documents'));
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
            if (Storage::disk('local')->exists($path)) {
                $signature = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get($path));
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
