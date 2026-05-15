{{-- resources/views/auth/two-factor-challenge.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="kb-2fa-challenge-page">
    <div class="kb-2fa-challenge-card">
        <div class="kb-2fa-challenge-icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>

        <h1 class="kb-2fa-challenge-title">Two-Factor Authentication</h1>
        <p class="kb-2fa-challenge-desc">
            Enter the 6-digit code from your authenticator app, or use a recovery code.
        </p>

        @if(session('warning'))
            <div class="kb-account-alert kb-account-alert-warning">
                <i class="bi bi-exclamation-triangle"></i>
                {{ session('warning') }}
            </div>
        @endif

        @if($errors->any())
            <div class="kb-account-alert kb-account-alert-error">
                <i class="bi bi-exclamation-circle"></i>
                @foreach($errors->all() as $error)
                    <span>{{ $error }}</span>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('2fa.verify') }}" class="kb-2fa-challenge-form">
            @csrf
            <div class="kb-form-group">
                <label class="kb-form-label" for="2fa-challenge-code">Authentication Code</label>
                <input type="text" name="code" id="2fa-challenge-code"
                       class="kb-form-input kb-2fa-code-input"
                       required autofocus
                       placeholder="000000"
                       maxlength="30"
                       autocomplete="one-time-code">
            </div>
            <button type="submit" class="kb-account-btn kb-account-btn-primary kb-2fa-challenge-btn">
                <i class="bi bi-check-circle"></i> Verify
            </button>
        </form>

        <div class="kb-2fa-challenge-footer">
            <p>Lost your device? Enter one of your recovery codes above.</p>
            <a href="{{ route('login') }}" class="kb-2fa-challenge-back">
                <i class="bi bi-arrow-left"></i> Back to login
            </a>
        </div>
    </div>
</div>
@endsection
