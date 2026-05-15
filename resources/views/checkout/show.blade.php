{{-- resources/views/checkout/show.blade.php --}}
@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/checkout.css') }}">
@endsection

@section('content')
@if($errors->any())
    <div style="background:red;color:white;padding:20px;">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="kb-checkout-page">
    <div class="kb-checkout-container">

        {{-- LEFT COLUMN: Forms --}}
        <div class="kb-checkout-forms">
            <h1 class="kb-checkout-heading">Checkout</h1>

            <form method="POST" action="{{ route('checkout.store') }}" id="checkout-form">
                @csrf

                @include('checkout.partials.shipping-section')
                @include('checkout.partials.billing-section')
                @include('checkout.partials.payment-section')
                @include('checkout.partials.order-notes')

                {{-- SUBMIT (mobile only) --}}
                <div class="kb-checkout-submit-mobile">
                    <button type="submit" class="kb-account-btn kb-account-btn-primary kb-checkout-place-order-btn">
                        <i class="bi bi-lock-fill"></i> Place Order — {{ $symbol }}{{ number_format(($totalPenny ?? 0) / 100, 2) }}
                    </button>
                </div>
            </form>
        </div>

        {{-- RIGHT COLUMN: Order Summary --}}
        <aside class="kb-checkout-sidebar">
            @include('checkout.partials.order-summary')
        </aside>

    </div>
</div>
@endsection

@section('scripts')
    @include('checkout.partials.checkout-scripts')
@endsection
