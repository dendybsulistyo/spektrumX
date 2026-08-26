<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingInventoryItem extends Model
{
    protected $table = 'accounting_inventory_items';
    protected $fillable = ['kode', 'nama', 'kelompok', 'satuan', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function counts(): HasMany { return $this->hasMany(AccountingInventoryCount::class, 'inventory_item_id'); }
}
