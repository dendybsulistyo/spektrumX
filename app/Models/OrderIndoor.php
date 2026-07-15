<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderIndoor extends Model
{
    protected $table = 'indm';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'TglOrder',
        'NoOrder',
        'KdCust',
        'KdOpr',
        'Cetak',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'KdCust', 'KdCust');
    }
}
