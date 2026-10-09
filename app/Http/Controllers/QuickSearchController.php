<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Pencarian cepat (Ctrl+K): order (No. Order / SO / Invoice) & customer.
 * Nomor dicari dari depan agar memakai indeks; hasil dibatasi.
 */
class QuickSearchController extends Controller
{
    private const MODELS = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

    /** Awalan No. Order per tipe, juga dipakai menerjemahkan SO.<divisi>.<nomor>. */
    private const DIVISION_TYPES = ['1' => 'outdoor', '2' => 'indoor', '3' => 'artwork'];

    private const ORDER_PREFIX = ['outdoor' => 'OUT.1.', 'indoor' => 'IND.2.', 'artwork' => 'ART.3.'];

    private const LIMIT = 6;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 3 || mb_strlen($q) > 60) {
            return response()->json(['orders' => [], 'customers' => []]);
        }

        return response()->json([
            'orders' => $this->orders($request, $q),
            'customers' => $this->customers($request, $q),
        ]);
    }

    private function orders(Request $request, string $q): Collection
    {
        $upper = mb_strtoupper(str_replace(' ', '', $q));
        if (! preg_match('/^[A-Z0-9.]+$/', $upper) || ! preg_match('/\d/', $upper)) {
            return collect();
        }

        /** @var array<string, array<int, string>> $prefixes per tipe → daftar awalan No. Order */
        $prefixes = [];
        $byId = [];

        if (preg_match('/^SO\.?([123])\.?(.*)$/', $upper, $m)) {
            $type = self::DIVISION_TYPES[$m[1]];
            $prefixes[$type][] = self::ORDER_PREFIX[$type].$m[2];
            $prefixes[$type][] = $m[2] === '' ? null : $m[2];
        } elseif (str_starts_with($upper, 'INV')) {
            DB::table('order_documents')->where('kind', 'inv')->where('number', 'like', $upper.'%')
                ->orderByDesc('id')->limit(self::LIMIT)->get(['order_type', 'order_id'])
                ->each(function ($doc) use (&$byId) { $byId[$doc->order_type][] = $doc->order_id; });
        } elseif (preg_match('/^\d/', $upper)) {
            // Nomor polos: cocokkan nomor lama dan nomor baru berawalan tipe.
            foreach (self::ORDER_PREFIX as $type => $prefix) {
                $prefixes[$type][] = $upper;
                $prefixes[$type][] = $prefix.$upper;
            }
        } else {
            foreach (self::MODELS as $type => $model) {
                $prefixes[$type][] = $upper;
            }
        }

        $results = collect();
        foreach (self::MODELS as $type => $model) {
            $typePrefixes = array_filter($prefixes[$type] ?? []);
            $ids = $byId[$type] ?? [];
            if (! $typePrefixes && ! $ids) {
                continue;
            }
            $model::query()->with('customer:id,KdCust,NmCust')
                ->where(function ($query) use ($typePrefixes, $ids) {
                    foreach ($typePrefixes as $prefix) {
                        $query->orWhere('NoOrder', 'like', $prefix.'%');
                    }
                    if ($ids) {
                        $query->orWhereIn('id', $ids);
                    }
                })
                ->orderByDesc('id')->limit(self::LIMIT)
                ->get(['id', 'NoOrder', 'KdCust', 'TglOrder', 'status', 'status_bayar'])
                ->each(fn (Model $order) => $results->push($this->orderRow($request, $type, $order)));
        }

        $invoices = $results->isEmpty() ? collect() : DB::table('order_documents')->where('kind', 'inv')
            ->where(function ($query) use ($results) {
                foreach ($results->groupBy('type') as $type => $rows) {
                    $query->orWhere(fn ($q) => $q->where('order_type', $type)->whereIn('order_id', $rows->pluck('id')));
                }
            })->orderBy('sequence')->get(['order_type', 'order_id', 'number'])
            ->keyBy(fn ($doc) => $doc->order_type.'-'.$doc->order_id);

        return $results->map(function (array $row) use ($invoices) {
            $row['invoice'] = $invoices->get($row['type'].'-'.$row['id'])?->number;

            return $row;
        })->sortByDesc('date')->take(self::LIMIT)->values();
    }

    private function orderRow(Request $request, string $type, Model $order): array
    {
        $user = $request->user();
        $editRoute = "order-{$type}.edit";
        $canEdit = $user->hasPermission("order-{$type}.view") && Route::has($editRoute);
        $canNota = $user->hasPermission('kasir.view') || $user->hasPermission('pengambilan.view')
            || $user->hasPermission('customer-service.view') || $user->hasPermission('keuangan.view');

        return [
            'type' => $type,
            'id' => $order->id,
            'no_order' => $order->NoOrder,
            'customer' => $order->customer?->NmCust ? ucwords(mb_strtolower($order->customer->NmCust)) : '-',
            'date' => optional($order->TglOrder)->format('Y-m-d'),
            'date_label' => optional($order->TglOrder)->locale('id')->translatedFormat('d M Y'),
            'status' => $order->status,
            'status_bayar' => $order->status_bayar,
            'edit_url' => $canEdit ? route($editRoute, $order->id) : null,
            'nota_url' => $canNota ? route('invoice.show', ['type' => $type, 'id' => $order->id]) : null,
            'progress_url' => url("/dashboard/order-progress/{$type}/{$order->id}"),
        ];
    }

    private function customers(Request $request, string $q): Collection
    {
        $canView = $request->user()->hasPermission('customers.view');

        return Customer::query()
            ->where(fn ($query) => $query->where('NmCust', 'like', '%'.$q.'%')->orWhere('KdCust', 'like', $q.'%'))
            ->orderByRaw('NmCust LIKE ? DESC', [$q.'%'])->orderBy('NmCust')
            ->limit(self::LIMIT)->get(['id', 'KdCust', 'NmCust', 'Telp'])
            ->map(fn (Customer $customer) => [
                'code' => $customer->KdCust,
                'name' => $customer->NmCust,
                'phone' => $customer->Telp,
                'url' => $canView ? route('customers.show', $customer->KdCust) : null,
            ]);
    }
}
