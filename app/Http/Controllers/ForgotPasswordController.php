<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\LoginDetail;
use App\Models\PasswordReset;
use App\Rules\NotSimilarToUserData;
use App\Rules\NoCommonPatterns;

class ForgotPasswordController extends Controller
{

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }


    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        $loginDetail = LoginDetail::where('email_address', $email)->first();

        if (!$loginDetail) {
            return back()->with('success',
                'If an account exists with that email, a password reset link has been sent.');
        }

        PasswordReset::where('email', $email)
            ->where('used', false)
            ->update(['used' => true]);

        $token = bin2hex(random_bytes(32));

        PasswordReset::create([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'created_at' => now(),
            'used'       => false,
        ]);

        $resetUrl = url("/reset-password/{$token}?email=" . urlencode($email));
        Mail::send('emails.password-reset', [
            'resetUrl' => $resetUrl,
            'email'    => $email,
            'minutes'  => 15,
        ], function ($message) use ($email) {
            $message->to($email)
                    ->subject('Reset Your ProFormance Password');
        });

        return back()->with('success',
            'If an account exists with that email, a password reset link has been sent.');
    }

    public function showResetForm(Request $request, $token)
    {
        $email = $request->query('email');

        if (!$email) {
            return redirect()->route('password.forgot')
                ->with('error', 'Invalid reset link.');
        }

        $hashedToken = hash('sha256', $token);

        $reset = PasswordReset::where('email', $email)
            ->where('token', $hashedToken)
            ->where('used', false)
            ->first();

        if (!$reset || !$reset->isValid()) {
            return redirect()->route('password.forgot')
                ->with('error', 'This reset link has expired or is invalid. Please request a new one.');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'email'    => 'required|email',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#]/',
                new NotSimilarToUserData($request->first_name, $request->last_name, $request->email),
                new NoCommonPatterns(),
            ],
        ], [
            'password.min'   => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
        ]);

        $hashedToken = hash('sha256', $request->token);

        $reset = PasswordReset::where('email', $request->email)
            ->where('token', $hashedToken)
            ->where('used', false)
            ->first();

        if (!$reset || !$reset->isValid()) {
            return redirect()->route('password.forgot')
                ->with('error', 'This reset link has expired or is invalid. Please request a new one.');
        }

        $breachCount = $this->checkPasswordBreach($request->password);

        if ($breachCount > 0) {
            return back()
                ->withErrors([
                    'password' => "This password has appeared in {$breachCount} data breach(es). Please choose a different password."
                ])
                ->withInput(['token' => $request->token, 'email' => $request->email])
                ->with('breach_warning', true);
        }

        $loginDetail = LoginDetail::where('email_address', $request->email)->first();

        if (!$loginDetail) {
            return redirect()->route('password.forgot')
                ->with('error', 'Account not found.');
        }

        $loginDetail->password_hash = Hash::make($request->password);
        $loginDetail->password_salt = ''; // or null if nullable
        $loginDetail->save();

        $reset->used = true;
        $reset->save();

        return redirect()->route('login')
            ->with('success', 'Your password has been reset! You can now sign in with your new password.');
    }

    private function checkPasswordBreach(string $password): int
    {
        try {
            $sha1   = strtoupper(sha1($password));
            $prefix = substr($sha1, 0, 5);
            $suffix = substr($sha1, 5);

            $response = Http::timeout(5)
                ->withHeaders(['Add-Padding' => 'true'])
                ->get("https://api.pwnedpasswords.com/range/{$prefix}");

            if (!$response->ok()) {
                return 0;
            }

            foreach (explode("\n", $response->body()) as $line) {
                [$hashSuffix, $count] = explode(':', trim($line));
                if (strtoupper($hashSuffix) === $suffix) {
                    return (int) $count;
                }
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
