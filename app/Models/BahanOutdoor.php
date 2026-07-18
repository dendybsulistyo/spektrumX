<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BahanOutdoor extends Model
{
    protected $table = 'bahan_outdoor';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdBrgs',
        'KdGrup',
        'NmBrgs',
        'Keters',
        'Satuan',
        'NoUrut',
    ];

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBahanOutdoor::class, 'KdGrup', 'KdGrup');
    }
}
