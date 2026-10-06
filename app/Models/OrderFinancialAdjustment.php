<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderFinancialAdjustment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'float',
            'paid_before' => 'float',
            'paid_after' => 'float',
            'receivable_before' => 'float',
            'receivable_after' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'KdCust');
    }
}
