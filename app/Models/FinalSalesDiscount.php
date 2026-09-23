<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalSalesDiscount extends Model
{
    protected $fillable = [
        'order_type',
        'order_id',
        'order_number',
        'customer_code',
        'transaction_date',
        'initial_amount',
        'discount_before',
        'discount_amount',
        'final_amount',
        'dpp_adjustment',
        'tax_adjustment',
        'receivable_offset',
        'refund_amount',
        'refund_method',
        'reference_number',
        'reason',
        'user_id',
        'journal_transaction_number',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'initial_amount' => 'float',
            'discount_before' => 'float',
            'discount_amount' => 'float',
            'final_amount' => 'float',
            'dpp_adjustment' => 'float',
            'tax_adjustment' => 'float',
            'receivable_offset' => 'float',
            'refund_amount' => 'float',
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
