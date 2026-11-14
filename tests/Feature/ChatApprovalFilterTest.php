<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatApprovalFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('roles', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->string('label')->nullable(), $t->text('permissions')->nullable(), $t->timestamps()]);
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email');
            $t->string('password')->nullable();
            $t->unsignedBigInteger('role_id')->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->timestamps();
        });
        foreach (['order_indoor', 'order_outdoor', 'order_artwork'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('NoOrder');
                foreach (['cancel_requested_at', 'cancel_approved_at', 'diskon_requested_at', 'diskon_approved_at', 'diskon_rejected_at', 'hutang_requested_at', 'hutang_approved_at', 'hutang_rejected_at'] as $column) {
                    $t->timestamp($column)->nullable();
                }
            });
        }
        (require base_path('database/migrations/2026_07_18_000001_create_chat_messages_table.php'))->up();
    }

    public function test_pengajuan_yang_sudah_diputuskan_disembunyikan_dan_pesan_terbaru_di_atas(): void
    {
        $approver = User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test']);
        $kasir = User::forceCreate(['name' => 'Brian', 'email' => 'brian@example.test']);

        DB::table('order_outdoor')->insert([
            ['NoOrder' => 'OUT.1.261106029', 'cancel_requested_at' => now(), 'cancel_approved_at' => null],
            ['NoOrder' => 'OUT.1.26110602', 'cancel_requested_at' => now(), 'cancel_approved_at' => now()],
        ]);
        DB::table('order_indoor')->insert(['NoOrder' => 'IND.2.261111031', 'hutang_requested_at' => now(), 'hutang_approved_at' => now()]);

        $send = fn (string $body, string $at) => ChatMessage::forceCreate(['sender_id' => $kasir->id, 'recipient_id' => $approver->id, 'body' => $body, 'created_at' => $at, 'updated_at' => $at]);
        $send('Halo bang', '2026-11-14 08:00:00');
        $send('Pengajuan pembatalan order outdoor OUT.1.26110602 (Surya). Menunggu persetujuan.', '2026-11-14 08:01:00');
        $send('Pengajuan hutang melebihi plafon untuk order IND.2.261111031 — total Rp 1.000. Menunggu persetujuan.', '2026-11-14 08:02:00');
        $send('Pengajuan pembatalan order outdoor OUT.1.261106029 (Rama). Menunggu persetujuan.', '2026-11-14 08:03:00');

        $this->actingAs($approver)->getJson(route('chat.unread-count'))->assertJson(['count' => 2]);

        $this->actingAs($approver)->getJson(route('chat.index'))
            ->assertJsonPath('0.unread_count', 2)
            ->assertJsonPath('0.last_message', 'Pengajuan pembatalan order outdoor OUT.1.261106029 (Rama). Menunggu persetujuan.');

        $bodies = $this->actingAs($approver)->getJson(route('chat.show', $kasir))->json('messages.*.body');

        $this->assertSame([
            'Pengajuan pembatalan order outdoor OUT.1.261106029 (Rama). Menunggu persetujuan.',
            'Halo bang',
        ], $bodies);
    }
}
