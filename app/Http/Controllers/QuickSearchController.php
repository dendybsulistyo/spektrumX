<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderLateLog;
use App\Models\OrderOutdoor;
use App\Models\OrderProof;
use App\Services\OrderLateLogService;
use App\Support\HariKerja;
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

        $customers = $this->customers($request, $q);

        return response()->json([
            'orders' => $this->orders($request, $q, $customers->pluck('code')->all()),
            'customers' => $customers,
        ]);
    }

    /**
     * @param  array<int, string>  $customerCodes  customer yang cocok; dipakai bila yang diketik berupa nama
     */
    private function orders(Request $request, string $q, array $customerCodes = []): Collection
    {
        $upper = mb_strtoupper(str_replace(' ', '', $q));
        $isNumberQuery = preg_match('/^[A-Z0-9.]+$/', $upper) && preg_match('/\d/', $upper);
        if (! $isNumberQuery && ! $customerCodes) {
            return collect();
        }

        /** @var array<string, array<int, string>> $prefixes per tipe → daftar awalan No. Order */
        $prefixes = [];
        $byId = [];
        $byCustomer = [];

        if (! $isNumberQuery) {
            // Nama customer: tampilkan order terbaru milik customer yang cocok.
            $byCustomer = $customerCodes;
        } elseif (preg_match('/^SO\.?([123])\.?(.*)$/', $upper, $m)) {
            $type = self::DIVISION_TYPES[$m[1]];
            $prefixes[$type][] = self::ORDER_PREFIX[$type].$m[2];
            $prefixes[$type][] = $m[2] === '' ? null : $m[2];
        } elseif (str_starts_with($upper, 'INV')) {
            DB::table('order_documents')->where('kind', 'inv')->where('number', 'like', $upper.'%')
                ->orderByDesc('id')->limit(self::LIMIT)->get(['order_type', 'order_id'])
                ->each(function ($doc) use (&$byId) {
                    $byId[$doc->order_type][] = $doc->order_id;
                });
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
            if (! $typePrefixes && ! $ids && ! $byCustomer) {
                continue;
            }
            $model::query()->with('customer:id,KdCust,NmCust', 'customer.limit')
                ->where(function ($query) use ($typePrefixes, $ids, $byCustomer) {
                    foreach ($typePrefixes as $prefix) {
                        $query->orWhere('NoOrder', 'like', $prefix.'%');
                    }
                    if ($ids) {
                        $query->orWhereIn('id', $ids);
                    }
                    if ($byCustomer) {
                        $query->orWhereIn('KdCust', $byCustomer);
                    }
                })
                ->orderByDesc('id')->limit(self::LIMIT)
                ->get(['id', 'NoOrder', 'KdCust', 'TglOrder', 'status', 'status_bayar', 'jumlah_piutang', 'invoice_voided_at', 'created_at', 'diproses_kasir_at'])
                ->each(fn (Model $order) => $results->push($this->orderRow($request, $type, $order)));
        }

        $invoices = $results->isEmpty() ? collect() : DB::table('order_documents')->where('kind', 'inv')
            ->where(function ($query) use ($results) {
                foreach ($results->groupBy('type') as $type => $rows) {
                    $query->orWhere(fn ($q) => $q->where('order_type', $type)->whereIn('order_id', $rows->pluck('id')));
                }
            })->orderBy('sequence')->get(['order_type', 'order_id', 'number'])
            ->keyBy(fn ($doc) => $doc->order_type.'-'.$doc->order_id);

        $lateLogs = app(OrderLateLogService::class)->logsFor($results);
        // Order yang dibuat dari proof: nomor proof asal ikut tampil.
        $sourceProofs = $results->isEmpty() ? collect() : OrderProof::query()
            ->where(function ($query) use ($results) {
                foreach ($results->groupBy('type') as $type => $rows) {
                    $query->orWhere(fn ($q) => $q->where('converted_order_type', $type)->whereIn('converted_order_id', $rows->pluck('id')));
                }
            })->get(['NoOrder', 'converted_order_type', 'converted_order_id'])
            ->keyBy(fn ($proof) => $proof->converted_order_type.'-'.$proof->converted_order_id);

        return $results->map(function (array $row) use ($invoices, $lateLogs, $sourceProofs) {
            $row['invoice'] = $invoices->get($row['type'].'-'.$row['id'])?->number;
            $row['proof'] = $sourceProofs->get($row['type'].'-'.$row['id'])?->NoOrder;
            $row['late'] = $this->lateInfo($lateLogs->get($row['type'].'-'.$row['id']), $row);
            unset($row['estimate_end']);

            return $row;
        })->sortByDesc('date')->take(self::LIMIT)->values()
            ->concat($this->proofs($request, $upper, $isNumberQuery, $byCustomer));
    }

    /**
     * Order proof (PRF.x.yymmdd###) atau proof milik customer yang cocok; dibuka ke halaman detail proof.
     *
     * @param  array<int, string>  $byCustomer
     * @return Collection<int, array<string, mixed>>
     */
    private function proofs(Request $request, string $upper, bool $isNumberQuery, array $byCustomer): Collection
    {
        $canOpen = $request->user()->hasPermission('order-proof.manage') || $request->user()->hasPermission('order-desain.view');
        $byNumber = $isNumberQuery && str_starts_with($upper, 'PRF');
        if (! $canOpen || (! $byNumber && ! $byCustomer)) {
            return collect();
        }

        return OrderProof::query()->with('customer:id,KdCust,NmCust')
            ->when($byNumber, fn ($query) => $query->where('NoOrder', 'like', $upper.'%'), fn ($query) => $query->whereIn('KdCust', $byCustomer))
            ->latest('id')->limit(self::LIMIT)->get()
            ->map(fn (OrderProof $proof) => [
                'type' => 'proof',
                'id' => $proof->id,
                'no_order' => $proof->NoOrder,
                'customer' => $proof->customer?->NmCust ? ucwords(mb_strtolower($proof->customer->NmCust)) : ($proof->KdCust ?: '-'),
                'date' => $proof->created_at?->format('Y-m-d'),
                'date_label' => $proof->created_at?->locale('id')->translatedFormat('d M Y'),
                'status' => $proof->statusLabel(),
                'status_bayar' => null,
                'edit_url' => route('order-proof.show', $proof),
                'nota_url' => null,
                'proforma_url' => null,
                'progress_url' => null,
                'invoice' => ($converted = $proof->convertedOrder()) ? '→ '.$converted->NoOrder : null,
                'proof' => null,
                'late' => null,
            ]);
    }

    private function orderRow(Request $request, string $type, Model $order): array
    {
        $user = $request->user();
        $editRoute = "order-{$type}.edit";
        $canEdit = $user->hasPermission("order-{$type}.view") && Route::has($editRoute);
        $canProforma = $user->hasPermission('kasir.view') || $user->hasPermission('keuangan.view') || $user->hasPermission('kasir.piutang-report.view') || $user->hasPermission('pengambilan.view');
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
            'estimate_end' => method_exists($order, 'operatorEstimateEndsAt')
                ? $order->operatorEstimateEndsAt()
                : ($order->diproses_kasir_at ? HariKerja::tambah($order->diproses_kasir_at, 1) : null),
            'edit_url' => $canEdit ? route($editRoute, $order->id) : null,
            'nota_url' => $canNota ? route('invoice.show', ['type' => $type, 'id' => $order->id]) : null,
            'proforma_url' => $canProforma && ProformaInvoiceController::isAvailableFor($order) ? route('kasir.proforma', ['type' => $type, 'id' => $order->id]) : null,
            'progress_url' => url("/dashboard/order-progress/{$type}/{$order->id}"),
        ];
    }

    /**
     * Penanda "pernah telat" untuk titik merah. Order yang baru lewat 24 jam dan
     * belum tercatat scheduler tetap ditandai dari aturan yang sama.
     *
     * @param  array<string, mixed>  $row
     * @return array{ongoing: bool, label: string}|null
     */
    private function lateInfo(?OrderLateLog $log, array $row): ?array
    {
        // Belum bayar = jam telat belum berjalan, termasuk bila masih ada catatan dari aturan lama.
        if ($row['status_bayar'] === 'belum_bayar' || in_array($row['status'], OrderLateLogService::UNPAID_STATUSES, true)) {
            return null;
        }

        if ($log) {
            $ongoing = $log->resolved_at === null;
            $label = ($ongoing ? 'Sedang telat ' : 'Pernah telat ').$log->lateDurationLabel()
                .($log->stage ? ' (tertahan di '.$log->stageLabel().')' : '')
                .($log->is_estimated ? ' · perkiraan' : '');

            return ['ongoing' => $ongoing, 'label' => $label];
        }

        // Belum tercatat scheduler: pakai batas estimasi yang sama (diproses kasir + Progress Hari).
        $estimateEnd = $row['estimate_end'] ?? null;
        if ($estimateEnd && ! in_array($row['status'], OrderLateLogService::DONE_STATUSES, true) && $estimateEnd->lt(now())) {
            return ['ongoing' => true, 'label' => 'Sedang telat (lewat estimasi, belum siap diambil)'];
        }

        return null;
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
