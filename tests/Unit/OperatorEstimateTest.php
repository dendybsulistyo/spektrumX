<?php

namespace Tests\Unit;

use App\Models\CustomerServiceJobSheet;
use App\Models\OrderIndoor;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperatorEstimateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cs_estimate_takes_priority(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $sheet = new CustomerServiceJobSheet(['progress_hari' => 2]);
        $sheet->created_at = Carbon::parse('2026-10-19 08:00:00');

        $order = new OrderIndoor;
        $order->created_at = Carbon::parse('2026-10-18 08:00:00');
        $order->setRelation('customerServiceJobSheet', $sheet);

        $this->assertSame('2026-10-21 08:00:00', $order->operatorEstimateEndsAt()?->format('Y-m-d H:i:s'));
    }

    public function test_old_order_without_cs_estimate_gets_one_day(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $order = new OrderIndoor;
        $order->created_at = Carbon::parse('2026-10-18 08:00:00');
        $order->setRelation('customerServiceJobSheet', null);

        $this->assertSame('2026-10-19 08:00:00', $order->operatorEstimateEndsAt()?->format('Y-m-d H:i:s'));
    }

    public function test_today_order_without_cs_estimate_has_no_fallback(): void
    {
        Carbon::setTestNow('2026-10-20 10:00:00');
        $order = new OrderIndoor;
        $order->created_at = Carbon::parse('2026-10-20 08:00:00');
        $order->setRelation('customerServiceJobSheet', null);

        $this->assertNull($order->operatorEstimateEndsAt());
    }
}
