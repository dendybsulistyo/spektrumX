<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterOutdoor extends Model
{
    protected $table = 'printers_outdoors';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdPrn',
        'NmPrn',
        'NoUrut',
    ];
}
