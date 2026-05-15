{{-- resources/views/account/security.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">Security Settings</h1>
    <p class="kb-account-subtitle">Manage your login credentials and account security.</p>
</div>

{{-- CHANGE EMAIL --}}
<div class="kb-account-card">
    <h2 class="kb-account-card-title">Email Address</h2>
    <p class="kb-form-hint" style="margin-bottom: 1rem;">
        Current email: <strong>{{ $user->email }}</strong>
    </p>
    <form method="POST" action="{{ route('account.security.email') }}">
        @csrf
        <div class="kb-form-group">
            <label class="kb-form-label">New Email Address</label>
            <input type="email" name="email" class="kb-form-input"
                   value="{{ old('email') }}" required placeholder="newemail@example.com">
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">Current Password <span class="kb-form-hint">(to confirm)</span></label>
            <input type="password" name="current_password" class="kb-form-input" required>
        </div>
        <div class="kb-form-actions">
            <button type="submit" class="kb-account-btn kb-account-btn-primary">Update Email</button>
        </div>
    </form>
</div>

{{-- CHANGE PASSWORD --}}
<div class="kb-account-card">
    <h2 class="kb-account-card-title">Change Password</h2>
    <form method="POST" action="{{ route('account.security.password') }}">
        @csrf
        <div class="kb-form-group">
            <label class="kb-form-label">Current Password</label>
            <input type="password" name="current_password" class="kb-form-input" required>
        </div>
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">New Password</label>
                <input type="password" name="new_password" class="kb-form-input" required minlength="8">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Confirm New Password</label>
                <input type="password" name="new_password_confirmation" class="kb-form-input" required>
            </div>
        </div>
        <div class="kb-form-actions">
            <button type="submit" class="kb-account-btn kb-account-btn-primary">Change Password</button>
        </div>
    </form>
</div>

{{-- DELETE ACCOUNT --}}
<div class="kb-account-card kb-account-card-danger">
    <h2 class="kb-account-card-title">Delete Account</h2>
    <p class="kb-form-hint" style="margin-bottom: 1rem;">
        This will deactivate your account. Your order history will be preserved,
        but you will no longer be able to log in. This action cannot be easily undone.
    </p>
    <form method="POST" action="{{ route('account.security.delete') }}"
          onsubmit="return confirm('Are you absolutely sure you want to delete your account?')">
        @csrf
        <div class="kb-form-group">
            <label class="kb-form-label">Current Password</label>
            <input type="password" name="current_password" class="kb-form-input" required>
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">Type <strong>DELETE</strong> to confirm</label>
            <input type="text" name="confirm_delete" class="kb-form-input" required placeholder="DELETE">
        </div>
        <div class="kb-form-actions">
            <button type="submit" class="kb-account-btn kb-account-btn-danger">Delete My Account</button>
        </div>
    </form>
</div>
@endsection
