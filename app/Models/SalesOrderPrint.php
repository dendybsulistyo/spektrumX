<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderPrint extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'first_printed_at' => 'datetime',
            'last_printed_at' => 'datetime',
            'print_count' => 'integer',
        ];
    }
}
