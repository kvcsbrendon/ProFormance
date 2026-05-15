{{-- resources/views/errors/403.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="kb-error-page">
    <div class="kb-error-icon"><i class="bi bi-shield-lock"></i></div>
    <h1 class="kb-error-code">403</h1>
    <h2 class="kb-error-title">Access denied</h2>
    <p class="kb-error-message">You don't have permission to access this page.</p>
    <div class="kb-error-actions" style="max-width: 30%; margin: 0 auto; display: flex; justify-content: center; gap: 15px;">
        <a href="{{ url('/') }}" class="kb-btn kb-btn-primary"><i class="bi bi-house"></i> Go Home</a></a>
      	<a href="javascript:history.back()" class="kb-btn kb-btn-outline"><i class="bi bi-arrow-left"></i> Go Back</a></a>
    </div>
</div>
@endsection
