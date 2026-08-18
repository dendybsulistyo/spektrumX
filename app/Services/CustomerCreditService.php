<?php

namespace App\Services;

use App\Models\Customer;

class CustomerCreditService
{
    public function addHutang(Customer $customer, float $amount): void
    {
        $customer->limit->increment('Total', $amount);
    }

    /**
     * Releases hutang tied to an order that got cancelled — no cash moved
     * (nothing was ever received on it), so this only frees up the
     * customer's credit limit rather than posting an accounting refund.
     */
    public function reduceHutang(Customer $customer, float $amount): void
    {
        $customer->limit->decrement('Total', min($amount, $customer->limit->Total));
    }
}
