{{-- resources/views/account/two-factor.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">Two-Factor Authentication</h1>
    <p class="kb-account-subtitle">Add an extra layer of security using an authenticator app.</p>
</div>

{{-- Recovery Codes Flash --}}
@if(session('new_recovery_codes'))
    <div class="kb-2fa-recovery-alert">
        <div class="kb-2fa-recovery-header">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Save your recovery codes now!</strong>
        </div>
        <p>These codes will not be shown again. Store them somewhere safe — you'll need them if you lose access to your authenticator app.</p>
        <div class="kb-2fa-codes-grid">
            @foreach(session('new_recovery_codes') as $code)
                <code class="kb-2fa-code">{{ $code }}</code>
            @endforeach
        </div>
        <button type="button" class="kb-account-btn kb-account-btn-outline kb-account-btn-small kb-2fa-copy-btn"
                onclick="navigator.clipboard.writeText(this.dataset.codes).then(() => { this.innerHTML = '<i class=\'bi bi-check2\'></i> Copied!'; setTimeout(() => this.innerHTML = '<i class=\'bi bi-clipboard\'></i> Copy All', 2000); })"
                data-codes="{{ implode("\n", session('new_recovery_codes')) }}">
            <i class="bi bi-clipboard"></i> Copy All
        </button>
    </div>
@endif

@if($user->two_factor_enabled)
    {{-- ══════ ENABLED STATE ══════ --}}
    <div class="kb-2fa-card">
        <div class="kb-2fa-status">
            <div class="kb-2fa-status-icon kb-2fa-on">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div>
                <h3 class="kb-2fa-status-title">Two-factor authentication is <span class="kb-2fa-on-text">enabled</span></h3>
                <p class="kb-2fa-status-desc">Your account has an extra layer of protection.</p>
            </div>
        </div>
    </div>

    {{-- Recovery Codes Section --}}
    <div class="kb-2fa-card">
        <div class="kb-2fa-section-header">
            <i class="bi bi-key"></i>
            <h3>Recovery Codes</h3>
        </div>
        <p class="kb-2fa-hint">If you lose your authenticator device, use a recovery code to sign in. Each code can only be used once.</p>

        @if($user->two_factor_recovery_codes)
            @php $codesCount = count(json_decode($user->two_factor_recovery_codes, true) ?? []); @endphp
            <div class="kb-2fa-codes-remaining">
                <i class="bi bi-info-circle"></i>
                You have <strong>{{ $codesCount }}</strong> recovery code{{ $codesCount !== 1 ? 's' : '' }} remaining.
            </div>
        @endif

        <form method="POST" action="{{ route('account.2fa.recovery') }}">
            @csrf
            <button type="submit" class="kb-account-btn kb-account-btn-outline">
                <i class="bi bi-arrow-repeat"></i> Generate New Recovery Codes
            </button>
        </form>
    </div>

    {{-- Disable Section --}}
    <div class="kb-2fa-card kb-2fa-danger-card">
        <div class="kb-2fa-section-header">
            <i class="bi bi-shield-x"></i>
            <h3>Disable Two-Factor Authentication</h3>
        </div>
        <p class="kb-2fa-hint">This will remove 2FA from your account. Enter your password to confirm.</p>

        <form method="POST" action="{{ route('account.2fa.disable') }}" class="kb-2fa-disable-form">
            @csrf
            <div class="kb-form-group">
                <label class="kb-form-label" for="disable-password">Password</label>
                <input type="password" name="password" id="disable-password"
                       class="kb-form-input" required placeholder="Enter your password">
                @error('password')
                    <div class="kb-form-error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="kb-account-btn kb-2fa-danger-btn">
                <i class="bi bi-shield-x"></i> Disable 2FA
            </button>
        </form>
    </div>

@else
    {{-- ══════ SETUP STATE ══════ --}}
    <div class="kb-2fa-card">
        <div class="kb-2fa-status">
            <div class="kb-2fa-status-icon kb-2fa-off">
                <i class="bi bi-shield"></i>
            </div>
            <div>
                <h3 class="kb-2fa-status-title">Two-factor authentication is <span class="kb-2fa-off-text">not enabled</span></h3>
                <p class="kb-2fa-status-desc">Protect your account by adding a second verification step at sign-in.</p>
            </div>
        </div>
    </div>

    <div class="kb-2fa-card">
        <div class="kb-2fa-setup-steps">
            {{-- Step 1 --}}
            <div class="kb-2fa-step">
                <span class="kb-2fa-step-num">1</span>
                <div>
                    <h4>Install an authenticator app</h4>
                    <p>Download Google Authenticator, Authy, or Microsoft Authenticator on your phone.</p>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="kb-2fa-step">
                <span class="kb-2fa-step-num">2</span>
                <div>
                    <h4>Scan this QR code</h4>
                    <p>Open your authenticator app and scan the code below.</p>
                </div>
            </div>

            <div class="kb-2fa-qr-wrapper">
                <div class="kb-2fa-qr">{!! $qrCode !!}</div>
                <div class="kb-2fa-manual-key">
                    <span class="kb-2fa-hint">Can't scan? Enter this key manually:</span>
                    <code class="kb-2fa-secret">{{ $secret }}</code>
                </div>
            </div>

            {{-- Step 3 --}}
            <div class="kb-2fa-step">
                <span class="kb-2fa-step-num">3</span>
                <div>
                    <h4>Enter the 6-digit code</h4>
                    <p>Type the code shown in your authenticator app to verify it's working.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('account.2fa.verify') }}" class="kb-2fa-verify-form">
                @csrf
                <div class="kb-form-group">
                    <label class="kb-form-label" for="2fa-code">Verification Code</label>
                    <input type="text" name="code" id="2fa-code"
                           class="kb-form-input kb-2fa-code-input"
                           required autofocus
                           placeholder="000000"
                           maxlength="6"
                           inputmode="numeric"
                           autocomplete="one-time-code">
                    @error('code')
                        <div class="kb-form-error">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="kb-account-btn kb-account-btn-primary">
                    <i class="bi bi-shield-check"></i> Enable Two-Factor Authentication
                </button>
            </form>
        </div>
    </div>
@endif
@endsection
