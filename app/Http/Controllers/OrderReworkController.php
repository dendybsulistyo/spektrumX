<?php

namespace App\Http\Controllers;

use App\Models\OrderReworkRequest;
use App\Models\OrderStatusNote;
use App\Support\ResolvesOrderType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderReworkController extends Controller
{
    use ResolvesOrderType;

    /**
     * Any operator who can manage at least one production stage can raise a
     * rework/cancel request from wherever the order currently sits — this
     * is a shared cross-stage action, not gated to one specific stage's
     * permission, since the button appears on all six stage pages.
     */
    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(
            auth()->user()->hasPermission('order-desain.manage')
                || auth()->user()->hasPermission('order-cetak.manage')
                || auth()->user()->hasPermission('order-finishing.manage')
                || auth()->user()->hasPermission('order-qc.manage')
                || auth()->user()->hasPermission('order-bungkus.manage')
                || auth()->user()->hasPermission('pengambilan.manage'),
            403
        );

        $order = $this->resolveOrder($type, $id);

        abort_if(
            OrderReworkRequest::forOrder($type, $id)->pending()->exists(),
            422,
            'Order ini sudah punya pengajuan yang menunggu persetujuan.'
        );

        $data = $request->validate([
            'action' => ['required', 'in:ulang,batal'],
            'target_stage' => ['required_if:action,ulang', 'nullable', 'in:desain,cetak,finishing,qc,bungkus'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        OrderReworkRequest::create([
            'order_type' => $type,
            'order_id' => $order->id,
            'current_stage' => $order->status,
            'action' => $data['action'],
            'target_stage' => $data['action'] === 'ulang' ? $data['target_stage'] : null,
            'reason' => $data['reason'],
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan dikirim, menunggu persetujuan.');
    }

    public function approve(OrderReworkRequest $orderReworkRequest): RedirectResponse
    {
        abort_if($orderReworkRequest->status !== 'pending', 422, 'Pengajuan ini sudah diproses.');

        $order = $this->resolveOrder($orderReworkRequest->order_type, $orderReworkRequest->order_id);

        $newStatus = $orderReworkRequest->action === 'batal' ? 'batal' : $orderReworkRequest->target_stage;

        $order->update(['status' => $newStatus]);

        $orderReworkRequest->update([
            'status' => 'approved',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        OrderStatusNote::create([
            'order_type' => $orderReworkRequest->order_type,
            'order_id' => $order->id,
            'stage' => $orderReworkRequest->current_stage,
            'action' => $orderReworkRequest->action === 'batal' ? 'dibatalkan' : 'diulang',
            'catatan' => $orderReworkRequest->action === 'batal'
                ? "Order dibatalkan (alasan: {$orderReworkRequest->reason})"
                : "Order diulang ke tahap ".(OrderReworkRequest::STAGE_LABELS[$orderReworkRequest->target_stage] ?? $orderReworkRequest->target_stage)." (alasan: {$orderReworkRequest->reason})",
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan disetujui.');
    }

    public function reject(OrderReworkRequest $orderReworkRequest): RedirectResponse
    {
        abort_if($orderReworkRequest->status !== 'pending', 422, 'Pengajuan ini sudah diproses.');

        $orderReworkRequest->update([
            'status' => 'rejected',
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return back()->with('status', 'Pengajuan ditolak, order lanjut diproses normal.');
    }
}
