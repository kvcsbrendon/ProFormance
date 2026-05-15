{{-- checkout/partials/shipping-section.blade.php --}}
<div class="kb-checkout-section">
    <div class="kb-checkout-section-header">
        <span class="kb-checkout-step-num">1</span>
        <h2 class="kb-checkout-section-title">Shipping Address</h2>
    </div>

    {{-- Pure Gift Cart --}}
    @if($isGift)
        <div class="alert alert-info">
            <i class="bi bi-gift-fill"></i>
            <strong>Gift Order</strong> - This item will be delivered directly to the gift recipient.
        </div>

        <div class="kb-checkout-gift-privacy-notice">
            <div class="kb-privacy-message">
                <i class="bi bi-shield-lock-fill"></i>
                <span>The recipient's address is private and has been securely stored. Your gift will be delivered to them directly.</span>
            </div>
        </div>

        {{-- Hidden shipping fields so validation passes --}}
        <input type="hidden" name="address_id" value="">
        <input type="hidden" name="ship_recipient_name" value="{{ $giftShippingPreview['recipient_name'] ?? '' }}">
        <input type="hidden" name="ship_house_number" value="{{ $giftShippingPreview['house_number'] ?? '' }}">
        <input type="hidden" name="ship_address_line_one" value="{{ $giftShippingPreview['address_line_one'] ?? '' }}">
        <input type="hidden" name="ship_address_line_two" value="{{ $giftShippingPreview['address_line_two'] ?? '' }}">
        <input type="hidden" name="ship_city" value="{{ $giftShippingPreview['city'] ?? '' }}">
        <input type="hidden" name="ship_county" value="{{ $giftShippingPreview['county'] ?? '' }}">
        <input type="hidden" name="ship_postcode" value="{{ $giftShippingPreview['postcode'] ?? '' }}">
        <input type="hidden" name="ship_country_code" value="{{ $giftShippingPreview['country_code'] ?? 'GB' }}">
        <input type="hidden" name="ship_phone_number" value="{{ $giftShippingPreview['phone_number'] ?? '' }}">

    {{-- Mixed Cart (Gift + Non-Gift items) --}}
    @elseif($hasMixedCart)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Mixed Basket</strong> - Your basket contains both gift and non-gift items.
        </div>

        @if(!empty($giftItems))
            <div class="kb-checkout-gift-summary mb-3">
                <h4>Gift Items ({{ count($giftItems) }})</h4>
                <div class="kb-checkout-gift-items-list">
                    @foreach($giftItems as $giftItem)
                        <div class="kb-checkout-item kb-checkout-gift-item">
                            <div class="kb-checkout-item-info">
                                <span class="kb-checkout-item-name">{{ $giftItem['name'] }}</span>
                                <span class="kb-checkout-item-qty">Qty: {{ $giftItem['quantity'] }}</span>
                            </div>
                            <span class="kb-badge kb-badge-gift">
                                <i class="bi bi-gift-fill"></i> Gift - Delivered to recipient
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="kb-privacy-note-small">
                    <i class="bi bi-shield-check"></i>
                    <span>Recipient addresses are private and not shown</span>
                </div>
            </div>
        @endif

        <h4 class="mt-3 mb-2">Your Delivery Address (for non-gift items)</h4>
        @include('checkout.partials.shipping-address-form', ['showManualFields' => true])

    {{-- Normal Checkout (No Gifts) --}}
    @else
        @include('checkout.partials.shipping-address-form', ['showManualFields' => true])
    @endif

    <div class="kb-checkout-save-option" id="save-address-option">
        <label class="kb-checkout-checkbox">
            <input type="checkbox" name="save_address" value="1">
            <span>Save this address for future orders</span>
        </label>
    </div>

    {{-- Delivery Option --}}
    @if($hasMixedCart)
        <div class="kb-form-group mt-3">
            <label class="kb-form-label">Delivery for your items</label>
            @foreach($shippingMethods as $sm)
            <label class="kb-radio">
                <input type="radio" name="shipping_method" value="{{ $sm->method_key }}"
                    {{ ($selectedShippingMethod ?? $shippingMethods->first()->method_key) === $sm->method_key ? 'checked' : '' }}
                    onchange="updateShippingTotals()">
                <span>{{ $sm->method_label }} — {{ $symbol ?? '£' }}{{ number_format($sm->price_penny / 100, 2) }}</span>
            </label>
            @endforeach
        </div>

        <div class="kb-form-group mt-3">
            <label class="kb-form-label"><i class="bi bi-gift"></i> Delivery for gift items</label>
            @foreach($giftShippingMethods as $sm)
            <label class="kb-radio">
                <input type="radio" name="gift_shipping_method" value="{{ $sm->method_key }}"
                    {{ $loop->first ? 'checked' : '' }}
                    onchange="updateShippingTotals()">
                <span>{{ $sm->method_label }} — {{ $symbol ?? '£' }}{{ number_format($sm->price_penny / 100, 2) }}</span>
            </label>
            @endforeach
        </div>
    @else
        <div class="kb-form-group mt-3">
            <label class="kb-form-label required">Delivery option</label>
            @foreach($shippingMethods as $sm)
            <label class="kb-radio">
                <input type="radio" name="shipping_method" value="{{ $sm->method_key }}"
                    {{ ($selectedShippingMethod ?? $shippingMethods->first()->method_key) === $sm->method_key ? 'checked' : '' }}
                    onchange="updateShippingTotals()">
                <span>{{ $sm->method_label }} — {{ $symbol ?? '£' }}{{ number_format($sm->price_penny / 100, 2) }}</span>
            </label>
            @endforeach
        </div>
    @endif
</div>
