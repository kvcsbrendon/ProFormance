{{-- checkout/partials/billing-section.blade.php --}}
<div class="kb-checkout-section">
    <div class="kb-checkout-section-header">
        <span class="kb-checkout-step-num">2</span>
        <h2 class="kb-checkout-section-title required">Billing Address</h2>
    </div>

    @if($isGift)
        {{-- Gift orders: billing cannot be same as shipping (privacy) --}}
        <div class="kb-form-hint" style="margin-bottom: 1rem;">
            <i class="bi bi-shield-lock"></i>
            <span>For gift orders, please enter a separate billing address to protect the recipient's privacy.</span>
        </div>
    @else
        <label class="kb-checkbox-label kb-checkout-same-billing">
            <input type="checkbox" name="billing_same" value="1" id="billing-same-checkbox"
                   checked onchange="toggleBilling()">
            <span>Same as shipping address</span>
        </label>
    @endif

    <div id="billing-fields" class="kb-checkout-manual-fields" style="{{ $isGift ? '' : 'display:none;' }}">

        {{-- Saved addresses --}}
        @if($addresses->isNotEmpty())
            <div class="kb-checkout-saved-addresses" style="margin-bottom: 14px;">
                @foreach($addresses as $addr)
                    <label class="kb-radio kb-checkout-address-option">
                        <input type="radio" name="billing_address_id" value="{{ $addr->address_id }}"
                               {{ $loop->first ? 'checked' : '' }}
                               onchange="toggleManualBilling(this)">
                        <div class="kb-checkout-address-summary">
                            <strong>{{ $addr->recipient_name }}</strong>
                            <span>{{ $addr->house_number }} {{ $addr->address_line_one }}, {{ $addr->city }}, {{ $addr->postcode }}</span>
                        </div>
                    </label>
                @endforeach

                <label class="kb-radio kb-checkout-address-option">
                    <input type="radio" name="billing_address_id" value=""
                           onchange="toggleManualBilling(this)">
                    <span>Enter a new billing address</span>
                </label>
            </div>
        @endif

        {{-- Manual billing fields --}}
        <div id="manual-billing-fields" style="{{ $addresses->isNotEmpty() ? 'display:none;' : '' }}">
            <div class="kb-form-group">
                <label class="kb-form-label required">Full Name</label>
                <input type="text" name="bill_recipient_name" class="kb-form-input"
                       value="{{ old('bill_recipient_name') }}">
            </div>

            <div class="kb-form-row">
                <div class="kb-form-group kb-form-group-small">
                    <label class="kb-form-label required">House No.</label>
                    <input type="text" name="bill_house_number" class="kb-form-input"
                           value="{{ old('bill_house_number') }}">
                </div>
                <div class="kb-form-group">
                    <label class="kb-form-label required">Address Line 1</label>
                    <input type="text" name="bill_address_line_one" class="kb-form-input"
                           value="{{ old('bill_address_line_one') }}">
                </div>
            </div>

            <div class="kb-form-group">
                <label class="kb-form-label">Address Line 2 <span class="kb-form-optional">(optional)</span></label>
                <input type="text" name="bill_address_line_two" class="kb-form-input"
                       value="{{ old('bill_address_line_two') }}">
            </div>

            <div class="kb-form-row">
                <div class="kb-form-group">
                    <label class="kb-form-label required">City</label>
                    <input type="text" name="bill_city" class="kb-form-input"
                           value="{{ old('bill_city') }}">
                </div>
                <div class="kb-form-group">
                    <label class="kb-form-label">County <span class="kb-form-optional">(optional)</span></label>
                    <input type="text" name="bill_county" class="kb-form-input"
                           value="{{ old('bill_county') }}">
                </div>
            </div>

            <div class="kb-form-row">
                <div class="kb-form-group">
                    <label class="kb-form-label required">Postcode</label>
                    <input type="text" name="bill_postcode" class="kb-form-input"
                           value="{{ old('bill_postcode') }}">
                </div>
                <div class="kb-form-group">
                    <label class="kb-form-label required">Country</label>
                    <select name="bill_country_code" class="kb-form-input">
                        @if(isset($countries))
                            @foreach($countries as $code => $name)
                                <option value="{{ $code }}"
                                    {{ old('bill_country_code', 'GB') === $code ? 'selected' : '' }}>
                                    {{ $name }} ({{ $code }})
                                </option>
                            @endforeach
                        @else
                            <option value="GB" selected>United Kingdom (GB)</option>
                        @endif
                    </select>
                    @error('bill_country_code') <div class="kb-form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="kb-form-group">
                <label class="kb-form-label">Phone Number <span class="kb-form-optional">(optional)</span></label>
                <input type="text" name="bill_phone_number" class="kb-form-input"
                       value="{{ old('bill_phone_number') }}">
            </div>
        </div>
    </div>
</div>
