<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ShippingRate;

class ShippingRateTest extends TestCase
{
    /** @test */
    public function gb_standard_rate_exists()
    {
        $rate = ShippingRate::getPenny('GB', 'standard');
        $this->assertGreaterThan(0, $rate);
    }

    /** @test */
    public function gb_next_day_costs_more_than_standard()
    {
        $standard = ShippingRate::getPenny('GB', 'standard');
        $nextDay = ShippingRate::getPenny('GB', 'next_day');
        $this->assertGreaterThan($standard, $nextDay);
    }

    /** @test */
    public function gb_same_day_costs_more_than_next_day()
    {
        $nextDay = ShippingRate::getPenny('GB', 'next_day');
        $sameDay = ShippingRate::getPenny('GB', 'same_day');
        $this->assertGreaterThanOrEqual($nextDay, $sameDay);
    }

    /** @test */
    public function unknown_country_returns_a_rate()
    {
        $rate = ShippingRate::getPenny('ZZ', 'standard');
        $this->assertGreaterThan(0, $rate);
    }

    /** @test */
    public function country_code_is_case_insensitive()
    {
        $upper = ShippingRate::getPenny('GB', 'standard');
        $lower = ShippingRate::getPenny('gb', 'standard');
        $this->assertEquals($upper, $lower);
    }

    /** @test */
    public function methods_for_gb_returns_multiple()
    {
        $methods = ShippingRate::methodsFor('GB');
        $this->assertGreaterThanOrEqual(1, $methods->count());
    }

    /** @test */
    public function methods_for_unknown_country_returns_fallback()
    {
        $methods = ShippingRate::methodsFor('ZZ');
        $this->assertGreaterThanOrEqual(1, $methods->count());
    }

    /** @test */
    public function methods_have_required_fields()
    {
        $methods = ShippingRate::methodsFor('GB');
        $first = $methods->first();
        $this->assertNotNull($first->method_key);
        $this->assertNotNull($first->method_label);
        $this->assertNotNull($first->price_penny);
    }
}