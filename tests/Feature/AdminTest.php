<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LoginDetail;
use App\Models\Order;
use App\Models\Product;
use App\Models\Discount;
use App\Models\Brand;
use Illuminate\Support\Facades\Hash;

class AdminTest extends TestCase
{
    protected User $admin;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'User',
            'country_phone_code' => 44, 'phone_number' => '07700000000',
            'user_role' => 'admin', 'is_active' => true,
        ]);
        LoginDetail::create([
            'user_id' => $this->admin->user_id,
            'email_address' => 'admin-' . uniqid() . '@example.com',
            'password_hash' => Hash::make('AdminPass!99'),
            'password_salt' => '',
        ]);

        $this->customer = User::create([
            'first_name' => 'Customer', 'last_name' => 'User',
            'country_phone_code' => 44, 'phone_number' => '07700000001',
            'user_role' => 'customer', 'is_active' => true,
        ]);
    }

    // ─── ACCESS CONTROL ───

    /** @test */
    public function customer_cannot_access_admin()
    {
        $this->actingAs($this->customer)->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    /** @test */
    public function admin_dashboard_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    /** @test */
    public function guest_cannot_access_admin()
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    // ─── PRODUCTS ───

    /** @test */
    public function admin_products_index_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.products.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_create_product()
    {
        $brand = Brand::first() ?? Brand::create(['brand_name' => 'Test', 'slug' => 'test']);
        $this->actingAs($this->admin)->post(route('admin.products.store'), [
            'product_name' => 'Test Product PHPUnit',
            'brand_id' => $brand->brand_id,
            'is_active' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['product_name' => 'Test Product PHPUnit']);
    }

    /** @test */
    public function admin_toggle_product()
    {
        $product = Product::where('is_active', true)->first();
        if (!$product) {
            $this->markTestSkipped('No active product in DB');
        }
        $this->actingAs($this->admin)->post(route('admin.products.toggle', $product->product_id))
            ->assertRedirect();
    }

    // ─── ORDERS ───

    /** @test */
    public function admin_orders_index_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.orders.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_update_order_status()
    {
        $order = Order::first();
        if (!$order) {
            $this->markTestSkipped('No orders in DB');
        }
        $this->actingAs($this->admin)->post(route('admin.orders.updateStatus', $order->order_id), [
            'order_status' => 'Shipped',
        ])->assertRedirect();
    }

    // ─── BRANDS ───

    /** @test */
    public function admin_create_brand()
    {
        $this->actingAs($this->admin)->post(route('admin.brands.store'), [
            'brand_name' => 'PHPUnit Brand',
        ])->assertRedirect();
        $this->assertDatabaseHas('brands', ['brand_name' => 'PHPUnit Brand']);
    }

    // ─── DISCOUNTS ───

    /** @test */
    public function admin_discounts_index_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.discounts.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_create_discount()
    {
        $this->actingAs($this->admin)->post(route('admin.discounts.store'), [
            'discount_code' => 'PHPUNIT' . rand(100, 999),
            'discoun_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => 1,
        ])->assertRedirect();
    }

    /** @test */
    public function admin_toggle_discount()
    {
        $discount = Discount::first();
        if (!$discount) {
            $this->markTestSkipped('No discounts in DB');
        }
        $this->actingAs($this->admin)->post(route('admin.discounts.toggle', $discount->discount_id))
            ->assertRedirect();
    }

    // ─── SHIPPING ───

    /** @test */
    public function admin_shipping_index_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.shipping.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_create_shipping_rate()
    {
        $this->actingAs($this->admin)->post(route('admin.shipping.store'), [
            'zone_name' => 'Test Zone',
            'country_code' => 'ZZ',
            'method_key' => 'standard',
            'method_label' => 'Standard Test',
            'price_penny' => 999,
            'is_active' => 1,
            'sort_order' => 99,
        ])->assertRedirect();
    }

    // ─── CUSTOMERS ───

    /** @test */
    public function admin_customers_index_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.customers.index'))
            ->assertStatus(200);
    }

    /** @test */
    public function admin_toggle_customer()
    {
        $this->actingAs($this->admin)->post(route('admin.customers.toggle', $this->customer->user_id))
            ->assertRedirect();
    }

    // ─── ANALYTICS ───

    /** @test */
    public function admin_analytics_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.analytics.index'))
            ->assertStatus(200);
    }

    // ─── INVENTORY ───

    /** @test */
    public function admin_inventory_loads()
    {
        $this->actingAs($this->admin)->get(route('admin.inventory.index'))
            ->assertStatus(200);
    }
}