<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rekap aktivitas satu jam yang baru lewat untuk maskot ucapan terima kasih.
 * Dihitung sekali per jam (cache), berapa pun jumlah browser yang meminta.
 */
class ApresiasiController extends Controller
{
    /** Tahap produksi yang dihitung sebagai "progress" (sama dengan tim di dashboard). */
    private const STAGES = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai', 'pengambilan'];

    public function __invoke(): JsonResponse
    {
        if (! config('apresiasi.aktif')) {
            return response()->json(['aktif' => false]);
        }

        $to = now()->startOfHour();
        $from = $to->copy()->subHour();

        $data = Cache::remember('apresiasi:'.$to->format('YmdH'), now()->addMinutes(90), fn () => $this->summary($from, $to));

        return response()->json(['aktif' => true, 'jam' => $to->format('YmdH')] + $data);
    }

    /** @return array{names: list<string>, more: int, progress: int, pembayaran: int, lembar_kerja: int} */
    private function summary(Carbon $from, Carbon $to): array
    {
        $progress = DB::table('order_status_notes')
            ->whereBetween('created_at', [$from, $to->copy()->subSecond()])
            ->whereIn('stage', self::STAGES)
            ->whereIn('action', ['progress', 'selesai'])
            ->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->pluck('n', 'user_id');

        $payments = DB::table('order_payments')
            ->whereBetween('created_at', [$from, $to->copy()->subSecond()])
            ->where('jenis', '!=', 'refund')
            ->selectRaw('user_id, COUNT(*) as n')->groupBy('user_id')->pluck('n', 'user_id');

        $sheets = DB::table('customer_service_job_sheets')
            ->whereBetween('created_at', [$from, $to->copy()->subSecond()])
            ->whereNotNull('created_by')
            ->selectRaw('created_by as user_id, COUNT(*) as n')->groupBy('created_by')->pluck('n', 'user_id');

        // Urut dari yang paling banyak berkontribusi.
        $userIds = $progress->keys()->merge($payments->keys())->merge($sheets->keys())
            ->map(fn ($id) => (int) $id)->unique()
            ->sortByDesc(fn ($id) => ($progress[$id] ?? 0) + ($payments[$id] ?? 0) + ($sheets[$id] ?? 0))
            ->values();
        $names = DB::table('users')->whereIn('id', $userIds)->pluck('name', 'id');
        $ordered = $userIds->map(fn ($id) => $names[$id] ?? null)->filter()
            ->map(fn ($name) => ucwords(mb_strtolower(trim($name))))->values();
        $max = (int) config('apresiasi.maks_nama', 12);

        return [
            'names' => $ordered->take($max)->all(),
            'more' => max(0, $ordered->count() - $max),
            'progress' => (int) $progress->sum(),
            'pembayaran' => (int) $payments->sum(),
            'lembar_kerja' => (int) $sheets->sum(),
        ];
    }
}
