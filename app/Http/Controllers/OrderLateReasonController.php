<?php

namespace App\Http\Controllers;

use App\Models\OrderLateReason;
use App\Services\OrderDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Alasan order telat: ditambah oleh pemegang izin history-telat.alasan, tidak bisa diubah. */
class OrderLateReasonController extends Controller
{
    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $model = OrderDocumentService::MODELS[$type] ?? abort(404);
        $model::query()->findOrFail($id);

        $data = $request->validate([
            'alasan' => ['required', 'string', 'max:1000'],
        ], [
            'alasan.required' => 'Alasan wajib diisi.',
        ]);

        OrderLateReason::create([
            'order_type' => $type,
            'order_id' => $id,
            'alasan' => trim($data['alasan']),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'late_reasons' => OrderLateReason::forOrder($type, $id)->with('user:id,name')->latest()->orderByDesc('id')->get()
                ->map(fn (OrderLateReason $reason) => $reason->toDisplay()),
        ]);
    }
}
