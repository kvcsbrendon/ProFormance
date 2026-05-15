<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Support\CardBrands;

class CardBrandsTest extends TestCase
{
    /** @test */
    public function detects_visa() { $this->assertEquals('visa', CardBrands::detect('4111111111111111')); }

    /** @test */
    public function detects_mastercard_51() { $this->assertEquals('mastercard', CardBrands::detect('5111111111111118')); }

    /** @test */
    public function detects_mastercard_2221() { $this->assertEquals('mastercard', CardBrands::detect('2221001234567890')); }

    /** @test */
    public function detects_amex() { $this->assertEquals('amex', CardBrands::detect('371449635398431')); }

    /** @test */
    public function detects_discover() { $this->assertEquals('discover', CardBrands::detect('6011111111111117')); }

    /** @test */
    public function returns_null_for_empty() { $this->assertNull(CardBrands::detect('')); }

    /** @test */
    public function returns_null_for_unknown() { $this->assertNull(CardBrands::detect('9999999999999999')); }

    /** @test */
    public function strips_non_digits() { $this->assertEquals('visa', CardBrands::detect('4111-1111-1111-1111')); }

    /** @test */
    public function visa_is_supported() { $this->assertTrue(CardBrands::isSupported('visa')); }

    /** @test */
    public function mastercard_is_supported() { $this->assertTrue(CardBrands::isSupported('mastercard')); }

    /** @test */
    public function amex_is_supported() { $this->assertTrue(CardBrands::isSupported('amex')); }

    /** @test */
    public function discover_not_supported() { $this->assertFalse(CardBrands::isSupported('discover')); }

    /** @test */
    public function null_not_supported() { $this->assertFalse(CardBrands::isSupported(null)); }

    /** @test */
    public function visa_length_16() { $this->assertEquals(16, CardBrands::expectedLength('visa')); }

    /** @test */
    public function amex_length_15() { $this->assertEquals(15, CardBrands::expectedLength('amex')); }

    /** @test */
    public function visa_cvv_3() { $this->assertEquals(3, CardBrands::expectedCvvLength('visa')); }

    /** @test */
    public function amex_cvv_4() { $this->assertEquals(4, CardBrands::expectedCvvLength('amex')); }

    /** @test */
    public function unknown_length_null() { $this->assertNull(CardBrands::expectedLength('unknown')); }
}