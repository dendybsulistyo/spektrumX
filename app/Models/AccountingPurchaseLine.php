<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPurchaseLine extends Model
{
    protected $table = 'accounting_purchase_lines';
    protected $fillable = ['purchase_id', 'inventory_item_id', 'deskripsi', 'klasifikasi', 'akun', 'qty', 'satuan', 'harga_satuan', 'subtotal'];
    protected function casts(): array { return ['qty' => 'float', 'harga_satuan' => 'float', 'subtotal' => 'float']; }
    public function purchase(): BelongsTo { return $this->belongsTo(AccountingPurchase::class, 'purchase_id'); }
    public function inventoryItem(): BelongsTo { return $this->belongsTo(AccountingInventoryItem::class, 'inventory_item_id'); }
}
