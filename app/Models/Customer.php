<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    /** Transaksi yang menyimpan kode customer: tabel => [kolom, keterangan]. Selama masih ada, customer tidak boleh dihapus. */
    private const TRANSACTION_REFERENCES = [
        'order_indoor' => ['KdCust', 'order indoor'],
        'order_outdoor' => ['KdCust', 'order outdoor'],
        'order_artwork' => ['KdCust', 'order artwork'],
        'customer_service_job_sheets' => ['customer_code', 'lembar kerja CS'],
        'final_sales_discounts' => ['customer_code', 'potongan penjualan'],
        'order_financial_adjustments' => ['customer_code', 'penyesuaian nota'],
    ];

    /** Alasan customer tidak boleh dihapus (agar transaksi tidak kehilangan customernya), atau null bila aman. */
    public function deleteBlockReason(): ?string
    {
        $used = collect(self::TRANSACTION_REFERENCES)
            ->filter(fn ($ref, $table) => Schema::hasTable($table) && DB::table($table)->where($ref[0], $this->KdCust)->exists())
            ->map(fn ($ref) => $ref[1]);
        if ($used->isNotEmpty()) {
            return 'Customer sudah dipakai di '.$used->join(', ', ' dan ').'.';
        }
        if ($this->limit && (float) $this->limit->Total !== 0.0) {
            return 'Customer masih memiliki piutang VIP berjalan.';
        }

        return null;
    }

    /** Data pengaturan milik customer yang ikut dihapus bersama customer. */
    public function deleteWithSettings(): void
    {
        DB::transaction(function () {
            CustomerLimit::where('KdCust', $this->KdCust)->delete();
            foreach (['harga_khusus_customer' => 'KdCust', 'harga_cetak_outdoor_khusus' => 'KdCust', 'accounting_customer_profiles' => 'customer_kd'] as $table => $column) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->where($column, $this->KdCust)->delete();
                }
            }
            $this->delete();
        });
    }

    public function getIsVipAttribute(): bool
    {
        return $this->limit !== null;
    }
}
