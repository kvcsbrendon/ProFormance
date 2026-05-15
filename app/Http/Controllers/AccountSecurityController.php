<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LoginDetail;
use Illuminate\Support\Facades\Hash;    

class AccountSecurityController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('account.security', compact('user'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $loginDetail = LoginDetail::where('user_id', $user->user_id)->firstOrFail();

        if (!Hash::check($request->current_password, $loginDetail->password_hash)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $loginDetail->update([
            'password_hash' => Hash::make($request->new_password),
            'password_salt' => '', // or '' if your DB column is NOT NULL
        ]);

        return redirect()->route('account.security')
            ->with('success', 'Password changed successfully.');
    }

    public function changeEmail(Request $request)
    {
        $request->validate([
            'email'            => 'required|email|max:255',
            'current_password' => 'required',
        ]);

        $user = Auth::user();
        $loginDetail = LoginDetail::where('user_id', $user->user_id)->firstOrFail();

        // Verify password
        if (!Hash::check($request->current_password, $loginDetail->password_hash)) {
            return back()->withErrors([
                'current_password' => 'Password is incorrect.',
            ]);
        }

        // Check email uniqueness
        $exists = LoginDetail::where('email_address', $request->email)
            ->where('user_id', '!=', $user->user_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['email' => 'This email is already in use.']);
        }

        $loginDetail->update(['email_address' => $request->email]);
        $user->update(['email_verified' => false]);

        return redirect()->route('account.security')
            ->with('success', 'Email updated successfully.');
    }

    public function deleteAccount(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'confirm_delete'   => 'required|in:DELETE',
        ]);

        $user = Auth::user();
        $loginDetail = LoginDetail::where('user_id', $user->user_id)->firstOrFail();

        if (!Hash::check($request->current_password, $loginDetail->password_hash)) {
            return back()->withErrors(['current_password' => 'Password is incorrect.']);
        }

        $user->update(['is_active' => false]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('success', 'Your account has been deactivated.');
    }
}
