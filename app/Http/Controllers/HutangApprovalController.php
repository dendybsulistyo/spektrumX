<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\View\View;

class HutangApprovalController extends Controller
{
    private const MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    /**
     * One consolidated view of every VIP hutang pending Admin/Admin Kasir
     * sign-off across all 3 order types — mirrors DiskonApprovalController.
     * Reuses the existing per-order approve/reject routes on KasirController
     * (kasir.hutang.approve/reject), this is purely a listing.
     */
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('kasir.approve-hutang'), 403);

        $rows = collect();

        foreach (self::MODELS as $orderType => $model) {
            $model::query()
                ->with(['customer.limit', 'hutangRequestedBy'])
                ->whereNotNull('hutang_requested_at')
                ->whereNull('hutang_approved_at')
                ->whereNull('hutang_rejected_at')
                ->orderBy('hutang_requested_at')
                ->get()
                ->each(function ($order) use (&$rows, $orderType) {
                    $order->order_type = $orderType;
                    $rows->push($order);
                });
        }

        $rows = $rows->sortBy('hutang_requested_at')->values();

        return view('hutang-approval.index', ['rows' => $rows]);
    }

    /**
     * Lightweight count for the nav badge.
     */
    public static function pendingCount(): int
    {
        $count = 0;

        foreach (self::MODELS as $model) {
            $count += $model::query()
                ->whereNotNull('hutang_requested_at')
                ->whereNull('hutang_approved_at')
                ->whereNull('hutang_rejected_at')
                ->count();
        }

        return $count;
    }
}
