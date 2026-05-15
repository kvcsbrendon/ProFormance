<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LoginDetail;
use App\Models\Address;
use App\Models\SavedCard;
use Illuminate\Support\Facades\Hash;

class AccountTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'first_name' => 'Test', 'last_name' => 'User',
            'country_phone_code' => 44, 'phone_number' => '07700000000',
            'user_role' => 'customer', 'is_active' => true,
        ]);
        LoginDetail::create([
            'user_id' => $this->user->user_id,
            'email_address' => 'accttest-' . uniqid() . '@example.com',
            'password_hash' => Hash::make('OldPass!99'),
            'password_salt' => '',
        ]);
    }

    // ─── DASHBOARD ───

    /** @test */
    public function dashboard_requires_auth()
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    /** @test */
    public function dashboard_loads()
    {
        $this->actingAs($this->user)->get(route('account.dashboard'))->assertStatus(200);
    }

    // ─── PROFILE ───

    /** @test */
    public function profile_page_loads()
    {
        $this->actingAs($this->user)->get(route('account.profile'))->assertStatus(200);
    }

    /** @test */
    public function update_profile()
    {
        $response = $this->actingAs($this->user)->post(route('account.profile.update'), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'phone_number' => '07700000001',
            'country_phone_code' => 44,
            'subscribed' => 0,
        ]);
        $response->assertRedirect();
        $this->user->refresh();
        $this->assertEquals('Updated', $this->user->first_name);
    }

    // ─── ADDRESSES ───

    /** @test */
    public function addresses_page_loads()
    {
        $this->actingAs($this->user)->get(route('account.addresses'))->assertStatus(200);
    }

    /** @test */
    public function create_address()
    {
        $this->actingAs($this->user)->post(route('account.addresses.store'), [
            'recipient_name' => 'John Doe',
            'house_number' => '42',
            'address_line_one' => 'Test Street',
            'city' => 'London',
            'postcode' => 'SW1A 1AA',
            'country_code' => 'GB',
            'country_phone_code' => 44,
        ])->assertRedirect();
        $this->assertDatabaseHas('addresses', ['recipient_name' => 'John Doe', 'user_id' => $this->user->user_id]);
    }

    /** @test */
    public function update_address()
    {
        $address = Address::create([
            'user_id' => $this->user->user_id,
            'recipient_name' => 'Old Name', 'house_number' => '1',
            'address_line_one' => 'Old Street', 'city' => 'London',
            'postcode' => 'SW1A 1AA', 'country_code' => 'GB', 'country_phone_code' => 44,
        ]);
        $this->actingAs($this->user)->put(route('account.addresses.update', $address->address_id), [
            'recipient_name' => 'New Name', 'house_number' => '2',
            'address_line_one' => 'New Street', 'city' => 'Manchester',
            'postcode' => 'M1 1AA', 'country_code' => 'GB', 'country_phone_code' => 44,
        ])->assertRedirect();
        $this->assertDatabaseHas('addresses', ['recipient_name' => 'New Name']);
    }

    /** @test */
    public function delete_address()
    {
        $address = Address::create([
            'user_id' => $this->user->user_id,
            'recipient_name' => 'Delete Me', 'house_number' => '1',
            'address_line_one' => 'Street', 'city' => 'London',
            'postcode' => 'SW1A 1AA', 'country_code' => 'GB', 'country_phone_code' => 44,
        ]);
        $this->actingAs($this->user)->delete(route('account.addresses.destroy', $address->address_id))
            ->assertRedirect();
        $this->assertDatabaseMissing('addresses', ['address_id' => $address->address_id]);
    }

    // ─── SAVED CARDS ───

    /** @test */
    public function saved_cards_page_loads()
    {
        $this->actingAs($this->user)->get(route('account.cards'))->assertStatus(200);
    }

    /** @test */
    public function add_saved_card()
    {
        $this->actingAs($this->user)->post(route('account.cards.store'), [
            'card_name' => 'Test User',
            'card_number' => '4111111111111111',
            'card_expiry' => now()->addYear()->format('m/y'),
            'card_cvv' => '123',
        ])->assertRedirect();
        $this->assertDatabaseHas('saved_cards', ['last_four' => '1111', 'user_id' => $this->user->user_id]);
    }

    /** @test */
    public function first_card_becomes_default()
    {
        $this->actingAs($this->user)->post(route('account.cards.store'), [
            'card_name' => 'Test', 'card_number' => '4111111111111111',
            'card_expiry' => now()->addYear()->format('m/y'), 'card_cvv' => '123',
        ]);
        $card = SavedCard::where('user_id', $this->user->user_id)->first();
        $this->assertTrue($card->is_default);
    }

    /** @test */
    public function reject_duplicate_card()
    {
        SavedCard::create([
            'user_id' => $this->user->user_id, 'card_brand' => 'visa',
            'last_four' => '1111', 'card_name' => 'Test',
            'expiry_month' => now()->addYear()->month, 'expiry_year' => now()->addYear()->year,
        ]);
        $this->actingAs($this->user)->post(route('account.cards.store'), [
            'card_name' => 'Test', 'card_number' => '4111111111111111',
            'card_expiry' => now()->addYear()->format('m/y'), 'card_cvv' => '123',
        ])->assertSessionHasErrors('card_number');
    }

    /** @test */
    public function delete_card()
    {
        $card = SavedCard::create([
            'user_id' => $this->user->user_id, 'card_brand' => 'visa',
            'last_four' => '4242', 'card_name' => 'Test',
            'expiry_month' => 12, 'expiry_year' => 2030,
        ]);
        $this->actingAs($this->user)->delete(route('account.cards.destroy', $card->card_id))
            ->assertRedirect();
        $this->assertDatabaseMissing('saved_cards', ['card_id' => $card->card_id]);
    }

    // ─── SECURITY ───

    /** @test */
    public function change_password()
    {
        $this->actingAs($this->user)->post(route('account.security.password'), [
            'current_password' => 'OldPass!99',
            'new_password' => 'NewSecure!Pass55',
            'new_password_confirmation' => 'NewSecure!Pass55',
        ])->assertRedirect()->assertSessionHas('success');
    }

    /** @test */
    public function change_password_wrong_current()
    {
        $this->actingAs($this->user)->post(route('account.security.password'), [
            'current_password' => 'WrongOldPass',
            'new_password' => 'NewSecure!Pass55',
            'new_password_confirmation' => 'NewSecure!Pass55',
        ])->assertSessionHasErrors();
    }
}