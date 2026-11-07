<?php

namespace App\Services;

use App\Models\OrderProof;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Menghubungkan order proof yang sudah selesai dengan satu order reguler. */
class OrderProofService
{
    /** Proof yang boleh dibuatkan order lewat form $formType (indoor/outdoor), atau 404. */
    public function convertible(int $proofId, string $formType): OrderProof
    {
        $proof = OrderProof::with('customer', 'items')->findOrFail($proofId);
        abort_unless($proof->status === 'selesai' && $proof->orderFormType() === $formType, 404);

        return $proof;
    }

    /**
     * Item awal form order reguler dari item proof (ukuran perkiraan ikut sebagai titik awal).
     *
     * @return Collection<int, object>
     */
    public function formItems(OrderProof $proof): Collection
    {
        return $proof->items->map(fn ($item) => $proof->order_type === 'outdoor'
            ? (object) [
                'NmFile' => mb_substr((string) $item->NmFile, 0, 50),
                'Panjang' => $item->Panjang,
                'Lebar' => $item->Lebar,
                'Qty' => $item->Qty,
                'KdCtk' => (string) $item->kode,
                'ada_finishing' => null,
                'jenis_finishing' => null,
            ]
            : (object) [
                'KdProd' => (string) $item->kode,
                'jenis_produk' => $item->jenis_produk ?: $proof->order_type,
                'Judul' => mb_substr((string) $item->NmFile, 0, 30),
                'Panjang' => $item->Panjang,
                'Lebar' => $item->Lebar,
                'Qty' => $item->Qty,
                'PisauTurun' => null,
                'JumlahKertas' => null,
                'TebalKertas' => null,
            ]);
    }

    /** Dipanggil di dalam transaksi pembuatan order; satu proof hanya bisa menjadi satu order. */
    public function markConverted(int $proofId, string $formType, Model $order): void
    {
        $proof = OrderProof::query()->lockForUpdate()->findOrFail($proofId);
        abort_unless($proof->status === 'selesai' && $proof->orderFormType() === $formType, 422,
            'Proof '.$proof->NoOrder.' sudah dibuatkan order atau belum selesai di Layout.');

        $proof->update([
            'status' => 'jadi_order',
            'converted_order_type' => $formType,
            'converted_order_id' => $order->id,
            'converted_at' => now(),
            'converted_by' => auth()->id(),
        ]);
    }
}
