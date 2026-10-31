<?php

namespace App\Http\Controllers;

use App\Models\OrderDocument;
use App\Models\ReprintLog;
use App\Models\ReprintRequest;
use App\Services\OrderDocumentService;
use App\Services\ReprintGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReprintApprovalController extends Controller
{
    /** Permintaan cetak ulang dari petugas yang sudah lewat batas waktu cetak ulang bebas. */
    public function store(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasPermission('kasir.view')
                || $request->user()->hasPermission('pengambilan.view')
                || $request->user()->hasPermission('customer-service.view')
                || $request->user()->hasPermission('keuangan.view'),
            403
        );
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ReprintGate::KIND_LABELS))],
            'order_type' => ['required', Rule::in(array_keys(OrderDocumentService::MODELS))],
            'order_id' => ['required', 'integer'],
            'order_document_id' => ['nullable', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $gate = app(ReprintGate::class);
        if ($open = $gate->openRequest($data['kind'], $data['order_type'], (int) $data['order_id'], $data['order_document_id'] ?? null)) {
            return response()->json(['message' => $open->status === 'approved'
                ? 'Permintaan sudah disetujui. Silakan klik Cetak Ulang lagi.'
                : 'Permintaan cetak ulang sudah dikirim sebelumnya dan masih menunggu approval.']);
        }

        ReprintRequest::create($data + ['requested_by' => $request->user()->id]);

        return response()->json(['message' => 'Permintaan cetak ulang terkirim. Tunggu persetujuan Admin Kasir, lalu klik Cetak Ulang lagi.']);
    }

    public function index(): View
    {
        abort_unless(ReprintGate::canApprove(auth()->user()), 403);

        $pending = ReprintRequest::query()->with('requestedBy')->where('status', 'pending')->oldest('id')->get();
        $pending->each(fn (ReprintRequest $row) => $row->document_number = $this->documentNumber($row));
        $logs = ReprintLog::query()->with('user')->latest('id')->limit(200)->get();

        return view('reprint-approval.index', compact('pending', 'logs'));
    }

    public function decide(Request $request, ReprintRequest $reprintRequest): RedirectResponse
    {
        abort_unless(ReprintGate::canApprove($request->user()), 403);
        $approve = $request->input('keputusan') === 'setujui';
        abort_unless($reprintRequest->status === 'pending', 422, 'Permintaan ini sudah diputuskan.');

        $reprintRequest->update([
            'status' => $approve ? 'approved' : 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        return back()->with('status', $approve ? 'Cetak ulang disetujui (berlaku satu kali cetak).' : 'Permintaan cetak ulang ditolak.');
    }

    private function documentNumber(ReprintRequest $row): string
    {
        if ($row->order_document_id) {
            return (string) OrderDocument::query()->whereKey($row->order_document_id)->value('number');
        }
        $order = OrderDocumentService::MODELS[$row->order_type]::query()->find($row->order_id);

        return $order ? app(OrderDocumentService::class)->number($order, 'so') : '-';
    }
}
