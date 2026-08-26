<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPurchase extends Model
{
    protected $table = 'accounting_purchases';

    protected $fillable = [
        'supplier_id', 'tanggal', 'nomor_bukti', 'keterangan', 'akun_pembelian',
        'dpp', 'ppn', 'total', 'status', 'cara_bayar', 'no_referensi', 'termin_hari', 'tanggal_terima_invoice',
        'jumlah_dibayar', 'jumlah_hutang', 'jumlah_retur', 'user_id', 'no_trans_jurnal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date', 'tanggal_terima_invoice' => 'date',
            'dpp' => 'float', 'ppn' => 'float', 'total' => 'float',
            'jumlah_dibayar' => 'float', 'jumlah_hutang' => 'float', 'jumlah_retur' => 'float',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(AccountingSupplier::class, 'supplier_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function payments(): HasMany { return $this->hasMany(AccountingPurchasePayment::class, 'purchase_id'); }
    public function returns(): HasMany { return $this->hasMany(AccountingPurchaseReturn::class, 'purchase_id'); }
    public function lines(): HasMany { return $this->hasMany(AccountingPurchaseLine::class, 'purchase_id'); }
}
