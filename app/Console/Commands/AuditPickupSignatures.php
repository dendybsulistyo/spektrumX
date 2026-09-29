<?php

namespace App\Console\Commands;

use App\Models\OrderDocument;
use App\Models\OrderPickupSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditPickupSignatures extends Command
{
    protected $signature = 'storage:audit-pickup-signatures';

    protected $description = 'Audit read-only integritas file tanda tangan pengambilan dan referensi dokumennya';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $records = OrderPickupSignature::query()->get([
            'id', 'order_type', 'order_id', 'signature_path', 'signature_hash',
        ]);

        $invalidPaths = $records->filter(
            fn (OrderPickupSignature $record) => ! str_starts_with($record->signature_path, 'pickup-signatures/')
        )->pluck('id')->values();

        $missingFiles = $records->filter(
            fn (OrderPickupSignature $record) => str_starts_with($record->signature_path, 'pickup-signatures/')
                && ! $disk->exists($record->signature_path)
        )->pluck('id')->values();

        $hashMismatches = $records->filter(function (OrderPickupSignature $record) use ($disk): bool {
            if (! str_starts_with($record->signature_path, 'pickup-signatures/') || ! $disk->exists($record->signature_path)) {
                return false;
            }

            $contents = $disk->get($record->signature_path);

            return ! is_string($contents)
                || ! hash_equals($record->signature_hash, hash('sha256', $contents));
        })->pluck('id')->values();

        $referencedPaths = $records->pluck('signature_path')->filter()->unique();
        $orphanFiles = collect($disk->allFiles('pickup-signatures'))
            ->diff($referencedPaths)
            ->values();

        $documentPaths = OrderDocument::query()
            ->where('kind', 'do')
            ->get(['id', 'snapshot'])
            ->mapWithKeys(fn (OrderDocument $document) => [$document->id => $document->snapshot['signature_path'] ?? null])
            ->filter();
        $documentsWithoutRecord = $documentPaths
            ->filter(fn (string $path) => ! $referencedPaths->contains($path))
            ->keys()
            ->values();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'records' => $records->count(),
            'files' => count($disk->allFiles('pickup-signatures')),
            'invalid_path_record_ids' => $invalidPaths,
            'missing_file_record_ids' => $missingFiles,
            'hash_mismatch_record_ids' => $hashMismatches,
            'document_ids_without_signature_record' => $documentsWithoutRecord,
            'orphan_files' => $orphanFiles,
        ];

        $path = storage_path('logs/pickup-signature-audit-'.now()->format('Ymd-His-u').'.json');
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->line(json_encode([
            'records' => $records->count(),
            'files' => $report['files'],
            'invalid_paths' => $invalidPaths->count(),
            'missing_files' => $missingFiles->count(),
            'hash_mismatches' => $hashMismatches->count(),
            'documents_without_record' => $documentsWithoutRecord->count(),
            'orphan_files' => $orphanFiles->count(),
            'report' => $path,
        ], JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
