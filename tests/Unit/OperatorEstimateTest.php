<?php

namespace Tests\Unit;

use App\Models\CustomerServiceJobSheet;
use App\Models\OrderIndoor;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** Batas estimasi = sejak pertama diproses kasir + Progress Hari CS (default 1 hari); keputusan user 3 Nov 2026. */
class OperatorEstimateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cs_progress_days_count_from_cashier_time(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $sheet = new CustomerServiceJobSheet(['progress_hari' => 2]);
        $sheet->created_at = Carbon::parse('2026-10-18 08:00:00');

        $order = $this->order('2026-10-18 07:00:00', '2026-10-19 09:30:00');
        $order->setRelation('customerServiceJobSheet', $sheet);

        $this->assertSame('2026-10-21 09:30:00', $order->operatorEstimateEndsAt()?->format('Y-m-d H:i:s'));
    }

    public function test_without_cs_estimate_gets_one_day_from_cashier_time(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $order = $this->order('2026-10-18 08:00:00', '2026-10-19 14:28:00');

        $this->assertSame('2026-10-20 14:28:00', $order->operatorEstimateEndsAt()?->format('Y-m-d H:i:s'));
    }

    public function test_today_order_gets_a_deadline_right_after_cashier(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $order = $this->order('2026-10-20 08:00:00', '2026-10-20 08:15:00');

        $this->assertSame('2026-10-21 08:15:00', $order->operatorEstimateEndsAt()?->format('Y-m-d H:i:s'));
    }

    public function test_unpaid_order_has_no_deadline(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $order = $this->order('2026-10-18 08:00:00', null);

        $this->assertNull($order->operatorEstimateEndsAt());
    }

    private function order(string $createdAt, ?string $diprosesKasirAt): OrderIndoor
    {
        $order = new OrderIndoor;
        $order->created_at = Carbon::parse($createdAt);
        $order->diproses_kasir_at = $diprosesKasirAt ? Carbon::parse($diprosesKasirAt) : null;
        $order->setRelation('customerServiceJobSheet', null);

        return $order;
    }
}
