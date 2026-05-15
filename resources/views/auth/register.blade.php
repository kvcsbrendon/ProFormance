@extends('layouts.app')

@section('content')

<div class="auth-container" style="max-width: 480px;">
    <div class="auth-card">

        <h1 class="auth-title">Create Account</h1>
        <p class="auth-subtitle">Join ProFormance and start shopping</p>

        {{-- Breach Warning --}}
        @if(session('breach_warning'))
            <div class="kb-pw-breach-alert">
                <i class="bi bi-shield-exclamation"></i>
                <div>
                    <strong>Breached password detected!</strong>
                    <p>The password you entered has been found in known data breaches. Please choose a different one.</p>
                </div>
            </div>
        @endif


        <form method="POST" action="{{ route('register.post') }}" id="register-form" autocomplete="off">
            @csrf

            {{-- Name --}}
            <div class="name-group">
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label for="first_name" class="required">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-input"
                           value="{{ old('first_name') }}" autocomplete="given-name" placeholder="e.g. John" aria-label="First Name" required>
                </div>
                <div class="form-group" style="flex:1; margin-bottom:0;">
                    <label for="last_name" class="required">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-input"
                           value="{{ old('last_name') }}" autocomplete="family-name" placeholder="e.g. Smith" aria-label="Last Name" required>
                </div>
            </div>

            {{-- Company (optional) --}}
            <div class="form-group">
                <label for="company_name">Company <span style="color:var(--kb-secondary-font)">(optional)</span></label>
                <input type="text" id="company_name" name="company_name" class="form-input"
                       value="{{ old('company_name') }} " autocomplete="organization" placeholder="e.g. ProFormance" aria-label="Company Name">
            </div>

            {{-- Phone --}}
            <x-phone-input-register 
                codeName="country_phone_code"
                numberName="phone_number"
                codeValue="{{ old('country_phone_code', '44') }}"
                numberValue="{{ old('phone_number', '') }}"
                label="Phone Number"
                :required="true"
            />

            {{-- Email --}}
            <div class="form-group">
                <label for="email" class="required">Email Address</label>
                <input type="email" id="email" name="email" class="form-input"
                       value="{{ old('email') }}" autocomplete="email" placeholder="email@address.com" aria-label="Email Address" required>
            </div>

            <div class="form-group">
                <label for="email-confirmation" class="required">Email Address Confirmation</label>
                <input type="email" id="email_confirmation" name="email_confirmation" class="form-input"
                       value="{{ old('email_confirmation') }}" autocomplete="off" title="Pasting is not allowed. Type it!" aria-label="Email Address Confirmation" placeholder="email@address.com" aria-label="Email Address" onpaste="return false;" ondrop="return false;" required>
            </div>
            <div class="kb-inline-msg" id="email-match-result" style="display:none;"></div>

            {{-- Password --}}
            <div class="form-group">
                <label for="password" class="required">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-input"
                           required autocomplete="new-password" placeholder="e.g. ************" aria-label="password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                {{-- Strength Bar --}}
                <div class="kb-pw-strength">
                    <div class="kb-pw-strength-bar">
                        <div class="kb-pw-strength-fill" id="pw-strength-fill"></div>
                    </div>
                    <span class="kb-pw-strength-label" id="pw-strength-label"></span>
                </div>

                {{-- Requirements Checklist --}}
                <div class="kb-pw-requirements" id="pw-requirements">
                    <div class="kb-pw-req" id="req-length">
                        <i class="bi bi-x-circle"></i>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="kb-pw-req" id="req-upper">
                        <i class="bi bi-x-circle"></i>
                        <span>One uppercase letter</span>
                    </div>
                    <div class="kb-pw-req" id="req-lower">
                        <i class="bi bi-x-circle"></i>
                        <span>One lowercase letter</span>
                    </div>
                    <div class="kb-pw-req" id="req-number">
                        <i class="bi bi-x-circle"></i>
                        <span>One number</span>
                    </div>
                    <div class="kb-pw-req" id="req-special">
                        <i class="bi bi-x-circle"></i>
                        <span>One special character (@$!%*?&#)</span>
                    </div>
                </div>

                {{-- Breach Check Result (populated via AJAX) --}}
                <div id="pw-policy-result" class="kb-pw-breach-inline" style="display:none;"></div>
                <div class="kb-pw-breach-inline" id="pw-breach-result" style="display:none;"></div>
            </div>

            {{-- Confirm Password --}}
            <div class="form-group">
                <label for="password_confirmation" class="required">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-input" required autocomplete="new-password" placeholder="e.g. ************" aria-label="password confirmation">
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="kb-pw-req" id="req-match" style="margin-top:6px;">
                    <i class="bi bi-x-circle"></i>
                    <span>Passwords match</span>
                </div>
            </div>

            {{-- Terms --}}
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" {{ old('terms') ? 'checked' : '' }}>
                    I agree to the <a href="#" style="color:var(--kb-button-beige-hover);">Terms & Conditions</a>
                </label>
            </div>

            <button type="submit" class="submit-btn" id="submit-btn">
                <span class="btn-text">Create Account</span>
                <span class="btn-loader"><i class="bi bi-arrow-repeat"></i></span>
            </button>
        </form>

        <div class="auth-footer">
            <p class="footer-text">Already have an account?</p>
            <a href="{{ route('login') }}" class="footer-link">Sign In</a>
        </div>
    </div>
</div>
<script>
    const breachCheckUrl = '{{ route("password.checkBreach") }}';
    const csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('js/password-security.js') }}"></script>
@endsection
