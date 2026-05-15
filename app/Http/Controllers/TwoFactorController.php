<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PragmaRX\Google2FALaravel\Google2FA;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    public function index()
    {
        $user = Auth::user();

        // If 2FA is already enabled, show the management view
        if ($user->two_factor_enabled) {
            return view('account.two-factor', [
                'user' => $user,
                'is_enabled' => true
            ]);
        }

        // Generate or retrieve existing setup secret
        $secret = session('2fa_setup_secret');
        
        if (!$secret) {
            $secret = $this->google2fa->generateSecretKey();
            session(['2fa_setup_secret' => $secret]);
            session(['2fa_setup_started_at' => now()]);
        }

        $qrCode = $this->google2fa->getQRCodeInline(
            config('app.name'),
            $user->email ?? $user->first_name,
            $secret
        );

        return view('account.two-factor', [
            'user' => $user,
            'secret' => $secret,
            'qrCode' => $qrCode,
            'is_enabled' => false
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = Auth::user();
        $secret = session('2fa_setup_secret');
        
        // Check if session exists AND hasn't expired (e.g., 10 minutes timeout)
        if (!$secret || !session('2fa_setup_started_at')) {
            return back()->withErrors(['error' => 'Setup session expired. Please restart.']);
        }
        
        // Optional: Check if session is older than 10 minutes
        $startedAt = session('2fa_setup_started_at');
        if (now()->diffInMinutes($startedAt) > 10) {
            session()->forget(['2fa_setup_secret', '2fa_setup_started_at']);
            return back()->withErrors(['error' => 'Setup session expired. Please restart.']);
        }

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (!$valid) {
            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        $plainCodes = $this->generateRecoveryCodesArray(8);
        $hashedCodes = array_map(fn($code) => Hash::make($code), $plainCodes);

        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = true;
        $user->two_factor_recovery_codes = json_encode($hashedCodes);
        $user->save();

        session()->forget(['2fa_setup_secret', '2fa_setup_started_at']);
        session()->flash('new_recovery_codes', $plainCodes);

        return redirect()->route('account.2fa')
            ->with('success', 'Two-factor authentication enabled. Save your recovery codes!');
    }

    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        $loginDetail = \App\Models\LoginDetail::where('user_id', $user->user_id)->first();

        if (!$loginDetail || !Hash::check($request->password, $loginDetail->password_hash)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return redirect()->route('account.2fa')
            ->with('success', 'Two-factor authentication disabled.');
    }

    public function generateRecoveryCodes(Request $request)
    {
        $user = Auth::user();
        if (!$user->two_factor_enabled) {
            return back()->withErrors(['error' => '2FA is not enabled.']);
        }

        $plainCodes = $this->generateRecoveryCodesArray(8);
        $hashedCodes = array_map(fn($code) => Hash::make($code), $plainCodes);

        $user->two_factor_recovery_codes = json_encode($hashedCodes);
        $user->save();

        session()->flash('new_recovery_codes', $plainCodes);

        return back()->with('success', 'New recovery codes generated. Save them now!');
    }

    protected function generateRecoveryCodesArray($count = 8)
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }
}
