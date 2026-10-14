<?php

namespace App\Services;

use App\Models\OrderArtwork;
use App\Models\OrderIndoor;
use App\Models\OrderOutdoor;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tracking order untuk customer: URL/QR di Sales Order dan pengiriman status
 * ke Worker `spektrum-tracking` (lihat config/tracking.php dan README Worker).
 */
class OrderTrackingService
{
    public const MODELS = [
        'indoor' => OrderIndoor::class,
        'outdoor' => OrderOutdoor::class,
        'artwork' => OrderArtwork::class,
    ];

    private const MAKS_PER_KIRIM = 500;

    /** Cap waktu tahap; yang paling akhir terisi = kapan status terakhir berubah. */
    private const KOLOM_WAKTU = ['created_at', 'dibayar_at', 'desain_at', 'cetak_at', 'finishing_at', 'qc_at', 'bungkus_at', 'diambil_at', 'cancel_approved_at'];

    public function aktif(): bool
    {
        return config('tracking.url') !== '' && config('tracking.secret') !== '';
    }

    public function url(Model $order): ?string
    {
        return $this->aktif() && $order->tracking_token
            ? config('tracking.url').'/o/'.$order->tracking_token
            : null;
    }

    /** SVG QR (tanpa margin; beri ruang kosong lewat CSS) untuk dicetak tajam di dot matrix. */
    public function qrSvg(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(256, 0), new SvgImageBackEnd));
        $svg = $writer->writeString($url, 'UTF-8', ErrorCorrectionLevel::M());

        $svg = preg_replace('/^<\?xml[^>]*>\s*/', '', $svg);

        return preg_replace('/<svg\b/', '<svg shape-rendering="crispEdges" role="img" aria-label="QR status order"', $svg, 1);
    }

    private function waktuStatus(Model $order): string
    {
        $waktu = collect(self::KOLOM_WAKTU)
            ->map(fn (string $kolom) => $order->getAttribute($kolom))
            ->filter()
            ->map(fn ($nilai) => \Illuminate\Support\Carbon::parse($nilai))
            ->max();

        return ($waktu ?? now())->toIso8601String();
    }

    /**
     * Kirim order yang statusnya berubah sejak pengiriman terakhir.
     *
     * @return array{dikirim: int, ditolak: int, gagal: bool}
     */
    public function kirimPerubahan(): array
    {
        $hasil = ['dikirim' => 0, 'ditolak' => 0, 'gagal' => false];
        if (! $this->aktif()) {
            return $hasil;
        }

        $antrian = collect(self::MODELS)->flatMap(fn (string $model, string $type) => $model::query()
            ->whereNotNull('tracking_token')
            ->where(fn ($q) => $q->whereNull('tracking_pushed_status')->orWhereColumn('tracking_pushed_status', '!=', 'status'))
            ->orderBy('id')
            ->limit(self::MAKS_PER_KIRIM)
            ->get(['id', 'NoOrder', 'status', 'tracking_token', ...self::KOLOM_WAKTU])
            ->map(fn ($order) => [$type, $order]))
            ->take(self::MAKS_PER_KIRIM)
            ->values();

        if ($antrian->isEmpty()) {
            return $hasil;
        }

        $orders = $antrian->map(fn ($row) => [
            'token' => $row[1]->tracking_token,
            'order_number' => $row[1]->NoOrder,
            'status' => config('tracking.tahap')[$row[1]->status] ?? null,
            'status_at' => $this->waktuStatus($row[1]),
        ]);

        // Status yang belum punya padanan tahap tidak dikirim (tetap ditandai agar tidak diulang).
        $kirim = $orders->filter(fn ($o) => $o['status'] !== null)->values();

        if ($kirim->isNotEmpty()) {
            $body = json_encode(['orders' => $kirim->all()], JSON_UNESCAPED_SLASHES);
            $timestamp = (string) now()->timestamp;

            try {
                $response = Http::timeout(15)
                    ->withHeaders([
                        'X-Spektrum-Timestamp' => $timestamp,
                        'X-Spektrum-Signature' => hash_hmac('sha256', $timestamp.'.'.$body, config('tracking.secret')),
                    ])
                    ->withBody($body, 'application/json')
                    ->post(config('tracking.url').'/api/v1/status');
            } catch (Throwable $e) {
                Log::warning('Tracking: gagal menghubungi Worker', ['error' => $e->getMessage()]);

                return ['gagal' => true] + $hasil;
            }

            if (! $response->successful() && $response->status() !== 422) {
                Log::warning('Tracking: Worker menolak kiriman', ['status' => $response->status(), 'body' => $response->body()]);

                return ['gagal' => true] + $hasil;
            }

            $hasil['ditolak'] = count($response->json('ditolak') ?? []);
            $hasil['dikirim'] = (int) $response->json('diterima', 0);
        }

        // Tandai terkirim hanya bila statusnya belum berubah lagi sejak dibaca.
        foreach ($antrian as [$type, $order]) {
            // toBase(): jangan ikut mengubah updated_at order.
            self::MODELS[$type]::query()
                ->whereKey($order->id)
                ->where('status', $order->status)
                ->toBase()
                ->update(['tracking_pushed_status' => $order->status, 'tracking_pushed_at' => now()]);
        }

        return $hasil;
    }
}
