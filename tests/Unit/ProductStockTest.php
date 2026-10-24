<?php

namespace Tests\Unit;

use App\Models\Product;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    /**
     * Test product stock sufficiency check.
     */
    public function test_product_has_sufficient_stock_check(): void
    {
        $product = new Product([
            'id' => 1,
            'name' => 'Burger Combo',
            'available_stock' => 10,
        ]);

        $this->assertTrue($product->hasSufficientStock(5));
        $this->assertTrue($product->hasSufficientStock(10));
        $this->assertFalse($product->hasSufficientStock(11));
        $this->assertFalse($product->hasSufficientStock(9999));
    }
}
