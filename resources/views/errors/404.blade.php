{{-- resources/views/errors/404.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="kb-error-page">
    <div class="kb-error-icon"><i class="bi bi-search"></i></div>
    <h1 class="kb-error-code">404</h1>
    <h2 class="kb-error-title">Page not found</h2>
    <p class="kb-error-message">Sorry, the page you're looking for doesn't exist or has been moved.</p>
	<div class="kb-error-actions" style="max-width: 30%; margin: 0 auto; display: flex; justify-content: center; gap: 15px;">
        <a href="{{ route('products.index') }}" class="kb-btn kb-btn-primary"><i class="bi bi-grid"></i> Browse Products</a></a>
      	<a href="{{ url('/') }}" class="kb-btn kb-btn-outline"><i class="bi bi-house"></i> Go Home</a></a>
    </div>
</div>
@endsection
