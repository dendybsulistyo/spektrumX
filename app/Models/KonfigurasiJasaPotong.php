<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KonfigurasiJasaPotong extends Model
{
    protected $table = 'konfigurasi_jasa_potong';

    protected $fillable = [
        'nilai_x',
    ];

    protected function casts(): array
    {
        return [
            'nilai_x' => 'float',
        ];
    }

    /**
     * Singleton settings row — created by the migration, so this always
     * resolves to the same record.
     */
    public static function current(): self
    {
        return self::query()->firstOrCreate([], ['nilai_x' => 0]);
    }
}
