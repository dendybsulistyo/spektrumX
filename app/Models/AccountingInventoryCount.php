<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingInventoryCount extends Model
{
    protected $table = 'accounting_inventory_counts';
    protected $fillable = ['inventory_item_id', 'tanggal', 'qty', 'harga_satuan', 'nilai', 'keterangan', 'user_id'];
    protected function casts(): array { return ['tanggal' => 'date', 'qty' => 'float', 'harga_satuan' => 'float', 'nilai' => 'float']; }
    public function item(): BelongsTo { return $this->belongsTo(AccountingInventoryItem::class, 'inventory_item_id'); }
}
