{{-- resources/views/errors/419.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="kb-error-page">
    <div class="kb-error-icon"><i class="bi bi-clock-history"></i></div>
    <h1 class="kb-error-code">419</h1>
    <h2 class="kb-error-title">Session expired</h2>
    <p class="kb-error-message">Your session has timed out for security. Please refresh the page and try again.</p>    
    <div class="kb-error-actions" style="max-width: 30%; margin: 0 auto; display: flex; justify-content: center; gap: 15px;">
        <a href="{{ url()->current() }}" class="kb-btn kb-btn-primary"><i class="bi bi-arrow-clockwise"></i> Refresh Page</a></a>
      	<a href="{{ url('/') }}" class="kb-btn kb-btn-outline"><i class="bi bi-house"></i> Go Home</a></a>
    </div>
</div>
@endsection
