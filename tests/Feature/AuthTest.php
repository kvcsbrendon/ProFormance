<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\LoginDetail;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    protected function createUser(array $overrides = []): array
    {
        $email = $overrides['email'] ?? ('authtest-' . uniqid() . '@example.com');
        unset($overrides['email']);

        $user = User::create(array_merge([
            'first_name' => 'Test', 'last_name' => 'User',
            'country_phone_code' => 44, 'phone_number' => '07700000000',
            'user_role' => 'customer', 'is_active' => true,
        ], $overrides));

        LoginDetail::create([
            'user_id' => $user->user_id,
            'email_address' => $email,
            'password_hash' => Hash::make('SecurePass!99'),
            'password_salt' => '',
        ]);

        return ['user' => $user, 'email' => $email];
    }

    /** @test */
    public function email_check_page_loads()
    {
        $this->get(route('auth.emailCheck'))->assertStatus(200);
    }

    /** @test */
    public function existing_email_redirects_to_login()
    {
        $d = $this->createUser();
        $this->post(route('auth.checkEmail'), ['email' => $d['email']])
            ->assertRedirect(route('login'));
    }

    /** @test */
    public function new_email_redirects_to_register()
    {
        $this->post(route('auth.checkEmail'), ['email' => 'new-' . uniqid() . '@example.com'])
            ->assertRedirect(route('register'));
    }

    /** @test */
    public function email_check_validates_format()
    {
        $this->post(route('auth.checkEmail'), ['email' => 'not-an-email'])
            ->assertSessionHasErrors('email');
    }

    /** @test */
    public function login_page_loads()
    {
        $this->get(route('login'))->assertStatus(200);
    }

    /** @test */
    public function login_with_valid_credentials()
    {
        $d = $this->createUser();
        $this->post(route('login.post'), ['email' => $d['email'], 'password' => 'SecurePass!99'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($d['user']);
    }

    /** @test */
    public function login_with_invalid_password()
    {
        $d = $this->createUser();
        $this->post(route('login.post'), ['email' => $d['email'], 'password' => 'Wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function login_with_nonexistent_email()
    {
        $this->post(route('login.post'), ['email' => 'nobody-' . uniqid() . '@x.com', 'password' => 'x'])
            ->assertSessionHasErrors('email');
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $d = $this->createUser(['is_active' => false]);
        $this->post(route('login.post'), ['email' => $d['email'], 'password' => 'SecurePass!99'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function admin_redirects_to_admin_dashboard()
    {
        $d = $this->createUser(['user_role' => 'admin']);
        $this->post(route('login.post'), ['email' => $d['email'], 'password' => 'SecurePass!99'])
            ->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function logout_redirects_to_home()
    {
        $d = $this->createUser();
        $this->actingAs($d['user'])->get(route('logout.get'))->assertRedirect('/');
    }

    /** @test */
    public function register_page_loads()
    {
        $this->get(route('register'))->assertStatus(200);
    }

    /** @test */
    public function register_validates_required_fields()
    {
        $this->post(route('register.post'), [])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);
    }

    /** @test */
    public function register_rejects_duplicate_email()
    {
        $d = $this->createUser();
        $this->post(route('register.post'), [
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => $d['email'], 'email_confirmation' => $d['email'],
            'phone_number' => '07700000001', 'country_phone_code' => 44,
            'password' => 'K7$mPx!nR9vL2', 'password_confirmation' => 'K7$mPx!nR9vL2',
            'terms' => '1',
        ])->assertSessionHasErrors('email');
    }

    /** @test */
    public function forgot_password_page_loads()
    {
        $this->get(route('password.forgot'))->assertStatus(200);
    }

    /** @test */
    public function reset_link_sent_for_valid_email()
    {
        $d = $this->createUser();
        $this->post(route('password.sendResetLink'), ['email' => $d['email']])->assertRedirect();
    }
}