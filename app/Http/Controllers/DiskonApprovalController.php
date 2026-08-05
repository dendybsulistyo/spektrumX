<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\View\View;

class DiskonApprovalController extends Controller
{
    private const MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    /**
     * One place to see every pending discount request across all 3 order
     * types — previously these only surfaced as a small badge on each order
     * row in Kasir, easy to miss unless an approver happened to scroll past
     * that specific order. Reuses the existing per-order approve/reject
     * routes (kasir.diskon.approve/reject), this is purely a consolidated
     * view.
     */
    public function index(): View
    {
        abort_unless(auth()->user()->hasPermission('kasir.approve-diskon'), 403);

        $rows = collect();

        foreach (self::MODELS as $orderType => $model) {
            $model::query()
                ->with(['customer', 'diskonRequestedBy'])
                ->whereNotNull('diskon_requested_at')
                ->whereNull('diskon_approved_at')
                ->whereNull('diskon_rejected_at')
                ->orderBy('diskon_requested_at')
                ->get()
                ->each(function ($order) use (&$rows, $orderType) {
                    $order->order_type = $orderType;
                    $rows->push($order);
                });
        }

        $rows = $rows->sortBy('diskon_requested_at')->values();

        return view('diskon-approval.index', ['rows' => $rows]);
    }

    /**
     * Lightweight count for the nav badge.
     */
    public static function pendingCount(): int
    {
        $count = 0;

        foreach (self::MODELS as $model) {
            $count += $model::query()
                ->whereNotNull('diskon_requested_at')
                ->whereNull('diskon_approved_at')
                ->whereNull('diskon_rejected_at')
                ->count();
        }

        return $count;
    }
}
