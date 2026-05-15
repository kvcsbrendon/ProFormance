@extends('layouts.app')

@section('content')
<div class="kb-cart-page">
    <div class="kb-cart-container">
        {{-- Header --}}
        <div class="kb-cart-header">
            <h1 class="kb-cart-title">Your Basket</h1>
            @if (!empty($lines))
                <div class="kb-cart-header-meta">
                    <span><strong id="js-total-items">{{ $totalItems }}</strong> items</span>
                </div>
            @endif
        </div>


        @if (empty($lines))
            <div class="kb-cart-empty">
                <div class="kb-empty-illustration">
                    <i class="bi bi-bag"></i>
                </div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added anything to your cart yet.</p>
                <div class="kb-empty-actions">
                    <a href="/" class="kb-btn kb-btn-primary">
                        <i class="bi bi-arrow-left"></i>
                        Continue Shopping
                    </a>
                    <a href="{{ route('products.index') }}" class="kb-btn kb-btn-outline">
                        Browse Products
                    </a>
                </div>
            </div>
        @else
            <div class="kb-cart-grid">
                {{-- LEFT: Cart Items --}}
                <div class="kb-cart-lines">
                    @foreach ($lines as $line)
                        @php
                            $price = $line['price'] ?? 0;
                            $qty = $line['quantity'] ?? 0;
                            $subtotal = $price * $qty;

                            $imgSrc = !empty($line['image'])
                                ? asset($line['image'])
                                : asset('images/placeholders/product-placeholder.jpg');
                            
                            $symbol = $line['symbol'] ?? '£';
                        @endphp

                        <div class="kb-cart-card" 
                             data-line-id="{{ $line['id'] }}"
                             data-price="{{ $price }}">
                            
                            {{-- Product Image --}}
                            <a href="{{ route('products.show', $line['product_id']) }}" class="kb-cart-card-media">
                                <img src="{{ $imgSrc }}" 
                                     alt="{{ $line['name'] ?? 'Product image' }}"
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'">
                            </a>

                            {{-- Card Body --}}
                            <div class="kb-cart-card-body">
                                {{-- Top Row: Title + Remove --}}
                                <div class="kb-cart-card-top">
                                    <div class="kb-cart-card-title">
                                        <a href="{{ route('products.show', $line['product_id']) }}" class="kb-cart-product-name">
                                            {{ $line['name'] ?? 'Unnamed product' }}
                                        </a>
                                        <div class="kb-cart-product-meta">
                                            <span class="kb-cart-unit-price">
                                                {{ $symbol }}{{ number_format($price, 2) }}
                                                @if(!empty($vatLabel))
                                                    <span class="kb-muted"> {{ $vatLabel }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @if(!empty($line['variant']))
                                            <div class="kb-cart-item-variant">
                                                {{ $line['variant'] }}
                                            </div>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('cart.remove', $line['id']) }}">
                                        @csrf
                                        <button type="submit" class="kb-link-danger" aria-label="Remove item">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- Bottom Row: Quantity + Subtotal --}}
                                <div class="kb-cart-card-bottom">
                                    {{-- Quantity controls --}}
                                    <div class="kb-cart-qty-block">
                                        <span class="kb-cart-qty-label">Qty</span>
                                        <div class="kb-cart-qty-controls" role="group" aria-label="Quantity controls">
                                            <button type="button"
                                                    class="kb-qty-btn js-qty-dec"
                                                    data-line-id="{{ $line['id'] }}"
                                                    aria-label="Decrease quantity">−</button>

                                            <form method="POST"
                                                  action="{{ route('cart.update', $line['id']) }}"
                                                  class="kb-cart-qty-form"
                                                  data-line-id="{{ $line['id'] }}">
                                                @csrf
                                                <input type="number"
                                                       name="quantity"
                                                       value="{{ $qty }}"
                                                       min="1"
                                                       class="kb-cart-qty-input js-qty-input"
                                                       inputmode="numeric"
                                                       aria-label="Quantity">
                                            </form>

                                            <button type="button"
                                                    class="kb-qty-btn js-qty-inc"
                                                    data-line-id="{{ $line['id'] }}"
                                                    aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>

                                    {{-- Subtotal --}}
                                    <div class="kb-cart-line-total">
                                        <div class="kb-cart-line-total-label">Subtotal</div>
                                        <div class="kb-cart-line-total-value">
                                            <span class="js-line-subtotal">{{ $symbol }}{{ number_format($subtotal, 2) }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Status Message --}}
                                <div class="js-row-status" aria-live="polite"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- RIGHT: Order Summary --}}
                <aside class="kb-order-summary">
                    <div class="kb-cart-summary-card">
                        <h2 class="kb-cart-summary-title">Summary</h2>

                        <div class="kb-cart-summary-line">
                            <span>Total items</span>
                            <strong id="js-total-items-2">{{ $totalItems }}</strong>
                        </div>

                        <div class="kb-cart-summary-line">
                            <span>Subtotal</span>
                            <span>{{ $symbol }}<span id="js-total">{{ number_format($total, 2) }}</span></span>
                        </div>

                        <div class="kb-cart-summary-line">
                            <span>Shipping</span>
                            <span class="kb-shipping-estimate">Free for ProFormance Plus</span>
                        </div>

                        @if(!empty($vatLabel))
                            <div class="kb-cart-summary-line kb-summary-tax-row">
                                <span>VAT</span>
                                <span class="kb-muted">{{ $vatLabel }}</span>
                            </div>
                        @endif

                        <div class="kb-cart-summary-line kb-cart-summary-total">
                            <span>Total</span>
                            <strong>{{ $symbol }}<span id="js-total-final">{{ number_format($total, 2) }}</span></strong>
                        </div>

                        <div class="kb-cart-summary-actions">
                            <a href="/checkout" class="kb-btn kb-btn-primary kb-btn-block">
                                <i class="bi bi-lock"></i> Proceed to Checkout
                            </a>
                            
                            <a href="/" class="kb-btn kb-btn-outline kb-btn-block">
                                <i class="bi bi-arrow-left"></i> Continue Shopping
                            </a>

                            <form method="POST" action="{{ route('cart.clear') }}" class="kb-clear-cart-form">
                                @csrf
                                <button type="submit" class="kb-btn kb-btn-ghost kb-btn-block" onclick="return confirm('Are you sure you want to clear your cart?')">
                                    <i class="bi bi-trash"></i> Clear Cart
                                </button>
                            </form>
                        </div>
                        
                        <div class="kb-payment-badges">
                            <p class="kb-payment-text">Secure checkout with</p>
                            <div class="kb-payment-icons">
                                <i class="bi bi-credit-card"></i>
                                <i class="bi bi-paypal"></i>
                                <span>+ more</span>
                            </div>
                        </div>
                    </div>

                    <div class="kb-cart-guarantees">
                        <div class="kb-guarantee-item">
                            <i class="bi bi-shield-check"></i>
                            <span>Secure payment</span>
                        </div>
                        <div class="kb-guarantee-item">
                            <i class="bi bi-arrow-return-left"></i>
                            <span>Free returns</span>
                        </div>
                        <div class="kb-guarantee-item">
                            <i class="bi bi-star"></i>
                            <span>ProFormance Plus</span>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</div>

<script>
    (function() {
        'use strict';
        
        function initQuantityControls() {
            const decrementBtns = document.querySelectorAll('.js-qty-dec');
            const incrementBtns = document.querySelectorAll('.js-qty-inc');
            
            if (decrementBtns.length === 0 && incrementBtns.length === 0) {
                return;
            }
            
            decrementBtns.forEach(btn => {
                btn.removeEventListener('click', handleQuantityClick);
                btn.addEventListener('click', handleQuantityClick);
            });
            
            incrementBtns.forEach(btn => {
                btn.removeEventListener('click', handleQuantityClick);
                btn.addEventListener('click', handleQuantityClick);
            });
        }
        
        function handleQuantityClick(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const btn = e.currentTarget;
            const lineId = btn.dataset.lineId;
            
            const qtyControls = btn.closest('.kb-cart-qty-controls');
            if (!qtyControls) return;
            
            const input = qtyControls.querySelector('.kb-cart-qty-input, .js-qty-input');
            if (!input) return;
            
            let currentVal = parseInt(input.value) || 1;
            let newVal = currentVal;
            let shouldSubmit = true;
            
            if (btn.classList.contains('js-qty-dec')) {
                if (currentVal > 1) {
                    newVal = currentVal - 1;
                } else {
                    const removeForm = document.querySelector(`form[action*="cart/remove"][action*="${lineId}"]`);
                    if (removeForm) {
                        removeForm.submit();
                    } else {
                        newVal = 0;
                    }
                    shouldSubmit = false;
                }
            } else if (btn.classList.contains('js-qty-inc')) {
                newVal = currentVal + 1;
            }
            
            if (shouldSubmit && newVal !== currentVal) {
                input.value = newVal;
                
                const form = document.querySelector(`.kb-cart-qty-form[data-line-id="${lineId}"]`);
                if (form) {
                    form.submit();
                }
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initQuantityControls);
        } else {
            initQuantityControls();
        }
    })();
</script>
@endsection