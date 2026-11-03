<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Order yang pernah telat (> 24 jam sejak pertama diproses kasir belum Siap Diambil). is_estimated = hasil
 * backfill dari waktu Bungkus, bukan tercatat langsung oleh scheduler.
 */
class OrderLateLog extends Model
{
    public const LATE_AFTER_HOURS = 24;

    public const STAGE_LABELS = [
        'baru' => 'Baru',
        'dibayar' => 'Dibayar',
        'desain' => 'Layout',
        'cetak' => 'Cetak',
        'finishing' => 'Finishing',
        'qc' => 'Back Office',
        'bungkus' => 'Bungkus',
    ];

    protected $fillable = [
        'order_type',
        'order_id',
        'order_created_at',
        'late_at',
        'stage',
        'resolved_at',
        'resolved_status',
        'is_estimated',
    ];

    protected function casts(): array
    {
        return [
            'order_created_at' => 'datetime',
            'late_at' => 'datetime',
            'resolved_at' => 'datetime',
            'is_estimated' => 'boolean',
        ];
    }

    /** Lama telat: dari batas 24 jam sampai siap (atau sampai sekarang bila masih telat). */
    public function lateDurationLabel(): string
    {
        $minutes = (int) $this->late_at->diffInMinutes($this->resolved_at ?? now(), true);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);

        if ($days > 0) {
            return $hours > 0 ? "{$days} hari {$hours} jam" : "{$days} hari";
        }

        return $hours > 0 ? "{$hours} jam" : max(1, $minutes % 60).' menit';
    }

    public function stageLabel(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? ($this->stage ? ucfirst($this->stage) : '-');
    }
}
