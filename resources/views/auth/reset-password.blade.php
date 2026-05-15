@extends('layouts.app')

@section('content')

<div class="auth-container" style="max-width: 480px;">
    <div class="auth-card">

        <h1 class="auth-title">Reset Password</h1>
        <p class="auth-subtitle">Choose a new password for your account.</p>

        @if($errors->any())
            <div class="error-message">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if(session('breach_warning'))
            <div class="kb-pw-breach-alert">
                <i class="bi bi-shield-exclamation"></i>
                <div>
                    <strong>Breached password detected!</strong>
                    <p>The password you entered has been found in known data breaches. Please choose a different one.</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="error-message">
                <i class="bi bi-exclamation-circle" style="margin-right:6px;"></i>
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.reset') }}" id="reset-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            {{-- Show which account --}}
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-input" value="{{ $email }}" disabled
                       style="background:var(--kb-grey-100); color:var(--kb-grey-500); cursor:not-allowed;">
            </div>

            {{-- New Password --}}
            <div class="form-group">
                <label for="password">New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-input"
                           required autocomplete="new-password">
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

                {{-- Breach Check Result --}}
                <div class="kb-pw-breach-inline" id="pw-breach-result" style="display:none;"></div>
            </div>

            {{-- Confirm Password --}}
            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="form-input" required autocomplete="new-password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="kb-pw-req" id="req-match" style="margin-top:6px;">
                    <i class="bi bi-x-circle"></i>
                    <span>Passwords match</span>
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <span class="btn-text">Reset Password</span>
                <span class="btn-loader"><i class="bi bi-arrow-repeat"></i></span>
            </button>
        </form>

        <div class="auth-footer">
            <a href="{{ route('login') }}" class="footer-link">Back to Sign In</a>
        </div>
    </div>
</div>
<script>
    const breachCheckUrl = '{{ route("password.checkBreach") }}';
    const csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ asset('js/password-security.js') }}"></script>
@endsection
