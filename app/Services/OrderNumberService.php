<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderNumberService
{
    /**
     * Ambil nomor berikutnya di dalam transaksi pemanggil. Baris sequence
     * dikunci agar dua CS tidak memperoleh nomor SO yang sama.
     */
    public function next(string $type, string $date): string
    {
        if (DB::transactionLevel() < 1) {
            throw new RuntimeException('Pembuatan nomor order wajib berada di dalam transaksi database.');
        }

        [$model, $prefix] = match ($type) {
            'indoor' => [OrderIndoor::class, 'IND.2.'.date('ymd', strtotime($date))],
            'outdoor' => [OrderOutdoor::class, 'OUT.1.'.date('ymd', strtotime($date))],
            'artwork' => [OrderArtwork::class, 'ART'.date('ymd', strtotime($date))],
            default => throw new RuntimeException("Jenis order {$type} tidak dikenal."),
        };

        DB::table('order_number_sequences')->insertOrIgnore([
            'order_type' => $type,
            'order_date' => $date,
            'last_sequence' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('order_number_sequences')
            ->where('order_type', $type)
            ->where('order_date', $date)
            ->lockForUpdate()
            ->first();

        $lastSequence = (int) $sequence->last_sequence;
        if ($lastSequence === 0) {
            $lastNumber = $model::query()
                ->where('NoOrder', 'like', $prefix.'%')
                ->orderByDesc('NoOrder')
                ->value('NoOrder');
            $lastSequence = $lastNumber ? (int) substr($lastNumber, strlen($prefix), 5) : 0;
        }

        $next = $lastSequence + 1;
        DB::table('order_number_sequences')
            ->where('order_type', $type)
            ->where('order_date', $date)
            ->update(['last_sequence' => $next, 'updated_at' => now()]);

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
