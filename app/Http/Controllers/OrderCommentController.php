<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
use App\Models\OrderCommentRead;
use App\Support\PageVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderCommentController extends Controller
{
    /**
     * Any staff who can see the order at all (desain, cetak, finishing,
     * QC/Back Office, bungkus, pengambilan, or the main order list) can
     * post — this is a shared discussion thread, not a stage-gated action
     * like Update Status.
     */
    public function store(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(
            auth()->user()->hasPermission("order-{$type}.view")
                || auth()->user()->hasPermission('order-desain.view')
                || auth()->user()->hasPermission('order-cetak.view')
                || auth()->user()->hasPermission('order-finishing.view')
                || auth()->user()->hasPermission('order-qc.view')
                || auth()->user()->hasPermission('order-bungkus.view')
                || auth()->user()->hasPermission('pengambilan.view'),
            403
        );

        $data = $request->validate([
            'pesan' => ['required', 'string', 'max:1000'],
        ]);

        OrderComment::create([
            'order_type' => $type,
            'order_id' => $id,
            'user_id' => auth()->id(),
            'pesan' => $data['pesan'],
            'created_at' => now(),
        ]);

        // order-desain polls this to auto-refresh when a new reply lands —
        // see OrderDesainController::version().
        PageVersion::touch('order-desain');

        return back()->with('status', 'Pesan terkirim.');
    }

    public function markRead(string $type, int $id): JsonResponse
    {
        OrderCommentRead::updateOrCreate(
            ['user_id' => auth()->id(), 'order_type' => $type, 'order_id' => $id],
            ['last_read_at' => now()],
        );

        return response()->json(['status' => 'ok']);
    }
}
