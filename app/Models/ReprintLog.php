<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReprintLog extends Model
{
    public const UPDATED_AT = null;

    public const MODE_LABELS = ['bebas' => 'Dalam batas waktu', 'hak_khusus' => 'Admin Kasir / Akuntansi', 'approval' => 'Dengan approval'];

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
