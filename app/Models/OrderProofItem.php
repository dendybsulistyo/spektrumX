<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item proof. kode = KdCtk (outdoor: printer + bahan) atau KdProd (indoor/artwork);
 * ukuran hanya perkiraan dan boleh kosong.
 */
class OrderProofItem extends Model
{
    protected $fillable = ['order_proof_id', 'NmFile', 'kode', 'jenis_produk', 'Panjang', 'Lebar', 'Qty', 'catatan'];

    public function proof(): BelongsTo
    {
        return $this->belongsTo(OrderProof::class, 'order_proof_id');
    }
}
