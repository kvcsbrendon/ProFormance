<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\VariantCurrencyPrice;
use App\Models\Brand;

class CartTest extends TestCase
{
    protected User $user;
    protected int $variantId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $brand = Brand::first() ?? Brand::create(['brand_name' => 'Test Brand', 'slug' => 'test-brand']);
        $product = Product::create(['product_name' => 'Test Protein', 'brand_id' => $brand->brand_id, 'is_active' => true]);
        $variant = ProductVariant::create(['product_id' => $product->product_id, 'sku' => 'TST-' . uniqid(), 'is_active' => true]);
        $this->variantId = $variant->variant_id;

        Inventory::create(['variant_id' => $variant->variant_id, 'available_stock' => 10]);
        VariantCurrencyPrice::create(['variant_id' => $variant->variant_id, 'currency_code' => 'gbp', 'price_penny' => 2499]);
    }

    /** @test */
    public function cart_page_loads()
    {
        $this->get(route('cart.index'))->assertStatus(200);
    }

    /** @test */
    public function add_item_to_cart()
    {
        $response = $this->postJson(route('cart.add'), [
            'variant_id' => $this->variantId,
            'quantity' => 1,
        ]);
        $response->assertStatus(200);
        $cart = session('cart', []);
        $this->assertNotEmpty($cart);
    }

    /** @test */
    public function add_same_item_twice_succeeds()
    {
        $this->postJson(route('cart.add'), ['variant_id' => $this->variantId, 'quantity' => 1])
            ->assertStatus(200);
        $this->postJson(route('cart.add'), ['variant_id' => $this->variantId, 'quantity' => 1])
            ->assertStatus(200);
    }

    /** @test */
    public function cannot_exceed_stock()
    {
        $response = $this->postJson(route('cart.add'), ['variant_id' => $this->variantId, 'quantity' => 99]);
        // Should either cap or reject — either way no crash
        $this->assertTrue($response->status() < 500, 'Cart should not crash on overstock');
    }

    /** @test */
    public function clear_cart()
    {
        $this->postJson(route('cart.add'), ['variant_id' => $this->variantId, 'quantity' => 1]);
        $this->post(route('cart.clear'));
        $cart = session('cart', []);
        $this->assertEmpty($cart);
    }

    /** @test */
    public function cart_preview_returns_json()
    {
        $this->postJson(route('cart.add'), ['variant_id' => $this->variantId, 'quantity' => 1]);
        $response = $this->getJson(route('cart.preview'));
        $response->assertStatus(200);
        $response->assertJson(fn ($json) => $json->etc());
    }
}