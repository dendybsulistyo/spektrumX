<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $table = 'operators';

    protected $primaryKey = 'KdOpr';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdOpr',
        'NmOpr',
        'Status',
    ];

    protected function casts(): array
    {
        return [
            'Status' => 'boolean',
        ];
    }
}
