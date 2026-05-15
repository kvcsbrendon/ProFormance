<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Discount;
use App\Models\ShippingRate;

class CheckoutAjaxTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function totals_endpoint_requires_cart()
    {
        $this->actingAs($this->user)
            ->postJson(route('checkout.totals'), ['shipping_method' => 'standard'])
            ->assertJson(['ok' => false]);
    }

    /** @test */
    public function totals_returns_all_fields_with_cart()
    {
        session(['cart' => [
            ['variant_id' => 1, 'name' => 'Test', 'price' => 10.00, 'price_penny' => 1000, 'quantity' => 1, 'symbol' => '£', 'currency' => 'gbp'],
        ]]);

        $this->actingAs($this->user)
            ->postJson(route('checkout.totals'), [
                'shipping_method' => 'standard',
                'ship_country_code' => 'GB',
            ])
            ->assertJsonStructure([
                'ok', 'subtotalPenny', 'discountPenny', 'shippingNetPenny',
                'combinedVatPenny', 'vatRate', 'totalPenny',
                'subDiscountPenny', 'subFreeShipping',
                'ssDiscountPenny', 'countryCode', 'shippingMethods',
            ]);
    }

    /** @test */
    public function gb_returns_20_percent_vat()
    {
        session(['cart' => [
            ['variant_id' => 1, 'name' => 'Test', 'price' => 10.00, 'price_penny' => 1000, 'quantity' => 1, 'symbol' => '£', 'currency' => 'gbp'],
        ]]);

        $this->actingAs($this->user)
            ->postJson(route('checkout.totals'), ['shipping_method' => 'standard', 'ship_country_code' => 'GB'])
            ->assertJson(['vatRate' => 0.20]);
    }

    /** @test */
    public function us_returns_zero_vat()
    {
        session(['cart' => [
            ['variant_id' => 1, 'name' => 'Test', 'price' => 10.00, 'price_penny' => 1000, 'quantity' => 1, 'symbol' => '£', 'currency' => 'gbp'],
        ]]);

        $this->actingAs($this->user)
            ->postJson(route('checkout.totals'), ['shipping_method' => 'standard', 'ship_country_code' => 'US'])
            ->assertJson(['vatRate' => 0.00]);
    }

    /** @test */
    public function totals_returns_shipping_methods()
    {
        session(['cart' => [
            ['variant_id' => 1, 'name' => 'Test', 'price' => 10.00, 'price_penny' => 1000, 'quantity' => 1, 'symbol' => '£', 'currency' => 'gbp'],
        ]]);

        $data = $this->actingAs($this->user)
            ->postJson(route('checkout.totals'), ['shipping_method' => 'standard', 'ship_country_code' => 'GB'])
            ->json();

        $this->assertNotEmpty($data['shippingMethods']);
    }

    /** @test */
    public function apply_valid_discount()
    {
        $discount = Discount::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->first();

        if (!$discount) {
            $this->markTestSkipped('No active non-expired discounts in DB');
        }

        session(['cart' => [
            ['variant_id' => 1, 'price_penny' => 50000, 'quantity' => 1],
        ]]);

        $this->actingAs($this->user)
            ->postJson(route('checkout.discount.apply'), ['discount_code' => $discount->discount_code])
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function apply_invalid_discount()
    {
        $this->actingAs($this->user)
            ->postJson(route('checkout.discount.apply'), ['discount_code' => 'FAKECODE999'])
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function remove_discount_clears_session()
    {
        session(['checkout_discount' => ['discount_id' => 1, 'discount_code' => 'TEST']]);

        $this->actingAs($this->user)
            ->postJson(route('checkout.discount.remove'))
            ->assertJson(['success' => true]);

        $this->assertNull(session('checkout_discount'));
    }
}