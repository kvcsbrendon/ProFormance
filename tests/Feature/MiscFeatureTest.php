<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserMessage;

class MiscFeatureTest extends TestCase
{
    // ─── NEWSLETTER ───

    /** @test */
    public function subscribe_to_newsletter()
    {
        $email = 'phpunit-' . uniqid() . '@example.com';
        $response = $this->post(route('newsletter.subscribe'), ['email_address' => $email]);
        $response->assertRedirect();
        $this->assertDatabaseHas('newsletter_subscribers', ['email_address' => $email]);
    }

    /** @test */
    public function newsletter_rejects_missing_email()
    {
        $this->post(route('newsletter.subscribe'), [])
            ->assertSessionHasErrors('email_address');
    }

    // ─── CONTACT FORM ───

    /** @test */
    public function contact_page_loads()
    {
        $this->get(route('contact'))->assertStatus(200);
    }

    /** @test */
    public function submit_contact_form()
    {
        $this->post(route('contact.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email_address' => 'john@example.com',
            'subject_select' => 'General Inquiry',
            'message_description' => 'I have a question about my recent order.',
        ])->assertRedirect();
    }

    /** @test */
    public function contact_form_validates_required()
    {
        $this->post(route('contact.store'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'email_address', 'message_description']);
    }

    // ─── CURRENCY ───

    /** @test */
    public function switch_currency_to_eur()
    {
        $this->post(route('currency.update'), ['currency' => 'eur'])->assertRedirect();
        $this->assertEquals('eur', session('currency'));
    }

    /** @test */
    public function switch_currency_to_usd()
    {
        $this->post(route('currency.update'), ['currency' => 'usd'])->assertRedirect();
        $this->assertEquals('usd', session('currency'));
    }

    /** @test */
    public function currency_defaults_to_gbp()
    {
        $this->assertEquals('gbp', session('currency', 'gbp'));
    }

    // ─── MESSAGES ───

    /** @test */
    public function messages_page_requires_auth()
    {
        $this->get(route('account.messages'))->assertRedirect(route('login'));
    }

    /** @test */
    public function messages_page_loads()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('account.messages'))->assertStatus(200);
    }

    /** @test */
    public function mark_message_read()
    {
        $user = User::factory()->create();
        $msg = UserMessage::send($user->user_id, 'system', 'Test', 'Body');
        $this->assertNull($msg->read_at);

        $this->actingAs($user)->post(route('account.messages.read', $msg->message_id));
        $msg->refresh();
        $this->assertNotNull($msg->read_at);
    }

    /** @test */
    public function mark_all_messages_read()
    {
        $user = User::factory()->create();
        UserMessage::send($user->user_id, 'system', 'Test 1', 'Body 1');
        UserMessage::send($user->user_id, 'system', 'Test 2', 'Body 2');

        $this->actingAs($user)->post(route('account.messages.readAll'));
        $unread = UserMessage::where('user_id', $user->user_id)->whereNull('read_at')->count();
        $this->assertEquals(0, $unread);
    }

    /** @test */
    public function delete_message()
    {
        $user = User::factory()->create();
        $msg = UserMessage::send($user->user_id, 'system', 'Delete Me', 'Body');

        $this->actingAs($user)->delete(route('messages.destroy', $msg->message_id));
        $this->assertDatabaseMissing('user_messages', ['message_id' => $msg->message_id]);
    }

    // ─── PAGES ───

    /** @test */
    public function home_page_loads()
    {
        $this->get(route('home'))->assertStatus(200);
    }

    /** @test */
    public function products_page_loads()
    {
        $this->get(route('products.index'))->assertStatus(200);
    }

    /** @test */
    public function products_search()
    {
        $this->get(route('products.index', ['search' => 'protein']))->assertStatus(200);
    }

    /** @test */
    public function products_sort()
    {
        $this->get(route('products.index', ['sort' => 'price_low']))->assertStatus(200);
    }

    /** @test */
    public function faq_page_redirects()
    {
        // FAQ route redirects to info page
        $this->get(route('faq'))->assertRedirect();
    }
}