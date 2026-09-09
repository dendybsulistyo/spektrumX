<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The user confirmed that the invoice backfill completed before this
        // audit was imported for demo presentation. Never relabel later work.
        DB::transaction(function () {
            DB::table('order_documents')->where('kind', 'inv')->whereNull('issued_by')
                ->where('created_at', '<=', '2026-09-08 21:25:22')
                ->orderBy('id')->chunkById(250, function ($documents) {
                    foreach ($documents as $document) {
                        $snapshot = json_decode($document->snapshot, true, 512, JSON_THROW_ON_ERROR);
                        if (isset($snapshot['origin'])) {
                            continue;
                        }
                        $snapshot['origin'] = 'historical_demo';
                        $snapshot['origin_evidence'] = 'confirmed_demo_backfill_20260908';
                        DB::table('order_documents')->where('id', $document->id)->update(['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
                    }
                });
        });
    }

    public function down(): void
    {
        DB::table('order_documents')->where('snapshot->origin_evidence', 'confirmed_demo_backfill_20260908')
            ->orderBy('id')->chunkById(250, function ($documents) {
                foreach ($documents as $document) {
                    $snapshot = json_decode($document->snapshot, true, 512, JSON_THROW_ON_ERROR);
                    unset($snapshot['origin'], $snapshot['origin_evidence']);
                    DB::table('order_documents')->where('id', $document->id)->update(['snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR)]);
                }
            });
    }
};
