<?php

namespace App\Http\Controllers;

use App\Models\OrderPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * "Ringkasan hari ini" di menu user — khusus operator produksi, CS, & kasir.
 * Dihitung saat menu dibuka dan di-cache 5 menit per user.
 */
class DailySummaryController extends Controller
{
    /** Hak akses operator → tahap di riwayat proses (order_status_notes.stage). */
    private const OPERATOR_STAGES = [
        'order-desain.manage' => 'desain',
        'order-cetak.manage' => 'cetak',
        'order-finishing.manage' => 'finishing',
        'order-qc.manage' => 'qc',
        'order-bungkus.manage' => 'bungkus',
        'pengambilan.manage' => 'siap_diambil',
    ];

    /** Aksi yang berarti pekerjaan diteruskan / diselesaikan. */
    private const DONE_ACTIONS = ['selesai', 'progress', 'lanjut', 'revisi_selesai'];

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! self::applies($user)) {
            return response()->json(['sections' => []]);
        }

        $today = CarbonImmutable::today();
        $sections = Cache::remember(
            "ringkasan-harian:{$user->id}:{$today->toDateString()}",
            now()->addMinutes(5),
            fn () => $this->build($user, $today),
        );

        return response()->json([
            'date' => $today->locale('id')->translatedFormat('l, d M Y'),
            'sections' => $sections,
        ]);
    }

    /** Ditampilkan hanya untuk operator/CS/kasir — bukan admin (pengelola role). */
    public static function applies(User $user): bool
    {
        if ($user->hasPermission('roles.manage')) {
            return false;
        }

        return collect([...array_keys(self::OPERATOR_STAGES), 'customer-service.manage', 'kasir.manage'])
            ->contains(fn (string $permission) => $user->hasPermission($permission));
    }

    private function build(User $user, CarbonImmutable $today): array
    {
        $sections = [];
        $yesterday = $today->subDay();

        $stages = collect(self::OPERATOR_STAGES)->filter(fn ($stage, $permission) => $user->hasPermission($permission))->values()->all();
        if ($stages) {
            $todayStats = $this->operatorStats($user->id, $stages, $today);
            $yesterdayQty = $this->operatorStats($user->id, $stages, $yesterday)['qty'];
            $sections[] = [
                'kind' => 'operator',
                'title' => 'Produksi',
                'stats' => [
                    ['label' => 'qty dikirim', 'value' => $todayStats['qty']],
                    ['label' => 'item', 'value' => $todayStats['items']],
                    ['label' => 'order', 'value' => $todayStats['orders']],
                ],
                'note' => $todayStats['reworks'] > 0 ? "{$todayStats['reworks']} kali diulang/revisi" : null,
                'trend' => $this->trend($todayStats['qty'], $yesterdayQty, 'qty'),
            ];
        }

        if ($user->hasPermission('customer-service.manage')) {
            $sheets = DB::table('customer_service_job_sheets')->where('created_by', $user->id)
                ->whereBetween('created_at', $this->range($today))->count();
            $forwarded = DB::table('order_status_notes')->where('user_id', $user->id)
                ->whereBetween('created_at', $this->range($today))
                ->where('stage', 'customer_service')->where('action', 'diteruskan_ke_kasir')->count();
            $sections[] = [
                'kind' => 'cs',
                'title' => 'Customer Service',
                'stats' => [
                    ['label' => 'lembar kerja', 'value' => $sheets],
                    ['label' => 'diteruskan ke kasir', 'value' => $forwarded],
                ],
                'note' => null,
                'trend' => null,
            ];
        }

        if ($user->hasPermission('kasir.manage')) {
            $payments = DB::table('order_payments')->where('user_id', $user->id)
                ->whereBetween('created_at', $this->range($today))
                ->where('jumlah', '>', 0)
                ->selectRaw('cara_bayar, COUNT(*) AS c, SUM(jumlah) AS total')->groupBy('cara_bayar')->get();
            $sections[] = [
                'kind' => 'kasir',
                'title' => 'Kasir',
                'stats' => [
                    ['label' => 'transaksi', 'value' => (int) $payments->sum('c')],
                    ['label' => 'uang masuk', 'value' => (float) $payments->sum('total'), 'money' => true],
                ],
                'methods' => $payments->sortByDesc('total')->map(fn ($row) => [
                    'label' => OrderPayment::CARA_BAYAR_LABELS[$row->cara_bayar] ?? ucfirst((string) $row->cara_bayar),
                    'total' => (float) $row->total,
                ])->values(),
                'note' => null,
                'trend' => null,
            ];
        }

        return $sections;
    }

    /** @return array{qty:int, items:int, orders:int, reworks:int} */
    private function operatorStats(int $userId, array $stages, CarbonImmutable $day): array
    {
        $row = DB::table('order_status_notes')->where('user_id', $userId)
            ->whereBetween('created_at', $this->range($day))
            ->whereIn('stage', $stages)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN action IN ('".implode("','", self::DONE_ACTIONS)."') THEN qty ELSE 0 END), 0) AS qty,
                COUNT(DISTINCT CASE WHEN action IN ('".implode("','", self::DONE_ACTIONS)."') THEN CONCAT(order_type, '-', COALESCE(order_detail_id, CONCAT('o', order_id))) END) AS items,
                COUNT(DISTINCT CASE WHEN action IN ('".implode("','", self::DONE_ACTIONS)."') THEN CONCAT(order_type, '-', order_id) END) AS orders,
                SUM(action = 'diulang') AS reworks
            ")->first();

        return [
            'qty' => (int) $row->qty,
            'items' => (int) $row->items,
            'orders' => (int) $row->orders,
            'reworks' => (int) $row->reworks,
        ];
    }

    private function trend(int $today, int $yesterday, string $unit): ?array
    {
        if ($today === 0 && $yesterday === 0) {
            return null;
        }
        $diff = $today - $yesterday;

        return [
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'same'),
            'text' => match (true) {
                $diff > 0 => "↑ {$diff} {$unit} dari kemarin 💪",
                $diff < 0 => '↓ '.abs($diff)." {$unit} dari kemarin",
                default => 'Sama dengan kemarin',
            },
        ];
    }

    private function range(CarbonImmutable $day): array
    {
        return [$day->startOfDay(), $day->endOfDay()];
    }
}
