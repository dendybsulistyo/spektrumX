<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanCetakOutdoor extends Model
{
    protected $table = 'bahan_cetak_outdoor';

    protected $primaryKey = 'NoUrut';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'NmBhn',
        'NoUrut',
        'NoCetak',
    ];
}
