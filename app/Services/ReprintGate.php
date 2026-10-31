<?php

namespace App\Services;

use App\Models\ReprintLog;
use App\Models\ReprintRequest;
use App\Models\User;
use App\Support\CetakUlang;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Aturan cetak ulang SO / DO / Invoice (printer macet, kertas sobek, dst.):
 * - dalam FREE_MINUTES sejak cetak pertama: boleh berkali-kali, alasan wajib;
 * - Admin Kasir / Akuntansi (CetakUlang::ROLES): kapan pun, alasan wajib;
 * - selain itu: lewat Approval (satu permintaan disetujui = satu kali cetak).
 * Setiap cetak ulang dicatat di reprint_logs dan diberi tanda di kaki nota.
 */
class ReprintGate
{
    public const FREE_MINUTES = 15;

    public const REASONS = [
        'Printer macet / kertas tersangkut',
        'Kertas habis / sobek',
        'Hasil cetak buram / terpotong',
    ];

    public const KIND_LABELS = ['so' => 'Sales Order', 'do' => 'Delivery Order', 'inv' => 'Invoice'];

    /**
     * Keadaan tombol cetak untuk halaman: first | free | privileged | approved | pending | needs_approval.
     *
     * @param  Model|null  $record  baris sales_order_prints / order_document_prints
     */
    public function state(?Model $record, string $kind, string $orderType, int $orderId, ?int $documentId = null, ?User $user = null): string
    {
        $user ??= auth()->user();
        if (! $record) {
            return 'first';
        }
        if (CetakUlang::boleh($user)) {
            return 'privileged';
        }
        if ($this->withinFreeWindow($record)) {
            return 'free';
        }
        $request = $this->openRequest($kind, $orderType, $orderId, $documentId);

        return match ($request?->status) {
            'approved' => 'approved',
            'pending' => 'pending',
            default => 'needs_approval',
        };
    }

    public function freeUntil(Model $record): Carbon
    {
        return $record->first_printed_at->copy()->addMinutes(self::FREE_MINUTES);
    }

    /**
     * Dipanggil di dalam transaksi registerPrint saat dokumen sudah pernah dicetak.
     * Mengembalikan null bila boleh (dan mencatat log), atau pesan penolakan.
     *
     * @return array{allowed: bool, message?: string, code?: string, stamp?: string}
     */
    public function authorizeReprint(Model $record, string $kind, string $orderType, int $orderId, ?int $documentId, string $documentNumber, ?string $reason): array
    {
        $reason = trim((string) $reason);
        if ($reason === '') {
            return ['allowed' => false, 'code' => 'reason_required', 'message' => 'Alasan cetak ulang wajib diisi.'];
        }

        $user = auth()->user();
        $request = null;
        if (CetakUlang::boleh($user)) {
            $mode = 'hak_khusus';
        } elseif ($this->withinFreeWindow($record)) {
            $mode = 'bebas';
        } else {
            $request = ReprintRequest::query()
                ->where('kind', $kind)->where('order_type', $orderType)->where('order_id', $orderId)
                ->where('order_document_id', $documentId)->where('status', 'approved')
                ->lockForUpdate()->oldest('id')->first();
            if (! $request) {
                return [
                    'allowed' => false,
                    'code' => 'needs_approval',
                    'message' => 'Batas cetak ulang '.self::FREE_MINUTES.' menit sudah lewat. Ajukan permintaan cetak ulang ke Approval.',
                ];
            }
            $request->update(['status' => 'used', 'used_at' => now()]);
            $mode = 'approval';
        }

        $printNo = (int) $record->print_count + 1;
        ReprintLog::create([
            'kind' => $kind,
            'order_type' => $orderType,
            'order_id' => $orderId,
            'order_document_id' => $documentId,
            'document_number' => $documentNumber,
            'print_no' => $printNo,
            'reason' => mb_substr($reason, 0, 255),
            'mode' => $mode,
            'reprint_request_id' => $request?->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        return ['allowed' => true, 'stamp' => $this->stamp($printNo)];
    }

    /** Tanda di kaki nota cetak ulang: "31/10 14:05 · Nama Petugas-3". */
    public function stamp(int $printNo): string
    {
        return now()->format('d/m H:i').' · '.auth()->user()->name.'-'.$printNo;
    }

    public function openRequest(string $kind, string $orderType, int $orderId, ?int $documentId): ?ReprintRequest
    {
        return ReprintRequest::query()
            ->where('kind', $kind)->where('order_type', $orderType)->where('order_id', $orderId)
            ->where('order_document_id', $documentId)->whereIn('status', ['pending', 'approved'])
            ->latest('id')->first();
    }

    public static function canApprove(?User $user): bool
    {
        return CetakUlang::boleh($user) || $user?->role?->name === 'admin';
    }

    public static function pendingCount(): int
    {
        return ReprintRequest::query()->where('status', 'pending')->count();
    }

    private function withinFreeWindow(Model $record): bool
    {
        return $record->first_printed_at && now()->lte($this->freeUntil($record));
    }
}
