{{-- resources/views/errors/500.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="kb-error-page">
    <div class="kb-error-icon"><i class="bi bi-exclamation-triangle"></i></div>
    <h1 class="kb-error-code">500</h1>
    <h2 class="kb-error-title">Something went wrong</h2>
    <p class="kb-error-message">We're having technical difficulties. Please try again in a moment.</p>
    <div class="kb-error-actions" style="max-width: 30%; margin: 0 auto; display: flex; justify-content: center; gap: 15px;">
        <a href="{{ url('/') }}" class="kb-btn kb-btn-primary"><i class="bi bi-house"></i> Go Home</a>
      	<a href="javascript:location.reload()" class="kb-btn kb-btn-outline"><i class="bi bi-arrow-clockwise"></i> Try Again</a>
    </div>
</div>
@endsection
