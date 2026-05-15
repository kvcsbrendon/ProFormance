{{-- resources/views/errors/429.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="kb-error-page">
    <div class="kb-error-icon"><i class="bi bi-speedometer2"></i></div>
    <h1 class="kb-error-code">429</h1>
    <h2 class="kb-error-title">Too many requests</h2>
    <p class="kb-error-message">You've made too many requests. Please wait a moment and try again.</p>
    <div class="kb-error-actions" style="max-width: 30%; margin: 0 auto; display: flex; justify-content: center; gap: 15px;">
        <a href="javascript:history.back()" class="kb-btn kb-btn-primary"><i class="bi bi-arrow-left"></i> Go Back</a>
      	<a href="{{ url('/') }}" class="kb-btn kb-btn-outline"><i class="bi bi-house"></i> Go Home</a>
    </div>
</div>
@endsection
