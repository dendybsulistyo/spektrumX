<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPurchasePayment extends Model
{
    protected $table = 'accounting_purchase_payments';

    protected $fillable = ['purchase_id', 'tanggal', 'jumlah', 'cara_bayar', 'no_referensi', 'user_id', 'no_trans_jurnal'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jumlah' => 'float'];
    }

    public function purchase(): BelongsTo { return $this->belongsTo(AccountingPurchase::class, 'purchase_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
