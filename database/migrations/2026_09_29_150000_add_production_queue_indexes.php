<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STAGES = ['desain', 'cetak', 'finishing', 'qc', 'bungkus', 'siap_diambil'];

    public function up(): void
    {
        foreach ([
            'order_indoor_detail' => 'order_indoor_id',
            'order_outdoor_detail' => 'order_outdoor_id',
            'order_artwork_detail' => 'order_artwork_id',
        ] as $tableName => $orderColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $orderColumn): void {
                foreach (self::STAGES as $stage) {
                    $table->index(
                        ["qty_{$stage}", $orderColumn],
                        "{$tableName}_qty_{$stage}_queue_idx"
                    );
                }
            });
        }

        foreach (['order_indoor', 'order_outdoor'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->index(
                    ['status_bayar', 'payment_queue', 'TglOrder', 'NoOrder'],
                    "{$tableName}_cashier_queue_idx"
                );
            });
        }

        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->index(
                    ['status_bayar', 'jumlah_piutang', 'TglOrder'],
                    "{$tableName}_receivable_queue_idx"
                );
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'order_indoor_detail',
            'order_outdoor_detail',
            'order_artwork_detail',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                foreach (self::STAGES as $stage) {
                    $table->dropIndex("{$tableName}_qty_{$stage}_queue_idx");
                }
            });
        }

        foreach (['order_indoor', 'order_outdoor'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_cashier_queue_idx");
            });
        }

        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropIndex("{$tableName}_receivable_queue_idx");
            });
        }
    }
};
