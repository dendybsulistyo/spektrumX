<?php

namespace App\Services;

use App\Models\OrderDocument;
use App\Models\OrderPickupSignature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliveryOrderService
{
    public function receive(Model $item, string $type, array $data, string $path, string $hash, int $userId): OrderDocument
    {
        return DB::transaction(function () use ($item, $type, $data, $path, $hash, $userId) {
            $order = $item->order;
            $order = $order->newQuery()->lockForUpdate()->findOrFail($order->id);
            $requested = collect($data['items'] ?? [['id' => $item->id, 'qty' => $data['qty']]])
                ->map(fn ($row) => ['id' => (int) $row['id'], 'qty' => (int) $row['qty']])->sortBy('id')->values();
            abort_unless($requested->isNotEmpty() && $requested->pluck('id')->unique()->count() === $requested->count(), 422, 'Daftar item tidak valid.');
            $items = $requested->map(function ($row) use ($item, $order) {
                $detail = $item->newQuery()->lockForUpdate()->findOrFail($row['id']);
                abort_unless($detail->order?->id === $order->id, 422, 'Semua barang harus berasal dari SO yang sama.');

                return $detail;
            });
            $existing = OrderDocument::where('request_key', $data['request_key'])->first();
            if ($existing) {
                $previous = collect($existing->snapshot['items'])->map(fn ($row) => ['id' => (int) $row['id'], 'qty' => (int) $row['qty']])->sortBy('id')->values();
                abort_unless($existing->kind === 'do' && $existing->order_type === $type && $existing->order_id === $order->id
                    && $previous->all() === $requested->all()
                    && $existing->snapshot['recipient'] === $data['nama_penerima'] && $existing->snapshot['contact'] === $data['kontak_penerima'], 422, 'Permintaan pengambilan tidak cocok.');

                return $existing;
            }
            abort_if($order->status === 'batal' || $order->invoice_voided_at, 422, 'Order dibatalkan.');
            $paid = $order->status_bayar === 'lunas' && (float) $order->jumlah_piutang <= 0;
            $credit = $order->status_bayar === 'hutang' && $order->customer?->isVip;
            abort_unless($paid || $credit, 422, 'Lunasi pesanan terlebih dahulu. Pengambilan sebelum lunas hanya untuk hutang VIP yang sudah diproses kasir.');
            $lines = [];
            foreach ($items as $index => $detail) {
                $qty = $requested[$index]['qty'];
                $result = app(StageProgressService::class)->advance($detail, 'siap_diambil', $qty,
                    "Diambil oleh: {$data['nama_penerima']} (Kontak: {$data['kontak_penerima']})", $userId);
                OrderPickupSignature::create([
                    'order_type' => $type, 'order_id' => $order->id, 'order_detail_id' => $detail->id, 'qty' => $qty,
                    'nama_penerima' => $data['nama_penerima'], 'kontak_penerima' => $data['kontak_penerima'],
                    'signature_path' => $path, 'signature_hash' => $hash, 'received_by' => $userId, 'received_at' => now(),
                ]);
                $lines[] = ['id' => $detail->id, 'description' => $detail->Judul ?: ($detail->NmFile ?: $detail->NmProd),
                    'qty' => $qty, 'length' => $detail->Panjang, 'width' => $detail->Lebar];
            }
            $sequence = 1 + (int) OrderDocument::where('kind', 'do')->where('order_type', $type)->where('order_id', $order->id)->max('sequence');
            $documents = app(OrderDocumentService::class);
            $snapshot = $documents->snapshot($order);
            $snapshot['items'] = $lines;
            $snapshot += ['recipient' => $data['nama_penerima'], 'contact' => $data['kontak_penerima'], 'signature_path' => $path];
            $document = OrderDocument::create([
                'kind' => 'do', 'order_type' => $type, 'order_id' => $order->id, 'sequence' => $sequence,
                'number' => $documents->number($order, 'do').'-'.$sequence,
                'request_key' => $data['request_key'], 'issued_at' => now(), 'issued_by' => $userId, 'snapshot' => $snapshot,
            ]);
            if ($result['order']->status === 'selesai' && ! $result['order']->diambil_at) {
                $result['order']->update(['diambil_at' => now(), 'pengambilan_by' => $userId]);
            }

            return $document;
        });
    }
}
