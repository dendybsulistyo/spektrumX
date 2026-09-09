<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\PengaturanKeuangan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderDocumentService
{
    public const MODELS = ['indoor' => OrderIndoor::class, 'outdoor' => OrderOutdoor::class, 'artwork' => OrderArtwork::class];

    public function type(Model $order): ?string
    {
        return array_search($order::class, self::MODELS, true) ?: null;
    }

    public function number(Model $order, string $kind): string
    {
        $division = ['outdoor' => '1', 'indoor' => '2', 'artwork' => '3'][$this->type($order)];
        $suffix = preg_replace('/^(OUT\.1\.|IND\.2\.|OUT|IND|ART)/', '', $order->NoOrder);

        return strtoupper($kind).'.'.$division.'.'.$suffix;
    }

    public function snapshot(Model $order, bool $withPricing = false): array
    {
        $type = $this->type($order);
        $rawItems = $order->detailItems()->values();
        $items = $rawItems->map(fn ($item) => [
            'id' => $item->id,
            'description' => $item->Judul ?: ($item->NmFile ?: $item->NmProd),
            'qty' => $item->Qty,
            'length' => $item->Panjang,
            'width' => $item->Lebar,
            'unit_price' => null,
            'subtotal' => null,
        ]);

        if ($withPricing && $type && $this->catalogsAvailable($type)) {
            $priced = app(OrderPricingService::class)->detailedLineItems($type, $order, $rawItems);
            $items = $items->map(function (array $item, int $index) use ($priced) {
                $price = $priced->get($index);
                $item['unit_price'] = $price?->harga_satuan;
                $item['subtotal'] = $price?->subtotal;

                return $item;
            });
        }

        return [
            'sales_order' => $order->NoOrder,
            'customer_code' => $order->KdCust,
            'customer' => $order->customer?->NmCust,
            'address' => $order->customer?->Alamat,
            'city' => $order->customer?->Kota,
            'customer_phone' => $order->customer?->Telp,
            'customer_npwp' => $order->customer?->NPWP,
            'order_total' => $order->total,
            'discount_percent' => $order->diskon_persen,
            'discount_amount' => $order->diskon_nominal_tetap,
            'payment_method' => $order->cara_bayar,
            'payment_reference' => $order->no_referensi,
            'items' => $items->all(),
        ];
    }

    private function catalogsAvailable(string $type): bool
    {
        return match ($type) {
            'indoor' => Schema::hasTable('produk_indoor') && Schema::hasTable('harga_artwork'),
            'outdoor' => Schema::hasTable('harga_cetak_outdoor')
                && Schema::hasTable('printers_outdoors') && Schema::hasTable('bahan_cetak_outdoor'),
            'artwork' => Schema::hasTable('harga_artwork'),
            default => false,
        };
    }

    public function issueInvoice(Model $order, mixed $issuedAt = null, string $origin = 'operational'): ?OrderDocument
    {
        if (! $type = $this->type($order)) {
            return null;
        }

        return DB::transaction(function () use ($order, $type, $issuedAt, $origin) {
            $order = $order->newQuery()->lockForUpdate()->findOrFail($order->id);
            if ($order->status_bayar !== 'lunas' || (float) $order->jumlah_piutang > 0 || $order->status === 'batal' || $order->invoice_voided_at) {
                return null;
            }
            $key = ['kind' => 'inv', 'order_type' => $type, 'order_id' => $order->id, 'sequence' => 1];
            if ($existing = OrderDocument::where($key)->first()) {
                return $existing;
            }
            $at = $issuedAt ?? $order->dibayar_at;
            if (! $at) {
                throw new \RuntimeException('Tanggal pelunasan wajib tersedia untuk menerbitkan invoice.');
            }

            $company = Schema::hasTable('pengaturan_keuangan') ? PengaturanKeuangan::current() : null;

            return OrderDocument::create($key + [
                'number' => $this->number($order, 'inv'), 'issued_at' => $at,
                'issued_by' => auth()->id(), 'total' => $order->jumlah_dibayar,
                'snapshot' => $this->snapshot($order, true) + [
                    'paid' => $order->jumlah_dibayar,
                    'balance' => 0,
                    'origin' => $origin,
                    'issued_by_name' => auth()->user()?->name,
                    'company_name' => $company?->nama_perusahaan,
                    'company_address' => $company?->alamat_perusahaan,
                    'company_npwp' => $company?->npwp_perusahaan,
                ],
            ]);
        });
    }

    public function invoiceQuery()
    {
        $queries = [];
        foreach (self::MODELS as $type => $model) {
            $table = (new $model)->getTable();
            $queries[] = DB::table('order_documents as d')->join($table.' as o', 'o.id', '=', 'd.order_id')
                ->where('d.kind', 'inv')->where('d.order_type', $type)
                ->where('o.status_bayar', 'lunas')->where('o.jumlah_piutang', '<=', 0)
                ->where('o.status', '!=', 'batal')->whereNull('o.invoice_voided_at')
                ->select('d.*');
        }
        $query = array_shift($queries);
        foreach ($queries as $other) {
            $query->unionAll($other);
        }

        return DB::query()->fromSub($query, 'invoices');
    }
}
