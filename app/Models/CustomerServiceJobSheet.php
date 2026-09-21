<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerServiceJobSheet extends Model
{
    protected $fillable = [
        'customer_code',
        'customer_name',
        'pc',
        'folder_file',
        'received_at',
        'deadline',
        'opf',
        'notes',
        'items',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'deadline' => 'date',
            'items' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_code', 'KdCust');
    }
}
