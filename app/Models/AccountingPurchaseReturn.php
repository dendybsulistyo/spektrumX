<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPurchaseReturn extends Model
{
    protected $table = 'accounting_purchase_returns';

    protected $fillable = ['purchase_id', 'tanggal', 'nomor_bukti', 'jenis', 'keterangan', 'dpp', 'ppn', 'total', 'offset_hutang', 'refund', 'cara_refund', 'no_referensi', 'user_id', 'no_trans_jurnal'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'dpp' => 'float', 'ppn' => 'float', 'total' => 'float', 'offset_hutang' => 'float', 'refund' => 'float'];
    }

    public function purchase(): BelongsTo { return $this->belongsTo(AccountingPurchase::class, 'purchase_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
