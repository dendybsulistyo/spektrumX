<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the single `stage` + `qty_diproses` columns (one active
     * stage per item, whole line moves together once its qty is fully
     * progressed) with one qty bucket PER STAGE, always summing to Qty.
     * This lets qty flow forward as soon as any portion is done at a
     * stage — e.g. Qty=5, 2 already at Cetak, 3 still at Desain — instead
     * of the whole line waiting for all 5 before anything moves.
     */
    private const STAGE_ORDER = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil', 'selesai'];

    private const ACTIVE_STAGES = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil'];

    private const NEXT_STAGE = [
        'desain' => 'cetak', 'cetak' => 'finishing', 'finishing' => 'qc',
        'qc' => 'bungkus', 'bungkus' => 'siap_diambil', 'siap_diambil' => 'selesai',
    ];

    private const PRE_PRODUCTION = ['baru', 'dibayar', 'batal'];

    public function up(): void
    {
        $this->addColumns('order_indoor_detail');
        $this->addColumns('order_outdoor_detail');
        $this->addColumns('order_artwork_detail');

        // Outdoor already had genuine per-item partial progress live since
        // this feature's Phase 2 (real qty_diproses entered via the UI) —
        // split it across the current bucket and the next one. Indoor and
        // Artwork's qty_diproses was only ever a Phase-1 backfill
        // placeholder (always ==Qty) or a full-line "Tuntas" click reset —
        // never a genuine partial value — so their whole remaining qty
        // stays parked at whatever `stage` they're currently marked at,
        // nothing pre-flows.
        $this->backfill('order_indoor_detail', 'order_indoor', 'order_indoor_id', splitByProgress: false);
        $this->backfill('order_outdoor_detail', 'order_outdoor', 'order_outdoor_id', splitByProgress: true);
        $this->backfill('order_artwork_detail', 'order_artwork', 'order_artwork_id', splitByProgress: false);

        $this->dropOldColumns('order_indoor_detail');
        $this->dropOldColumns('order_outdoor_detail');
        $this->dropOldColumns('order_artwork_detail');
    }

    private function addColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $t) {
            foreach (self::STAGE_ORDER as $stage) {
                $t->unsignedInteger("qty_{$stage}")->default(0);
            }
        });
    }

    private function backfill(string $table, string $headerTable, string $fk, bool $splitByProgress): void
    {
        $preProd = "'".implode("','", self::PRE_PRODUCTION)."'";

        DB::statement("
            UPDATE {$table} d
            JOIN {$headerTable} o ON o.id = d.{$fk}
            SET d.qty_desain = d.Qty
            WHERE o.status IN ({$preProd})
        ");

        DB::statement("
            UPDATE {$table} d
            JOIN {$headerTable} o ON o.id = d.{$fk}
            SET d.qty_selesai = d.Qty
            WHERE o.status = 'selesai'
        ");

        foreach (self::ACTIVE_STAGES as $stage) {
            $nextStage = self::NEXT_STAGE[$stage];

            if ($splitByProgress) {
                DB::statement("
                    UPDATE {$table} d
                    JOIN {$headerTable} o ON o.id = d.{$fk}
                    SET d.qty_{$stage} = d.Qty - d.qty_diproses,
                        d.qty_{$nextStage} = d.qty_diproses
                    WHERE o.status NOT IN ({$preProd}, 'selesai') AND d.stage = '{$stage}'
                ");
            } else {
                DB::statement("
                    UPDATE {$table} d
                    JOIN {$headerTable} o ON o.id = d.{$fk}
                    SET d.qty_{$stage} = d.Qty
                    WHERE o.status NOT IN ({$preProd}, 'selesai') AND d.stage = '{$stage}'
                ");
            }
        }
    }

    private function dropOldColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $t) {
            $t->dropColumn(['stage', 'qty_diproses']);
        });
    }

    public function down(): void
    {
        foreach (['order_indoor_detail', 'order_outdoor_detail', 'order_artwork_detail'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('stage', 20)->default('desain');
                $t->unsignedInteger('qty_diproses')->default(0);
            });

            DB::statement("UPDATE {$table} SET stage = 'desain', qty_diproses = 0");

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn([
                    'qty_desain', 'qty_cetak', 'qty_finishing', 'qty_qc',
                    'qty_bungkus', 'qty_siap_diambil', 'qty_selesai',
                ]);
            });
        }
    }
};
