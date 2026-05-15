<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\LoginDetail;
use App\Rules\NotSimilarToUserData;
use App\Rules\NoCommonPatterns;

class RegisterController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name'          => ['required', 'string', 'max:100'],
            'last_name'           => ['required', 'string', 'max:100'],
            'company_name'        => ['nullable', 'string', 'max:255'],
            'country_phone_code'  => ['required', 'integer'],
            'phone_number'        => ['required', 'string', 'regex:/^[0-9]{10,15}$/'],
            'email'               => ['required', 'string', 'email', 'confirmed', 'max:255', 'unique:login_details,email_address'],
            'password'            => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',      // at least one uppercase
                'regex:/[a-z]/',      // at least one lowercase
                'regex:/[0-9]/',      // at least one number
                'regex:/[@$!%*?&#]/', // at least one special character
                new NotSimilarToUserData($request->first_name, $request->last_name, $request->email),
                new NoCommonPatterns(),
            ],
            'terms' => ['accepted'],
        ], [
            'terms.accepted'       => 'You must accept the Terms & Conditions.',
            'email.unique'         => 'This email is already registered.',
            'phone_number.regex'   => 'Phone number must be 10-15 digits.',
            'email.email'          => 'Please enter a valid email address.',
            'password.min'         => 'Password must be at least 8 characters.',
            'password.regex'       => 'Password must include uppercase, lowercase, number, and special character.',
            'email.confirmed' => 'Email addresses do not match.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $breachCount = $this->checkPasswordBreach($request->password);

        if ($breachCount > 0) {
            return redirect()->back()
                ->withErrors([
                    'password' => "This password has appeared in {$breachCount} data breach(es). Please choose a different password for your security."
                ])
                ->withInput()
                ->with('breach_warning', true);
        }

        \DB::beginTransaction();

        try {
            $user = User::create([
                'first_name'          => $request->first_name,
                'last_name'           => $request->last_name,
                'company_name'        => $request->company_name,
                'country_phone_code'  => $request->country_phone_code,
                'phone_number'        => $request->phone_number,
                'user_role'           => 'customer',
                'is_active'           => true,
                'email_verified'      => false,
            ]);

            $salt = bin2hex(random_bytes(16));
            $passwordHash = hash('sha256', $request->password . $salt);

            LoginDetail::create([
                'user_id'       => $user->user_id,
                'email_address' => $request->email,
                'password_hash' => Hash::make($request->password),
                'password_salt' => '',
            ]);

            \DB::commit();
            Auth::login($user);

            return redirect('/')
                ->with('success', 'Welcome to ProFormance! Your account has been created successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();

            return redirect()->back()
                ->with('error', 'Registration failed. Please try again.')
                ->withInput();
        }
    }

    public function checkBreach(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $count = $this->checkPasswordBreach($request->password);

        return response()->json([
            'breached' => $count > 0,
            'count'    => $count,
        ]);
    }

    private function checkPasswordBreach(string $password): int
    {
        try {
            $sha1   = strtoupper(sha1($password));
            $prefix = substr($sha1, 0, 5);
            $suffix = substr($sha1, 5);

            $response = Http::timeout(10)
                ->withoutVerifying()
                ->withHeaders([
                    'Add-Padding' => 'true',
                    'User-Agent'  => 'ProFormance-Laravel-App',
                ])
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
            // If HIBP is unreachable, don't block registration
            return 0;
        }
    }
}
