<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderOutdoor extends Model
{
    protected $table = 'order_outdoor';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'NoOrder',
        'TglOrder',
        'KdCust',
        'KdOpr',
        'Cetak',
    ];

    protected function casts(): array
    {
        return [
            'TglOrder' => 'date',
            'Cetak' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderOutdoorDetail::class);
    }
}
