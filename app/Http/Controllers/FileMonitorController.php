<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FileMonitorController extends Controller
{
    /**
     * Consolidated monitoring list of incoming order files across Indoor,
     * Outdoor, and Artwork — one row per file/detail item, showing No Order,
     * nama file, customer, tanggal, and the user who input the order.
     *
     * Outdoor details carry a real file name (NmFile). Indoor/Artwork have no
     * such column, so their detail "Judul" (title) is used in its place.
     *
     * Order headers total in the hundreds of thousands, so we paginate at the
     * header level first (cheap, indexed) and only join detail/file rows for
     * the handful of orders on the current page — joining detail tables
     * across the full header set would multiply out to billions of rows.
     */
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $headerQuery = function (string $table, string $jenis) use ($search) {
            $query = DB::table("$table as o")
                ->join('customers as c', 'c.KdCust', '=', 'o.KdCust')
                ->leftJoin('users as u', 'u.id', '=', 'o.created_by')
                ->where('o.status', '!=', 'selesai')
                ->select([
                    DB::raw("'$jenis' as jenis"),
                    'o.id as order_id',
                    'o.NoOrder as no_order',
                    'o.TglOrder as tanggal',
                    'c.NmCust as customer',
                    'u.name as user_input',
                ]);

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('o.NoOrder', 'like', "%{$search}%")
                        ->orWhere('c.NmCust', 'like', "%{$search}%");
                });
            }

            return $query;
        };

        $indoor = $headerQuery('order_indoor', 'Indoor');
        $outdoor = $headerQuery('order_outdoor', 'Outdoor');
        $artwork = $headerQuery('order_artwork', 'Artwork');

        $combined = $indoor->unionAll($outdoor)->unionAll($artwork);

        /** @var \Illuminate\Pagination\LengthAwarePaginator $headers */
        $headers = DB::table(DB::raw("({$combined->toSql()}) as combined_orders"))
            ->mergeBindings($combined)
            ->orderByDesc('tanggal')
            ->orderByDesc('no_order')
            ->paginate(20)
            ->withQueryString();

        $files = $this->attachFiles(collect($headers->items()));

        $headers->setCollection($files);

        return view('file.index', ['files' => $headers]);
    }

    /**
     * For the given page of order headers, fetch their detail/file rows and
     * flatten into one row per file (an order with no details still yields
     * one row with nama_file = null).
     */
    private function attachFiles(\Illuminate\Support\Collection $headers): \Illuminate\Support\Collection
    {
        $byJenis = $headers->groupBy('jenis');

        $indoorNoOrders = $byJenis->get('Indoor', collect())->pluck('no_order')->all();
        $indoorFiles = empty($indoorNoOrders) ? collect() : DB::table('order_indoor_detail')
            ->where(function ($q) use ($indoorNoOrders) {
                foreach ($indoorNoOrders as $noOrder) {
                    $q->orWhere('BrsOrder', 'like', "{$noOrder}%");
                }
            })
            ->get(['BrsOrder', 'Judul'])
            // BrsOrder is NoOrder (11 chars, itself containing a dot) plus a
            // 2-digit line number with no extra separator — not a plain
            // "prefix.suffix" split, so group by fixed-length prefix instead
            // of strtok (which would cut at the dot inside NoOrder itself).
            ->groupBy(fn ($row) => substr($row->BrsOrder, 0, 11));

        $outdoorIds = $byJenis->get('Outdoor', collect())->pluck('order_id')->all();
        $outdoorFiles = empty($outdoorIds) ? collect() : DB::table('order_outdoor_detail')
            ->whereIn('order_outdoor_id', $outdoorIds)
            ->get(['order_outdoor_id', 'NmFile'])
            ->groupBy('order_outdoor_id');

        $artworkIds = $byJenis->get('Artwork', collect())->pluck('order_id')->all();
        $artworkFiles = empty($artworkIds) ? collect() : DB::table('order_artwork_detail')
            ->whereIn('order_artwork_id', $artworkIds)
            ->get(['order_artwork_id', 'Judul'])
            ->groupBy('order_artwork_id');

        $rows = collect();

        foreach ($headers as $header) {
            $details = match ($header->jenis) {
                'Indoor' => $indoorFiles->get($header->no_order, collect())->pluck('Judul'),
                'Outdoor' => $outdoorFiles->get($header->order_id, collect())->pluck('NmFile'),
                'Artwork' => $artworkFiles->get($header->order_id, collect())->pluck('Judul'),
                default => collect(),
            };

            $namaFiles = $details->filter()->unique()->values();

            if ($namaFiles->isEmpty()) {
                $rows->push((object) [...(array) $header, 'nama_file' => null]);

                continue;
            }

            foreach ($namaFiles as $namaFile) {
                $rows->push((object) [...(array) $header, 'nama_file' => $namaFile]);
            }
        }

        return $rows;
    }
}
