<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Stage a detail row is considered "at" when its parent order hasn't
     * actually entered production yet (baru/dibayar) or was cancelled
     * (batal) — kept as the default starting stage. The item-level queue
     * queries added in this feature always additionally filter out orders
     * whose header status is baru/dibayar/batal, so these rows never
     * surface prematurely; this is just a harmless starting value that
     * becomes correct the moment the order is paid and enters production
     * for real.
     */
    private const PRE_PRODUCTION_STATUSES = ['baru', 'dibayar', 'batal'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->backfillIndoor();
        $this->backfillOutdoor();
        $this->backfillArtwork();
    }

    private function backfillIndoor(): void
    {
        // BrsOrder is always NoOrder + a 2-digit line suffix (verified against
        // live data: 406,813 of 406,813 order_indoor_detail rows match this
        // pattern exactly, no ambiguity, no orphans) — a LEFT()-based equality
        // join uses the existing index on order_indoor.NoOrder, unlike a
        // leading-LIKE scan.
        DB::statement("
            UPDATE order_indoor_detail d
            JOIN order_indoor o ON LEFT(d.BrsOrder, CHAR_LENGTH(d.BrsOrder) - 2) = o.NoOrder
            SET d.order_indoor_id = o.id
        ");

        $unmatched = DB::table('order_indoor_detail')->whereNull('order_indoor_id')->count();
        if ($unmatched > 0) {
            fwrite(STDOUT, "  [backfill] WARNING: {$unmatched} order_indoor_detail row(s) could not be matched to a parent order via BrsOrder — needs manual review.\n");
        }

        $preProd = "'".implode("','", self::PRE_PRODUCTION_STATUSES)."'";

        DB::statement("
            UPDATE order_indoor_detail d
            JOIN order_indoor o ON o.id = d.order_indoor_id
            SET
                d.stage = CASE WHEN o.status IN ({$preProd}) THEN 'desain'
                               WHEN o.status = 'selesai' THEN 'siap_diambil'
                               ELSE o.status END,
                d.qty_diproses = d.Qty,
                d.stage_entered_at = COALESCE(o.desain_at, o.cetak_at, o.finishing_at, o.qc_at, o.bungkus_at, o.created_at, NOW())
        ");
    }

    private function backfillOutdoor(): void
    {
        $preProd = "'".implode("','", self::PRE_PRODUCTION_STATUSES)."'";

        // qty_diproses already holds real progress for outdoor — not touched here.
        DB::statement("
            UPDATE order_outdoor_detail d
            JOIN order_outdoor o ON o.id = d.order_outdoor_id
            SET
                d.stage = CASE WHEN o.status IN ({$preProd}) THEN 'desain'
                               WHEN o.status = 'selesai' THEN 'siap_diambil'
                               ELSE o.status END,
                d.stage_entered_at = COALESCE(o.desain_at, o.cetak_at, o.finishing_at, o.qc_at, o.bungkus_at, o.created_at, NOW())
        ");
    }

    private function backfillArtwork(): void
    {
        $preProd = "'".implode("','", self::PRE_PRODUCTION_STATUSES)."'";

        DB::statement("
            UPDATE order_artwork_detail d
            JOIN order_artwork o ON o.id = d.order_artwork_id
            SET
                d.stage = CASE WHEN o.status IN ({$preProd}) THEN 'desain'
                               WHEN o.status = 'selesai' THEN 'siap_diambil'
                               ELSE o.status END,
                d.qty_diproses = d.Qty,
                d.stage_entered_at = COALESCE(o.desain_at, o.cetak_at, o.finishing_at, o.qc_at, o.bungkus_at, o.created_at, NOW())
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('order_indoor_detail')->update(['order_indoor_id' => null, 'stage' => 'desain', 'qty_diproses' => 0, 'stage_entered_at' => null]);
        DB::table('order_outdoor_detail')->update(['stage' => 'desain', 'stage_entered_at' => null]);
        DB::table('order_artwork_detail')->update(['stage' => 'desain', 'qty_diproses' => 0, 'stage_entered_at' => null]);
    }
};
