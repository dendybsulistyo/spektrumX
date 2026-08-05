<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'KdCust',
        'NmCust',
        'Alamat',
        'Kota',
        'Telp',
        'NPWP',
    ];

    public function limit()
    {
        return $this->hasOne(CustomerLimit::class, 'KdCust', 'KdCust');
    }

    public function hargaCetakOutdoorKhusus()
    {
        return $this->hasMany(HargaCetakOutdoorKhusus::class, 'KdCust', 'KdCust');
    }

    public function getIsVipAttribute(): bool
    {
        return $this->limit !== null;
    }
}
