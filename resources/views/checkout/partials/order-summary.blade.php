{{-- checkout/partials/order-summary.blade.php --}}
<div class="kb-checkout-sidebar-sticky">
    <div class="kb-checkout-summary-card">
        <h2 class="kb-checkout-summary-title">Order Summary</h2>

        {{-- Line items --}}
        <div class="kb-checkout-items">
            @foreach($lines as $line)
                <div class="kb-checkout-item">
                    @if(!empty($line['image']))
                        <img src="{{ asset($line['image']) }}" alt="{{ $line['name'] }}"
                             class="kb-checkout-item-img">
                    @else
                        <div class="kb-checkout-item-img kb-checkout-item-img-placeholder">
                            <i class="bi bi-image"></i>
                        </div>
                    @endif
                    <div class="kb-checkout-item-info">
                        <span class="kb-checkout-item-name">{{ $line['name'] }}</span>
                        <span class="kb-checkout-item-qty">Qty: {{ $line['quantity'] }}</span>
                    </div>
                    @php
                        $unitPenny = $line['price_penny'] ?? (int) round(($line['price'] ?? 0) * 100);
                        $linePenny = $unitPenny * (int)($line['quantity'] ?? 0);
                    @endphp
                    <span class="kb-checkout-item-price">
                        {{ $symbol }}{{ number_format($linePenny / 100, 2) }}
                    </span>
                </div>
            @endforeach
        </div>

        {{-- DISCOUNT CODE (AJAX — both states always in DOM) --}}
        <div class="kb-checkout-discount-section">
            <div class="kb-checkout-discount-applied" id="discount-applied"
                 style="{{ $appliedDiscount ? '' : 'display:none;' }}">
                <div class="kb-checkout-discount-info">
                    <i class="bi bi-tag-fill"></i>
                    <span><strong id="discount-code-display">{{ $appliedDiscount['discount_code'] ?? '' }}</strong></span>
                    <span class="kb-checkout-discount-amount" id="discount-amount-display">
                        -{{ $symbol }}{{ number_format(($discountPenny ?? 0) / 100, 2) }}
                    </span>
                </div>
                <button type="button" class="kb-checkout-discount-remove" title="Remove"
                        onclick="removeDiscountAjax(); return false;">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="kb-checkout-discount-form-wrap" id="discount-form-wrap"
                 style="{{ $appliedDiscount ? 'display:none;' : '' }}">
                <div class="kb-checkout-discount-form">
                    <input type="text" id="discount-code-input" class="kb-form-input"
                           placeholder="Discount code">
                    <button type="button" class="kb-account-btn kb-account-btn-outline"
                            id="discount-apply-btn"
                            onclick="applyDiscountAjax(); return false;">Apply</button>
                </div>
                <div id="discount-message" style="margin-top:4px;font-size:12px;"></div>
            </div>
        </div>

        {{-- TOTALS --}}
        <div class="kb-checkout-totals">
            <div class="kb-checkout-total-row">
                <span>Subtotal (excl. VAT)</span>
                <span id="subtotalAmount">{{ $symbol }}{{ number_format(($subtotalExVatPenny ?? 0)/100, 2) }}</span>
            </div>

            <div class="kb-checkout-total-row kb-checkout-discount-row" id="discount-total-row"
                 style="{{ ($discountPenny ?? 0) > 0 ? '' : 'display:none;' }}">
                <span>Discount</span>
                <span id="discountAmount">
                    -{{ $symbol }}{{ number_format(($discountPenny ?? 0)/100, 2) }}
                </span>
            </div>

            <div class="kb-checkout-total-row">
                <span>Shipping</span>
                <span id="shippingAmount">{{ ($subFreeShipping ?? false) ? 'FREE' : $symbol . number_format(($shippingNetPenny ?? 0)/100, 2) }}</span>
            </div>

            <div class="kb-checkout-total-row">
                <span id="vatLabel">
                    @if(($vatRate ?? 0) > 0)
                        VAT ({{ (int)round(($vatRate ?? 0)*100) }}%)
                    @else
                        VAT (0% — Export)
                    @endif
                </span>
                <span id="vatAmount">{{ $symbol }}{{ number_format(($combinedVatPenny ?? 0)/100, 2) }}</span>
            </div>

            <div class="kb-checkout-total-row kb-checkout-grand-total">
                <strong>Total</strong>
                <strong id="totalAmount">{{ $symbol }}{{ number_format(($totalPenny ?? 0)/100, 2) }}</strong>
            </div>

            @include('checkout.partials.subscription-offers')

            @if(isset($destinations) && count($destinations) > 1)
                <div class="kb-form-hint" style="margin-top: 10px; font-size: 12px;">
                    <i class="bi bi-truck"></i>
                    <span>Shipping to multiple destinations:
                        @foreach($destinations as $dest)
                            {{ $dest['country'] }}@if(!$loop->last), @endif
                        @endforeach
                    </span>
                </div>
            @endif

            @if(isset($shipCountryCode) && strtoupper($shipCountryCode) !== 'GB')
                <p class="kb-form-hint" style="margin-top: 10px;">
                    VAT is 0% for export. Import taxes may apply in your country.
                </p>
            @endif
        </div>

        <button type="submit" form="checkout-form"
                class="kb-account-btn kb-account-btn-primary kb-checkout-place-order-btn">
            <i class="bi bi-lock-fill"></i> Place Order
        </button>

        <div class="kb-checkout-trust-badges">
            <div class="kb-checkout-trust-item">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Secure Checkout</span>
            </div>
            <div class="kb-checkout-trust-item">
                <i class="bi bi-arrow-return-left"></i>
                <span>Free Returns</span>
            </div>
        </div>
    </div>
</div>
