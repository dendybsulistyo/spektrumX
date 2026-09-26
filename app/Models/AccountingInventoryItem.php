<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountingInventoryItem extends Model
{
    protected $table = 'accounting_inventory_items';

    protected $fillable = ['kode', 'nama', 'kelompok', 'satuan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function counts(): HasMany
    {
        return $this->hasMany(AccountingInventoryCount::class, 'inventory_item_id');
    }

    public function latestCount(): HasOne
    {
        return $this->hasOne(AccountingInventoryCount::class, 'inventory_item_id')->latestOfMany('tanggal');
    }
}
