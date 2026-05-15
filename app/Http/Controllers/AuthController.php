<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\LoginDetail;
use App\Models\UserMessage;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /**
     * Step 1: Show email entry form.
     */
    public function showEmailCheck()
    {
        return view('auth.email-check');
    }

    /**
     * Step 1 submit: Check if email exists → login or register.
     */
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $exists = LoginDetail::where('email_address', $request->email)->exists();

        if ($exists) {
            return redirect()->route('login')
                ->with('checked_email', $request->email);
        }

        return redirect()->route('register')
            ->with('checked_email', $request->email)
            ->withInput(['email' => $request->email]);
    }

    /**
     * Show login form.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Login submit.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $loginDetail = LoginDetail::where('email_address', $credentials['email'])->first();

        if (!$loginDetail) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        if (!Hash::check($credentials['password'], $loginDetail->password_hash)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $user = User::find($loginDetail->user_id);

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.'])->onlyInput('email');
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Your account is inactive.'])->onlyInput('email');
        }

        // 2FA check
        if ($user->two_factor_enabled) {
            session(['2fa:user:id' => $user->user_id]);
            return redirect()->route('2fa.challenge');
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->user_role === 'admin') {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Welcome back, ' . $user->first_name . '!');
        }

        return redirect()->intended('/')
            ->with('success', 'Welcome back, ' . $user->first_name . '!');
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'You have been logged out.');
    }

    public function loginDetail()
    {
        return view('account.login-detail');
    }

    // ═══════════════════════════════════════════
    // GOOGLE OAUTH
    // ═══════════════════════════════════════════

    /**
     * Redirect to Google's OAuth consent screen.
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Handle the callback from Google.
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('auth.emailCheck')
                ->withErrors(['google' => 'Google sign-in failed. Please try again.']);
        }

        $email    = $googleUser->getEmail();
        $googleId = $googleUser->getId();
        $name     = $googleUser->getName();

        // Split name into first/last
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0] ?? 'User';
        $lastName  = $nameParts[1] ?? '';

        // 1) Check if we already have a user with this google_id
        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            return $this->loginGoogleUser($user);
        }

        // 2) Check if the email matches an existing account
        $loginDetail = LoginDetail::where('email_address', $email)->first();

        if ($loginDetail) {
            $user = User::find($loginDetail->user_id);

            if ($user) {
                // Link Google to existing account
                $user->update(['google_id' => $googleId]);
                return $this->loginGoogleUser($user);
            }
        }

        // 3) New user — create account
        DB::beginTransaction();
        try {
            $user = User::create([
                'first_name'         => $firstName,
                'last_name'          => $lastName,
                'country_phone_code' => 44,
                'phone_number'       => '0000000000',
                'user_role'          => 'customer',
                'is_active'          => true,
                'email_verified'     => true,
                'google_id'          => $googleId,
            ]);

            LoginDetail::create([
                'user_id'       => $user->user_id,
                'email_address' => $email,
                'password_hash' => Hash::make(bin2hex(random_bytes(16))),
                'password_salt' => '',
            ]);

            DB::commit();

            // Send welcome message
            UserMessage::send(
                $user->user_id,
                'system',
                'Welcome to ProFormance!',
                "Your account has been created via Google Sign-In. You can manage your profile, track orders, and more from your account dashboard.",
                route('account.dashboard'),
                'Go to Dashboard'
            );

            Auth::login($user);
            session()->regenerate();

            return redirect('/')
                ->with('success', 'Welcome to ProFormance, ' . $firstName . '! Your account has been created.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('auth.emailCheck')
                ->withErrors(['google' => 'Something went wrong creating your account. Please try again.']);
        }
    }

    /**
     * Log in an existing Google user.
     */
    private function loginGoogleUser(User $user)
    {
        if (!$user->is_active) {
            return redirect()->route('auth.emailCheck')
                ->withErrors(['google' => 'Your account is inactive.']);
        }

        // 2FA check — same as regular login
        if ($user->two_factor_enabled) {
            session(['2fa:user:id' => $user->user_id]);
            return redirect()->route('2fa.challenge');
        }

        Auth::login($user);
        session()->regenerate();

        if ($user->user_role === 'admin') {
            return redirect()->route('admin.dashboard')
                ->with('success', 'Welcome back, ' . $user->first_name . '!');
        }

        return redirect()->intended('/')
            ->with('success', 'Welcome back, ' . $user->first_name . '!');
    }
}