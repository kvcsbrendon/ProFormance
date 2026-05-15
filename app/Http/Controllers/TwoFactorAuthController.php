<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Google2FA;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorAuthController extends Controller
{
    protected $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    public function showChallenge()
    {
        if (!session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        if (!session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        $userId = session('2fa:user:id');
        $user = User::find($userId);

        if (!$user || !$user->two_factor_enabled) {
            return redirect()->route('login')
                ->withErrors(['error' => '2FA not enabled for this user.']);
        }

        $secret = $user->two_factor_secret;

        // Check TOTP code first
        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            Auth::login($user);
            session()->forget('2fa:user:id');
            $request->session()->regenerate();
            return redirect()->intended('/')
                ->with('success', 'Welcome back, ' . $user->first_name . '!');
        }

        // Check recovery codes
        $hashedCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];
        foreach ($hashedCodes as $index => $hash) {
            if (Hash::check($request->code, $hash)) {
                unset($hashedCodes[$index]);
                $user->two_factor_recovery_codes = json_encode(array_values($hashedCodes));
                $user->save();

                Auth::login($user);
                session()->forget('2fa:user:id');
                $request->session()->regenerate();
                return redirect()->intended('/')
                    ->with('warning', 'You used a recovery code. Please generate new ones in your account settings.');
            }
        }

        return back()->withErrors(['code' => 'Invalid verification code or recovery code.']);
    }
}
