<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Checkout\PricingService;
use App\Models\TaxProfile;

class PricingServiceTest extends TestCase
{
    protected PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new PricingService();
    }

    // ─────────────────────────────────────────
    // VAT CALCULATIONS
    // ─────────────────────────────────────────

    /** @test */
    public function gb_country_returns_20_percent_vat()
    {
        $rate = $this->pricing->vatRateForCountry('GB');
        $this->assertEquals(0.20, $rate);
    }

    /** @test */
    public function non_gb_country_returns_zero_vat()
    {
        $this->assertEquals(0.00, $this->pricing->vatRateForCountry('US'));
        $this->assertEquals(0.00, $this->pricing->vatRateForCountry('DE'));
        $this->assertEquals(0.00, $this->pricing->vatRateForCountry('FR'));
    }

    /** @test */
    public function country_code_is_case_insensitive()
    {
        $this->assertEquals(0.20, $this->pricing->vatRateForCountry('gb'));
        $this->assertEquals(0.20, $this->pricing->vatRateForCountry('Gb'));
    }

    /** @test */
    public function tax_exempt_user_gets_zero_vat()
    {
        $taxProfile = new TaxProfile();
        $taxProfile->tax_exempt = 1;

        $rate = $this->pricing->vatRateForCountry('GB', $taxProfile);
        $this->assertEquals(0.00, $rate);
    }

    // ─────────────────────────────────────────
    // PRICE EXTRACTION
    // ─────────────────────────────────────────

    /** @test */
    public function extracts_base_price_from_vat_inclusive()
    {
        // £12.00 inc VAT = £10.00 ex VAT
        $base = $this->pricing->extractBasePrice(12.00);
        $this->assertEquals(10.00, round($base, 2));
    }

    /** @test */
    public function zero_price_returns_zero()
    {
        $this->assertEquals(0.00, $this->pricing->extractBasePrice(0));
    }

    // ─────────────────────────────────────────
    // GIFT DETECTION
    // ─────────────────────────────────────────

    /** @test */
    public function empty_cart_returns_no_gifts()
    {
        $result = $this->pricing->detectGifts([], []);
        $this->assertEmpty($result['giftItems']);
        $this->assertEmpty($result['nonGiftItems']);
        $this->assertFalse($result['isGift']);
        $this->assertFalse($result['hasMixedCart']);
    }

    /** @test */
    public function cart_with_no_gift_orders_is_non_gift()
    {
        $cart = [
            ['variant_id' => 1, 'name' => 'Whey Protein', 'quantity' => 2],
        ];
        $result = $this->pricing->detectGifts($cart, []);

        $this->assertEmpty($result['giftItems']);
        $this->assertCount(1, $result['nonGiftItems']);
        $this->assertFalse($result['isGift']);
    }

    /** @test */
    public function cart_with_all_gift_items_is_gift_only()
    {
        $cart = [
            ['variant_id' => 5, 'name' => 'Yoga Mat', 'quantity' => 1],
        ];
        $giftOrders = [
            '5' => ['country_code' => 'GB', 'recipient_name' => 'Jane'],
        ];
        $result = $this->pricing->detectGifts($cart, $giftOrders);

        $this->assertCount(1, $result['giftItems']);
        $this->assertEmpty($result['nonGiftItems']);
        $this->assertTrue($result['isGift']);
        $this->assertFalse($result['hasMixedCart']);
    }

    /** @test */
    public function mixed_cart_detected_correctly()
    {
        $cart = [
            ['variant_id' => 1, 'name' => 'Dumbbell', 'quantity' => 1],
            ['variant_id' => 5, 'name' => 'Yoga Mat', 'quantity' => 1],
        ];
        $giftOrders = [
            '5' => ['country_code' => 'US', 'recipient_name' => 'Jane'],
        ];
        $result = $this->pricing->detectGifts($cart, $giftOrders);

        $this->assertCount(1, $result['giftItems']);
        $this->assertCount(1, $result['nonGiftItems']);
        $this->assertFalse($result['isGift']);
        $this->assertTrue($result['hasMixedCart']);
    }

    // ─────────────────────────────────────────
    // DESTINATIONS
    // ─────────────────────────────────────────

    /** @test */
    public function non_gift_order_has_single_purchaser_destination()
    {
        $destinations = $this->pricing->buildDestinations([], [['variant_id' => 1]], [], 'GB');

        $this->assertCount(1, $destinations);
        $this->assertEquals('purchaser', $destinations[0]['type']);
        $this->assertEquals('GB', $destinations[0]['country']);
        $this->assertEquals(0.20, $destinations[0]['vat_rate']);
    }

    /** @test */
    public function gift_only_order_uses_recipient_country()
    {
        $giftItems = [['variant_id' => 5]];
        $giftOrders = ['5' => ['country_code' => 'US']];

        $destinations = $this->pricing->buildDestinations($giftItems, [], $giftOrders, 'GB');

        $this->assertCount(1, $destinations);
        $this->assertEquals('gift', $destinations[0]['type']);
        $this->assertEquals('US', $destinations[0]['country']);
        $this->assertEquals(0.00, $destinations[0]['vat_rate']);
    }

    /** @test */
    public function mixed_cart_has_multiple_destinations()
    {
        $giftItems = [['variant_id' => 5]];
        $nonGift = [['variant_id' => 1]];
        $giftOrders = ['5' => ['country_code' => 'DE']];

        $destinations = $this->pricing->buildDestinations($giftItems, $nonGift, $giftOrders, 'GB');

        $this->assertCount(2, $destinations);
        $this->assertEquals('purchaser', $destinations[0]['type']);
        $this->assertEquals('gift', $destinations[1]['type']);
    }

    // ─────────────────────────────────────────
    // SUBSCRIPTION & TOTAL
    // ─────────────────────────────────────────

    /** @test */
    public function free_shipping_only_applies_to_standard()
    {
        $result = $this->pricing->applySubscriptionAndTotal(
            subtotalExVatPenny: 5000,
            discountPenny: 0,
            shippingNetPenny: 1499,
            shippingVatPenny: 300,
            productVatPenny: 1000,
            subDiscountPenny: 0,
            subFreeShipping: true,
            ssDiscountPenny: 0,
            shippingMethod: 'next_day'
        );

        // Free shipping should NOT apply for next_day
        $this->assertEquals(1499, $result['shippingNetPenny']);
        $this->assertFalse($result['subFreeShipping']);
    }

    /** @test */
    public function free_shipping_applies_to_standard_method()
    {
        $result = $this->pricing->applySubscriptionAndTotal(
            subtotalExVatPenny: 5000,
            discountPenny: 0,
            shippingNetPenny: 399,
            shippingVatPenny: 80,
            productVatPenny: 1000,
            subDiscountPenny: 0,
            subFreeShipping: true,
            ssDiscountPenny: 0,
            shippingMethod: 'standard'
        );

        $this->assertEquals(0, $result['shippingNetPenny']);
        $this->assertTrue($result['subFreeShipping']);
    }

    /** @test */
    public function subscription_discount_reduces_total()
    {
        $result = $this->pricing->applySubscriptionAndTotal(
            subtotalExVatPenny: 10000,
            discountPenny: 0,
            shippingNetPenny: 399,
            shippingVatPenny: 80,
            productVatPenny: 2000,
            subDiscountPenny: 500,
            subFreeShipping: false,
            ssDiscountPenny: 0,
            shippingMethod: 'standard'
        );

        // Total = 10000 + 399 + 2080 - 500 = 11979
        $this->assertEquals(11979, $result['totalPenny']);
    }

    /** @test */
    public function ss_discount_reduces_total()
    {
        $result = $this->pricing->applySubscriptionAndTotal(
            subtotalExVatPenny: 10000,
            discountPenny: 0,
            shippingNetPenny: 399,
            shippingVatPenny: 80,
            productVatPenny: 2000,
            subDiscountPenny: 0,
            subFreeShipping: false,
            ssDiscountPenny: 300,
            shippingMethod: 'standard'
        );

        // Total = 10000 + 399 + 2080 - 300 = 12179
        $this->assertEquals(12179, $result['totalPenny']);
    }

    /** @test */
    public function original_shipping_preserved_when_free()
    {
        $result = $this->pricing->applySubscriptionAndTotal(
            subtotalExVatPenny: 5000,
            discountPenny: 0,
            shippingNetPenny: 399,
            shippingVatPenny: 80,
            productVatPenny: 1000,
            subDiscountPenny: 0,
            subFreeShipping: true,
            ssDiscountPenny: 0,
            shippingMethod: 'standard'
        );

        $this->assertEquals(399, $result['originalShippingNetPenny']);
        $this->assertEquals(0, $result['shippingNetPenny']);
    }
}