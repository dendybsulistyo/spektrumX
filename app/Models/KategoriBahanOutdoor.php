<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriBahanOutdoor extends Model
{
    protected $table = 'kategori_bahan_outdoor';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdGrup',
        'NmGrup',
        'NoUrut',
    ];

    public function bahan(): HasMany
    {
        return $this->hasMany(BahanOutdoor::class, 'KdGrup', 'KdGrup');
    }
}
