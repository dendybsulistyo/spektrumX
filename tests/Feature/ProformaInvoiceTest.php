<?php

namespace Tests\Feature;

use App\Http\Controllers\ProformaInvoiceController;
use App\Models\Customer;
use App\Models\CustomerLimit;
use App\Models\OrderOutdoor;
use Tests\TestCase;

class ProformaInvoiceTest extends TestCase
{
    public function test_available_only_for_unpaid_vip_credit_orders(): void
    {
        $this->assertTrue(ProformaInvoiceController::isAvailableFor($this->order()));

        $this->assertFalse(ProformaInvoiceController::isAvailableFor($this->order(vip: false)));
        $this->assertFalse(ProformaInvoiceController::isAvailableFor($this->order(['status_bayar' => 'lunas'])));
        $this->assertFalse(ProformaInvoiceController::isAvailableFor($this->order(['jumlah_piutang' => 0])));
        $this->assertFalse(ProformaInvoiceController::isAvailableFor($this->order(['status' => 'batal'])));
        $this->assertFalse(ProformaInvoiceController::isAvailableFor($this->order(['invoice_voided_at' => now()])));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes = [], bool $vip = true): OrderOutdoor
    {
        $customer = new Customer(['KdCust' => 'EUR001']);
        $customer->setRelation('limit', $vip ? new CustomerLimit(['Batas' => 50000000]) : null);

        $order = new OrderOutdoor;
        $order->forceFill($attributes + ['status' => 'siap_diambil', 'status_bayar' => 'hutang', 'jumlah_piutang' => 385200, 'invoice_voided_at' => null]);
        $order->setRelation('customer', $customer);

        return $order;
    }
}
