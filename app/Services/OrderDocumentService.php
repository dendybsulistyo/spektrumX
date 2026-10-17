<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderDocument;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use App\Models\PengaturanKeuangan;
use App\Support\Rupiah;
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
                return $this->tandaiInvoiceVipLunas($existing, $order, $issuedAt);
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

    /**
     * Invoice VIP: terbit saat barang diambil (bersamaan DO) walau belum lunas,
     * selama order berstatus hutang VIP (dalam plafon). Nomor & tanggal invoice =
     * saat itu; saat tagihan lunas, invoice yang SAMA ditandai lunas
     * (lihat tandaiInvoiceVipLunas). Order lunas tetap lewat issueInvoice().
     */
    public function issueCreditInvoice(Model $order, mixed $issuedAt = null, string $origin = 'vip_credit'): ?OrderDocument
    {
        if (! $type = $this->type($order)) {
            return null;
        }

        return DB::transaction(function () use ($order, $type, $issuedAt, $origin) {
            $order = $order->newQuery()->with('customer.limit')->lockForUpdate()->findOrFail($order->id);
            if ($order->status_bayar !== 'hutang' || (float) $order->jumlah_piutang <= 0 || ! $order->customer?->isVip
                || $order->status === 'batal' || $order->invoice_voided_at) {
                return null;
            }
            $key = ['kind' => 'inv', 'order_type' => $type, 'order_id' => $order->id, 'sequence' => 1];
            if ($existing = OrderDocument::where($key)->first()) {
                return $existing;
            }

            $company = Schema::hasTable('pengaturan_keuangan') ? PengaturanKeuangan::current() : null;

            return OrderDocument::create($key + [
                'number' => $this->number($order, 'inv'), 'issued_at' => $issuedAt ?? now(),
                'issued_by' => auth()->id(),
                // Nilai tagihan (bukan uang diterima, karena belum lunas).
                'total' => Rupiah::bulatkan((float) $order->jumlah_dibayar + (float) $order->jumlah_piutang),
                // array_merge: nilai di bawah MENIMPA snapshot (mis. payment_method dari order).
                'snapshot' => array_merge($this->snapshot($order, true), [
                    'paid' => (float) $order->jumlah_dibayar,
                    'balance' => (float) $order->jumlah_piutang,
                    'payment_method' => 'hutang',
                    'origin' => $origin,
                    'issued_by_name' => auth()->user()?->name,
                    'company_name' => $company?->nama_perusahaan,
                    'company_address' => $company?->alamat_perusahaan,
                    'company_npwp' => $company?->npwp_perusahaan,
                ]),
            ]);
        });
    }

    /**
     * Invoice VIP (terbit sebelum lunas) yang tagihannya kini lunas: nomor &
     * tanggal tetap, status jadi lunas. `lunas_at` dipakai invoiceQuery agar
     * PPN / Gunggungan / Rekap Omset tetap mencatat di tanggal pelunasan,
     * sama seperti sebelum ada invoice VIP.
     */
    private function tandaiInvoiceVipLunas(OrderDocument $invoice, Model $order, mixed $lunasAt = null): OrderDocument
    {
        $snapshot = $invoice->snapshot;
        if ((float) ($snapshot['balance'] ?? 0) <= 0) {
            return $invoice;
        }

        // Tampil seperti SO: total & rincian TETAP seperti saat invoice terbit, hanya
        // status jadi lunas. Potongan Penjualan Akhir adalah koreksi INTERNAL dan
        // tidak boleh terlihat di invoice customer (mis. "Diskon" / total Rp 0).
        $snapshot['paid'] = (float) $invoice->total;
        $snapshot['balance'] = 0;
        $snapshot['payment_method'] = $order->cara_bayar;
        $snapshot['payment_reference'] = $order->no_referensi;
        $snapshot['lunas_at'] = \Illuminate\Support\Carbon::parse($lunasAt ?? $order->dibayar_at ?? now())->toDateTimeString();
        // issued_at ditulis ulang apa adanya: di MySQL lama kolom TIMESTAMP pertama bisa
        // "ON UPDATE CURRENT_TIMESTAMP" dan tanggal invoice ikut berubah tanpa ini.
        $invoice->forceFill(['snapshot' => $snapshot, 'issued_at' => $invoice->getRawOriginal('issued_at')]);
        $invoice->newQuery()->whereKey($invoice->getKey())->update([
            'snapshot' => json_encode($snapshot),
            'issued_at' => $invoice->getRawOriginal('issued_at'),
            'updated_at' => now(),
        ]);

        return $invoice;
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
                // Invoice VIP yang terbit sebelum lunas dihitung di tanggal pelunasan
                // (lunas_at), sama seperti perilaku laporan pajak sebelumnya.
                ->select('d.id', 'd.kind', 'd.order_type', 'd.order_id', 'd.sequence', 'd.number', 'd.request_key',
                    'd.issued_by', 'd.total', 'd.snapshot', 'd.created_at', 'd.updated_at')
                ->selectRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(d.snapshot, '$.lunas_at')), d.issued_at) as issued_at");
        }
        $query = array_shift($queries);
        foreach ($queries as $other) {
            $query->unionAll($other);
        }

        return DB::query()->fromSub($query, 'invoices');
    }
}
