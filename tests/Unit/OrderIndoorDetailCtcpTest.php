<?php

namespace Tests\Unit;

use App\Models\OrderIndoorDetail;
use App\Models\Produk;
use Tests\TestCase;

class OrderIndoorDetailCtcpTest extends TestCase
{
    public function test_product_in_division_nine_is_ctcp(): void
    {
        $item = new OrderIndoorDetail(['KdProd' => 'XX01']);
        $item->setRelation('produk', new Produk(['KdDivs' => '09']));

        $this->assertTrue($item->isCtcp());
    }

    public function test_unpadded_division_nine_is_ctcp(): void
    {
        $item = new OrderIndoorDetail(['KdProd' => 'XX02']);
        $item->setRelation('produk', new Produk(['KdDivs' => '9']));

        $this->assertTrue($item->isCtcp());
    }

    public function test_legacy_product_code_starting_with_nine_is_ctcp(): void
    {
        $item = new OrderIndoorDetail(['KdProd' => '0901']);
        $item->setRelation('produk', null);

        $this->assertTrue($item->isCtcp());
    }

    public function test_other_indoor_product_is_not_ctcp(): void
    {
        $item = new OrderIndoorDetail(['KdProd' => '0301']);
        $product = new Produk(['KdDivs' => '03']);
        $product->setRelation('kategori', null);
        $item->setRelation('produk', $product);

        $this->assertFalse($item->isCtcp());
    }
}
