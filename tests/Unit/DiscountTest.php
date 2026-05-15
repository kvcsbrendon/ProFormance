<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Discount;

class DiscountTest extends TestCase
{

    // ─────────────────────────────────────────
    // PERCENTAGE DISCOUNT
    // ─────────────────────────────────────────

    /** @test */
    public function percentage_discount_calculates_correctly()
    {
        $discount = new Discount();
        $discount->discoun_type = 'percentage';
        $discount->discount_value = 10;

        // 10% of £100.00 (10000 penny) = £10.00 (1000 penny)
        $this->assertEquals(1000, $discount->calculatePenny(10000));
    }

    /** @test */
    public function percentage_discount_rounds_correctly()
    {
        $discount = new Discount();
        $discount->discoun_type = 'percentage';
        $discount->discount_value = 15;

        // 15% of £33.33 (3333 penny) = £5.00 (500 penny)
        $this->assertEquals(500, $discount->calculatePenny(3333));
    }

    // ─────────────────────────────────────────
    // FIXED AMOUNT DISCOUNT
    // ─────────────────────────────────────────

    /** @test */
    public function fixed_discount_calculates_correctly()
    {
        $discount = new Discount();
        $discount->discoun_type = 'fixed_amount';
        $discount->discount_value = 5.00;

        // £5.00 = 500 penny regardless of subtotal
        $this->assertEquals(500, $discount->calculatePenny(10000));
        $this->assertEquals(500, $discount->calculatePenny(50000));
    }

    // ─────────────────────────────────────────
    // UNKNOWN TYPE
    // ─────────────────────────────────────────

    /** @test */
    public function unknown_type_returns_zero()
    {
        $discount = new Discount();
        $discount->discoun_type = 'invalid';
        $discount->discount_value = 10;

        $this->assertEquals(0, $discount->calculatePenny(10000));
    }

    // ─────────────────────────────────────────
    // VALIDATION
    // ─────────────────────────────────────────

    /** @test */
    public function inactive_discount_returns_error()
    {
        $discount = new Discount();
        $discount->is_active = false;

        $result = $discount->isValid(10000, 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('no longer active', $result);
    }

    /** @test */
    public function expired_discount_returns_error()
    {
        $discount = new Discount();
        $discount->is_active = true;
        $discount->ends_at = now()->subDay();

        $result = $discount->isValid(10000, 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('expired', $result);
    }

    /** @test */
    public function future_discount_returns_error()
    {
        $discount = new Discount();
        $discount->is_active = true;
        $discount->starts_at = now()->addDay();

        $result = $discount->isValid(10000, 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('not yet active', $result);
    }

    /** @test */
    public function minimum_subtotal_enforced()
    {
        $discount = new Discount();
        $discount->is_active = true;
        $discount->min_subtotal_penny = 5000;

        // £30.00 subtotal, minimum £50.00
        $result = $discount->isValid(3000, 1);
        $this->assertIsString($result);
        $this->assertStringContainsString('Minimum order', $result);
    }

    /** @test */
    public function valid_discount_returns_true()
    {
        $discount = new Discount();
        $discount->is_active = true;
        $discount->starts_at = now()->subDay();
        $discount->ends_at = now()->addDay();
        $discount->min_subtotal_penny = null;
        $discount->usage_limit = null;
        $discount->per_user_limit = null;

        $result = $discount->isValid(10000, 1);
        $this->assertTrue($result);
    }
}
