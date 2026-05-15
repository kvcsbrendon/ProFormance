{{-- resources/views/errors/404-product.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="kb-error-page">
    <div class="kb-error-icon"><i class="bi bi-box-seam"></i></div>
    <h1 class="kb-error-code">404</h1>
    <h2 class="kb-error-title">Product not found</h2>
    <p class="kb-error-message">This product may have been removed or is no longer available.</p>
	<div class="kb-error-actions" style="max-width: 30%; margin: 0 auto; display: flex; justify-content: center; gap: 15px;">
        <a href="{{ route('products.index') }}" class="kb-btn kb-btn-primary"><i class="bi bi-grid"></i> Browse Products</a></a>
      	<a href="{{ url('/') }}" class="kb-btn kb-btn-outline"><i class="bi bi-house"></i> Go Home</a></a>
    </div>
</div>
@endsection
