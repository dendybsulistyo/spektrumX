<?php

namespace Tests\Feature;

use App\Models\OrderIndoor;
use App\Models\OrderIndoorDetail;
use Illuminate\Support\Collection;
use Tests\TestCase;

class LewatiLayoutCetakTest extends TestCase
{
    public function test_order_with_only_finishing_items_moves_past_layout_after_payment(): void
    {
        $order = $this->order([['qty_finishing' => 2]]);

        $order->skipToFirstActiveStage();

        $this->assertSame('finishing', $order->status);
    }

    public function test_mixed_order_stays_in_layout_while_a_banner_still_needs_it(): void
    {
        $order = $this->order([['qty_finishing' => 1], ['qty_desain' => 1]]);

        $order->skipToFirstActiveStage();

        $this->assertSame('desain', $order->status);
    }

    public function test_order_that_is_not_in_layout_is_left_alone(): void
    {
        $order = $this->order([['qty_finishing' => 1]], 'dibayar');

        $order->skipToFirstActiveStage();

        $this->assertSame('dibayar', $order->status);
    }

    /**
     * @param  array<int, array<string, int>>  $items
     */
    private function order(array $items, string $status = 'desain'): OrderIndoor
    {
        $details = collect($items)->map(fn (array $qty) => (new OrderIndoorDetail)->forceFill($qty + [
            'qty_desain' => 0, 'qty_cetak' => 0, 'qty_finishing' => 0, 'qty_qc' => 0,
            'qty_bungkus' => 0, 'qty_siap_diambil' => 0, 'qty_selesai' => 0,
        ]));

        $order = new class extends OrderIndoor
        {
            public Collection $fakeItems;

            public function detailItems(): Collection
            {
                return $this->fakeItems;
            }

            public function update(array $attributes = [], array $options = []): bool
            {
                $this->forceFill($attributes);

                return true;
            }
        };
        $order->fakeItems = $details;
        $order->forceFill(['status' => $status]);

        return $order;
    }
}
