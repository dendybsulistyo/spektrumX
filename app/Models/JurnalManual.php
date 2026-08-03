<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JurnalManual extends Model
{
    protected $table = 'jurnal_manual';

    protected $fillable = [
        'tanggal',
        'keterangan',
        'no_trans_jurnal',
        'status',
        'user_id',
        'dibatalkan_oleh',
        'dibatalkan_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    /**
     * The actual debit/credit lines, pulled from `am` by NoTrans — there's
     * no FK for this since am has no primary key, just a shared NoTrans.
     */
    public function jurnalLines(): Collection
    {
        if (! $this->no_trans_jurnal) {
            return new Collection;
        }

        return JurnalEntry::where('NoTrans', $this->no_trans_jurnal)->orderBy('NoAkun')->get();
    }
}
