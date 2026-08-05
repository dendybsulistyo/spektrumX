<?php

namespace App\Http\Controllers;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\OrderReworkRequest;
use Illuminate\View\View;

class PembatalanController extends Controller
{
    /**
     * One place to see every pending cancellation request across all 3
     * order types — previously these only surfaced embedded in Antrian
     * Desain, easy to miss unless an approver happened to be on that page.
     * Reuses the existing per-type approve/reject routes,
     * this is purely a consolidated view.
     *
     * Merges two kinds of cancellation ("kind" on each row):
     * - "cancel": full-order cancellation with nota pengganti (topup/cashback
     *   handled at payment time by KasirController).
     * - "rework_batal": a "Batal / Ulang" request (any production stage)
     *   whose action is "batal" — approving it refunds whatever the
     *   customer already paid (see OrderReworkController::approve()).
     *   "Ulang Proses" requests stay inline on their stage page; they have
     *   no financial impact so don't belong on this approval page.
     */
    public function index(): View
    {
        abort_unless(
            auth()->user()->hasPermission('order-indoor.approve-cancel')
                || auth()->user()->hasPermission('order-outdoor.approve-cancel')
                || auth()->user()->hasPermission('order-artwork.approve-cancel'),
            403
        );

        $rows = collect();

        $models = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

        foreach ($models as $orderType => $model) {
            $model::query()
                ->with(['customer', 'cancelRequestedBy'])
                ->whereNotNull('cancel_requested_at')
                ->whereNull('cancel_approved_at')
                ->orderBy('cancel_requested_at')
                ->get()
                ->each(function ($order) use (&$rows, $orderType) {
                    $order->order_type = $orderType;
                    $order->kind = 'cancel';
                    $rows->push($order);
                });
        }

        OrderReworkRequest::query()
            ->where('action', 'batal')
            ->pending()
            ->with('requestedBy')
            ->orderBy('requested_at')
            ->get()
            ->each(function (OrderReworkRequest $req) use (&$rows, $models) {
                $model = $models[$req->order_type] ?? null;
                $order = $model ? $model::with('customer')->find($req->order_id) : null;

                if (! $order) {
                    return;
                }

                $row = new \stdClass;
                $row->kind = 'rework_batal';
                $row->id = $req->id;
                $row->order_type = $req->order_type;
                $row->NoOrder = $order->NoOrder;
                $row->customer = $order->customer;
                $row->cancel_reason = $req->reason;
                $row->cancelRequestedBy = $req->requestedBy;
                $row->cancel_requested_at = $req->requested_at;

                $rows->push($row);
            });

        $rows = $rows->sortBy('cancel_requested_at')->values();

        return view('pembatalan.index', ['rows' => $rows]);
    }

    /**
     * Lightweight count (no relations loaded) for the nav badge — same two
     * kinds counted in index(), just tallied instead of hydrated.
     */
    public static function pendingCount(): int
    {
        $count = 0;

        foreach ([OrderIndoor::class, OrderOutdoor::class, OrderArtwork::class] as $model) {
            $count += $model::query()
                ->whereNotNull('cancel_requested_at')
                ->whereNull('cancel_approved_at')
                ->count();
        }

        $count += OrderReworkRequest::query()->where('action', 'batal')->pending()->count();

        return $count;
    }
}
