<?php

namespace App\Services;

use App\Models\OrderDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Item dari produk yang "tidak diproses operator" (Produk::diproses = false,
 * mis. kresek, CTcP): langsung selesai sejak SO dibuat (lihat saveItems di
 * OrderIndoor/ArtworkController). Item ini tetap harus tercantum di DO:
 * - order campuran: ikut DO pertama saat barang lain diserahkan di Pengambilan;
 * - order yang seluruhnya tanpa proses: DO terbit otomatis begitu lunas
 *   (atau hutang VIP) dan order langsung Selesai.
 */
class TanpaProsesService
{
    public const RECIPIENT = 'Tanpa proses operator (otomatis)';

    /**
     * Item tanpa proses yang belum tercantum di DO mana pun untuk order ini.
     *
     * @return Collection<int, Model>
     */
    public function pendingItems(Model $order, string $type): Collection
    {
        $items = $order->detailItems()->filter(fn ($item) => (bool) $item->tanpa_proses);
        if ($items->isEmpty()) {
            return $items;
        }
        $listed = OrderDocument::query()->where('kind', 'do')->where('order_type', $type)->where('order_id', $order->id)
            ->get(['snapshot'])
            ->flatMap(fn (OrderDocument $document) => collect($document->snapshot['items'] ?? [])->pluck('id'))
            ->map(fn ($id) => (int) $id)->all();

        return $items->reject(fn ($item) => in_array((int) $item->id, $listed, true))->values();
    }

    /** Baris DO untuk item tanpa proses (qty penuh). */
    public function lines(Collection $items): array
    {
        return $items->map(fn ($detail) => [
            'id' => $detail->id,
            'description' => $detail->Judul ?: ($detail->NmFile ?: $detail->NmProd),
            'qty' => (int) $detail->Qty,
            'length' => $detail->Panjang,
            'width' => $detail->Lebar,
        ])->values()->all();
    }

    /**
     * Dipanggil setelah setiap pembayaran: order yang SELURUH item-nya tanpa
     * proses langsung Selesai dengan DO otomatis bila sudah lunas / hutang VIP.
     */
    public function afterPayment(Model $order): ?OrderDocument
    {
        $documents = app(OrderDocumentService::class);
        $type = $documents->type($order);
        if (! $type || in_array($order->status, ['baru', 'batal', 'selesai'], true) || $order->invoice_voided_at) {
            return null;
        }
        $items = $order->detailItems();
        if ($items->isEmpty() || $items->contains(fn ($item) => ! $item->tanpa_proses)) {
            return null;
        }
        $paid = $order->status_bayar === 'lunas' && (float) $order->jumlah_piutang <= 0;
        $credit = $order->status_bayar === 'hutang' && $order->customer?->isVip;
        if (! $paid && ! $credit) {
            // DP: tidak ada yang dikerjakan operator; tunggu pelunasan (DO terbit saat itu).
            if ($order->status !== 'siap_diambil') {
                $order->update(['status' => 'siap_diambil']);
            }

            return null;
        }
        $pending = $this->pendingItems($order, $type);
        if ($pending->isEmpty()) {
            return null;
        }

        $sequence = 1 + (int) OrderDocument::where('kind', 'do')->where('order_type', $type)->where('order_id', $order->id)->max('sequence');
        $snapshot = $documents->snapshot($order);
        $snapshot['items'] = $this->lines($pending);
        $snapshot += ['recipient' => self::RECIPIENT, 'contact' => '-', 'signature_path' => null];
        $document = OrderDocument::create([
            'kind' => 'do', 'order_type' => $type, 'order_id' => $order->id, 'sequence' => $sequence,
            'number' => $documents->number($order, 'do').'-'.$sequence,
            'request_key' => (string) Str::uuid(), 'issued_at' => now(), 'issued_by' => auth()->id(), 'snapshot' => $snapshot,
        ]);
        if ($credit) {
            $documents->issueCreditInvoice($order, $document->issued_at);
        }
        $order->update(['status' => 'selesai', 'diambil_at' => now(), 'pengambilan_by' => auth()->id()]);

        return $document;
    }
}
