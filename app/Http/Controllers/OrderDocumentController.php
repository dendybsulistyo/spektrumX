<?php

namespace App\Http\Controllers;

use App\Models\OrderDocument;
use App\Models\OrderDocumentPrint;
use App\Models\OrderPickupSignature;
use App\Models\PengaturanKeuangan;
use App\Models\SalesOrderPrint;
use App\Services\OrderDocumentService;
use App\Support\CetakUlang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrderDocumentController extends Controller
{
    private function authorizeAccess(): void
    {
        abort_unless(
            auth()->user()->hasPermission('keuangan.view'),
            403
        );
    }

    private function authorizeDocumentAccess(OrderDocument $document): void
    {
        abort_unless(
            auth()->user()->hasPermission('keuangan.view')
                || (auth()->user()->hasPermission('pengambilan.view')
                    && in_array($document->kind, ['do', 'inv'], true)),
            403
        );
    }

    /**
     * Arsip dokumen per transaksi: setiap SO beserta DO & Invoice-nya, untuk
     * dicek dan dicetak ulang (cetak ulang tetap dibatasi CetakUlang).
     * Satu query union terpaginasi untuk daftar SO, lalu dokumen dan
     * status cetak diambil sekali untuk baris di halaman itu saja.
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();
        $filters = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['lunas', 'dp', 'hutang', 'batal'])],
            'dokumen' => ['nullable', Rule::in(['do', 'inv', 'belum'])],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));
        // Pencarian berlaku untuk semua tanggal; tanpa pencarian default bulan berjalan.
        $dari = $filters['dari'] ?? ($search === '' ? now()->startOfMonth()->format('Y-m-d') : null);
        $sampai = $filters['sampai'] ?? ($search === '' ? now()->format('Y-m-d') : null);
        $status = $filters['status'] ?? null;
        $dokumen = $filters['dokumen'] ?? null;

        // Pencarian customer & nomor dokumen diselesaikan dulu menjadi daftar kunci kecil,
        // supaya query order tidak menjalankan subquery per baris.
        $searchCustomers = collect();
        $searchDocuments = collect();
        if ($search !== '') {
            $searchCustomers = DB::table('customers')->where('NmCust', 'like', "%{$search}%")
                ->orWhere('KdCust', $search)->limit(500)->pluck('KdCust');
            $searchDocuments = DB::table('order_documents')->where('number', 'like', "%{$search}%")
                ->limit(2000)->get(['order_type', 'order_id'])->groupBy('order_type')
                ->map(fn ($rows) => $rows->pluck('order_id')->unique()->values());
        }

        $union = null;
        foreach (OrderDocumentService::MODELS as $type => $model) {
            $table = (new $model)->getTable();
            $query = DB::table($table.' as o')
                ->leftJoin('customers as c', 'c.KdCust', '=', 'o.KdCust')
                ->select([
                    'o.id', 'o.NoOrder', 'o.TglOrder', 'o.KdCust', 'o.status', 'o.status_bayar',
                    'o.total', 'o.jumlah_piutang', 'c.NmCust', DB::raw("'{$type}' as order_type"),
                ])
                ->when($dari, fn ($q) => $q->where('o.TglOrder', '>=', $dari))
                ->when($sampai, fn ($q) => $q->where('o.TglOrder', '<=', $sampai.' 23:59:59'))
                ->when($status === 'batal', fn ($q) => $q->where('o.status', 'batal'))
                ->when($status && $status !== 'batal', fn ($q) => $q->where('o.status_bayar', $status)->where('o.status', '!=', 'batal'))
                ->when($dokumen === 'belum', fn ($q) => $q->whereNotExists(fn ($d) => $d->from('order_documents')
                    ->whereColumn('order_documents.order_id', 'o.id')->where('order_documents.order_type', $type)))
                ->when(in_array($dokumen, ['do', 'inv'], true), fn ($q) => $q->whereExists(fn ($d) => $d->from('order_documents')
                    ->whereColumn('order_documents.order_id', 'o.id')->where('order_documents.order_type', $type)
                    ->where('order_documents.kind', $dokumen)))
                ->when($search !== '', function ($q) use ($search, $type, $searchCustomers, $searchDocuments) {
                    $q->where(function ($q) use ($search, $type, $searchCustomers, $searchDocuments) {
                        $q->where('o.NoOrder', 'like', "%{$search}%")
                            ->when($searchCustomers->isNotEmpty(), fn ($q) => $q->orWhereIn('o.KdCust', $searchCustomers))
                            ->when(isset($searchDocuments[$type]), fn ($q) => $q->orWhereIn('o.id', $searchDocuments[$type]));
                    });
                });
            $union = $union ? $union->unionAll($query) : $query;
        }

        $orders = DB::query()->fromSub($union, 'orders')
            ->orderByDesc('TglOrder')->orderByDesc('NoOrder')
            ->paginate(50)->withQueryString();

        $documents = collect();
        $soPrints = collect();
        $pageOrders = collect($orders->items())->groupBy('order_type');
        if ($pageOrders->isNotEmpty()) {
            $byType = function ($query) use ($pageOrders) {
                $query->where(function ($query) use ($pageOrders) {
                    foreach ($pageOrders as $type => $rows) {
                        $query->orWhere(fn ($q) => $q->where('order_type', $type)->whereIn('order_id', $rows->pluck('id')));
                    }
                });
            };
            $documents = DB::table('order_documents')->where($byType)
                ->orderBy('kind')->orderBy('sequence')
                ->get(['id', 'kind', 'order_type', 'order_id', 'number']);
            $docPrints = OrderDocumentPrint::query()->whereIn('order_document_id', $documents->pluck('id'))
                ->pluck('print_count', 'order_document_id');
            $documents = $documents->map(function ($document) use ($docPrints) {
                $document->print_count = (int) ($docPrints[$document->id] ?? 0);

                return $document;
            })->groupBy(fn ($document) => $document->order_type.'-'.$document->order_id);
            $soPrints = SalesOrderPrint::query()->where($byType)
                ->get(['order_type', 'order_id', 'print_count'])
                ->mapWithKeys(fn ($print) => [$print->order_type.'-'.$print->order_id => (int) $print->print_count]);
        }

        return view('order-documents.index', [
            'orders' => $orders, 'documents' => $documents, 'soPrints' => $soPrints,
            'dari' => $dari, 'sampai' => $sampai, 'search' => $search,
            'status' => $status, 'dokumen' => $dokumen,
            'canReprint' => CetakUlang::boleh(auth()->user()),
        ]);
    }

    public function show(OrderDocument $document)
    {
        $this->authorizeDocumentAccess($document);
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
            'printRecord' => OrderDocumentPrint::query()->where('order_document_id', $document->id)->first(),
            'canReprint' => CetakUlang::boleh(auth()->user()),
            'void' => $void,
            'signature' => $signature,
            'pengaturan' => PengaturanKeuangan::current(),
        ]);
    }

    public function receipt(OrderDocument $document)
    {
        $this->authorizeAccess();
        abort_unless($document->kind === 'inv', 404);
        // Kwitansi = bukti terima uang: hanya untuk invoice yang sudah lunas.
        abort_if((float) ($document->snapshot['balance'] ?? 0) > 0, 422, 'Invoice ini belum lunas (tagihan VIP). Kwitansi tersedia setelah pelunasan.');

        $document->load('issuedBy');
        $snapshot = $document->snapshot;
        $receiptNumber = preg_replace('/^INV\./', 'KWT.', $document->number);

        return view('order-documents.receipt', [
            'document' => $document,
            'snapshot' => $snapshot,
            'receiptNumber' => $receiptNumber,
        ]);
    }

    /**
     * Catat cetak DO / Invoice. Cetak pertama: siapa pun yang boleh membuka
     * dokumen (termasuk Invoice setelah pembayaran di Pengambilan); cetak
     * ulang hanya App\Support\CetakUlang::ROLES.
     */
    public function registerPrint(OrderDocument $document): JsonResponse
    {
        abort_unless(
            auth()->user()->hasPermission('kasir.view')
                || auth()->user()->hasPermission('pengambilan.view')
                || auth()->user()->hasPermission('customer-service.view')
                || auth()->user()->hasPermission('keuangan.view'),
            403
        );

        $result = DB::transaction(function () use ($document): array {
            // Kunci dokumen induk: baris catatan yang belum ada tidak bisa dikunci,
            // jadi ini mencegah dua tab mencatat "cetak pertama" bersamaan.
            OrderDocument::query()->lockForUpdate()->findOrFail($document->id);
            $record = OrderDocumentPrint::query()->where('order_document_id', $document->id)->lockForUpdate()->first();

            if ($record && ! CetakUlang::boleh(auth()->user())) {
                return ['allowed' => false, 'record' => $record];
            }

            if (! $record) {
                $record = OrderDocumentPrint::create([
                    'order_document_id' => $document->id,
                    'print_count' => 1,
                    'first_printed_by' => auth()->id(),
                    'first_printed_at' => now(),
                    'last_printed_by' => auth()->id(),
                    'last_printed_at' => now(),
                ]);
            } else {
                $record->update([
                    'print_count' => $record->print_count + 1,
                    'last_printed_by' => auth()->id(),
                    'last_printed_at' => now(),
                ]);
            }

            return ['allowed' => true, 'record' => $record];
        });

        $jenis = $document->kind === 'inv' ? 'Invoice' : 'Delivery Order';

        if (! $result['allowed']) {
            return response()->json([
                'message' => "{$jenis} sudah pernah dicetak. Cetak ulang hanya dapat dilakukan oleh ".CetakUlang::LABEL.'.',
            ], 409);
        }

        return response()->json([
            'message' => $result['record']->print_count > 1 ? 'Cetak ulang diizinkan.' : 'Cetak pertama dicatat.',
            'print_count' => $result['record']->print_count,
        ]);
    }
}
