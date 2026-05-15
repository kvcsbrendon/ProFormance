@extends('layouts.app')

@section('title', 'Sign In - ProFormance')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <h1 class="auth-title">Sign In</h1>
        <p class="auth-subtitle">Enter your details to access your account</p>
       

        @if($errors->has('google'))
            <div class="kb-auth-alert kb-auth-alert-error">
                <i class="bi bi-exclamation-circle"></i> {{ $errors->first('google') }}
            </div>
        @endif

        {{-- Google Sign-In --}}
		<div class="form-group">
        	<a href="{{ route('auth.google') }}" class="kb-google-btn">
            	<svg class="kb-google-icon" viewBox="0 0 24 24" width="20" height="20">
                	<path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                	<path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                	<path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                	<path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            	</svg>
            	<span>Continue with Google</span>
        	</a>
        </div>

        <div class="kb-auth-divider">
            <span>or</span>
        </div>
        
        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            
            <div class="form-group">
                <label class="required">Email Address</label>
                <input type="email" 
                       name="email" 
                       required 
                       value="{{ old('email', session('checked_email', '')) }}"
                       placeholder="your.email@example.com"
                       class="form-input">
            </div>
            
            <div class="form-group">
                <label class="required">Password</label>
                <input type="password" 
                       name="password" 
                       required 
                       placeholder="Enter your password"
                       class="form-input"
                       {{ session('checked_email') ? 'autofocus' : '' }}>
            </div>
            
            <button type="submit" class="submit-btn">
                Sign In
            </button>
            
            <div class="auth-footer">
                <p class="footer-text">Don't have an account?</p>
                <a href="{{ route('register') }}" class="footer-link">
                    Create an account
                </a>
                <a href="{{ route('password.forgot') }}" class="forgot-link">Forgot Password?</a>
            </div>
        </form>
            		<div style="display: flex; justify-content: center; width: 100%; margin: 20px 0;">

    <div style="
        background-color: var(--kb-primary-bg, #f8f9fa); 
        color: var(--kb-primary-font, #333);
        border: 1px dashed var(--kb-primary-font, #ccc); 
        padding: 20px; 
        border-radius: 8px; 
        font-family: sans-serif;
        text-align: center;
        width: 320px;
        transition: background-color 0.3s, color 0.3s; /* Smooth transition when toggling */
    ">
        <label style="opacity: 0.7; font-size: 14px; font-weight: 600; display: block; margin-bottom: 12px; text-transform: uppercase;">
            Tester Credentials
        </label>

        <div style="margin-bottom: 15px;">
            <span style="opacity: 0.6; font-size: 11px; display: block;">EMAIL</span>
            <div style="
                background: rgba(128, 128, 128, 0.1); /* Semi-transparent so it works on any bg */
                border: 1px solid var(--kb-primary-font, #ddd); 
                padding: 5px; 
                font-family: monospace; 
                cursor: pointer; 
                user-select: all;
            ">kvcsbrendon@gmail.com</div>
        </div>

        <div style="margin-bottom: 5px;">
            <span style="opacity: 0.6; font-size: 11px; display: block;">PASSWORD</span>
            <div style="
                background: rgba(128, 128, 128, 0.1); 
                border: 1px solid var(--kb-primary-font, #ddd); 
                padding: 5px; 
                font-family: monospace;
                cursor: pointer; 
                user-select: all;
            ">4$Eew.Xh3@TBLHr</div>
        </div>
    </div>

</div>
    </div>
</div>

@endsection
