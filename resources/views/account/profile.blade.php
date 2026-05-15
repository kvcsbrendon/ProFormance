{{-- resources/views/account/profile.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">My Profile</h1>
    <p class="kb-account-subtitle">Manage your personal information.</p>
</div>

<div class="kb-account-card">
    <form method="POST" action="{{ route('account.profile.update') }}">
        @csrf

        <div class="kb-form-row">
            <div class="kb-form-group">
                <label for="first_name" class="kb-form-label">First Name</label>
                <input type="text" id="first_name" name="first_name"
                       class="kb-form-input"
                       value="{{ old('first_name', $user->first_name) }}" required>
            </div>

            <div class="kb-form-group">
                <label for="last_name" class="kb-form-label">Last Name</label>
                <input type="text" id="last_name" name="last_name"
                       class="kb-form-input"
                       value="{{ old('last_name', $user->last_name) }}" required>
            </div>
        </div>

        <div class="kb-form-group">
            <label for="company_name" class="kb-form-label">Company Name <span class="kb-form-optional">(optional)</span></label>
            <input type="text" id="company_name" name="company_name"
                   class="kb-form-input"
                   value="{{ old('company_name', $user->company_name) }}">
        </div>

        <div class="kb-form-row">
            <div class="kb-form-group kb-form-group-small">
                <label for="country_phone_code" class="kb-form-label">Phone Code</label>
                <input type="number" id="country_phone_code" name="country_phone_code"
                       class="kb-form-input"
                       value="{{ old('country_phone_code', $user->country_phone_code) }}"
                       placeholder="44" required>
            </div>

            <div class="kb-form-group">
                <label for="phone_number" class="kb-form-label">Phone Number</label>
                <input type="text" id="phone_number" name="phone_number"
                       class="kb-form-input"
                       value="{{ old('phone_number', $user->phone_number) }}" required>
            </div>
        </div>

        <div class="kb-form-group">
            <label class="kb-form-label">Email Address</label>
            <input type="email" class="kb-form-input kb-form-input-disabled"
                   value="{{ $user->email }}" disabled>
            <p class="kb-form-hint">To change your email, go to <a href="{{ route('account.security') }}">Security Settings</a>.</p>
        </div>

        <div class="kb-form-group">
            <label class="kb-form-label">Account Type</label>
            <input type="text" class="kb-form-input kb-form-input-disabled"
                   value="{{ ucfirst($user->user_role) }}" disabled>
        </div>

        <div class="kb-newsletter-section">
            <label class="kb-form-label">Newsletter</label>

            <div class="kb-newsletter-row">
                @if($newsletterSubscribed)
                    <span class="kb-newsletter-status kb-newsletter-status-on">
                        ✓ You are subscribed
                    </span>

                    <button type="submit"
                            name="subscribed"
                            value="0"
                            class="kb-newsletter-btn kb-newsletter-btn-outline">
                        Unsubscribe
                    </button>
                @else
                    <span class="kb-newsletter-status kb-newsletter-status-off">
                        Not subscribed
                    </span>

                    <button type="submit"
                            name="subscribed"
                            value="1"
                            class="kb-newsletter-btn kb-newsletter-btn-primary">
                        Subscribe
                    </button>
                @endif
            </div>
        </div>

        <div class="kb-form-actions">
            <button type="submit" class="kb-account-btn kb-account-btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
