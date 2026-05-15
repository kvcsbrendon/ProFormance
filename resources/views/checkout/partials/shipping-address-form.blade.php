@if($addresses->isNotEmpty())
    <div class="kb-checkout-saved-addresses">
        <p class="kb-checkout-hint">Select a saved address or enter a new one below.</p>

        <div class="kb-checkout-address-options">
            @foreach($addresses as $address)
                <label class="kb-checkout-address-card">
                    <input type="radio"
                        name="address_id"
                        value="{{ $address->address_id }}"
                        data-country="{{ $address->country_code }}"
                        {{ $address->is_default_shipping_address ? 'checked' : '' }}
                        onchange="toggleManualAddress(this)">
                    <div class="kb-checkout-address-card-body">
                        @if($address->is_default_shipping_address)
                            <span class="kb-badge kb-badge-shipping">Default</span>
                        @endif
                        <strong>{{ $address->recipient_name }}</strong>
                        <span>{{ $address->house_number }} {{ $address->address_line_one }}</span>
                        <span>{{ $address->city }}, {{ $address->postcode }}</span>
                    </div>
                </label>
            @endforeach

            <label class="kb-checkout-address-card kb-checkout-address-new">
                <input type="radio"
                    name="address_id"
                    value=""
                    data-country=""
                    onchange="toggleManualAddress(this)">
                <div class="kb-checkout-address-card-body">
                    <i class="bi bi-plus-circle"></i>
                    <strong>New Address</strong>
                </div>
            </label>
        </div>
    </div>
@endif

{{-- Manual address fields --}}
<div id="manual-address-fields" class="kb-checkout-manual-fields"
     style="{{ $addresses->isNotEmpty() && $addresses->where('is_default_shipping_address', true)->isNotEmpty() ? 'display:none;' : '' }}">

    <div class="kb-form-group">
        <label class="kb-form-label required">Full Name</label>
        <input type="text" name="ship_recipient_name" class="kb-form-input"
               value="{{ old('ship_recipient_name', $user->first_name . ' ' . $user->last_name) }}">
        @error('ship_recipient_name') <div class="kb-form-error">{{ $message }}</div> @enderror
    </div>

    <div class="kb-form-row">
        <div class="kb-form-group kb-form-group-small">
            <label class="kb-form-label required">House No.</label>
            <input type="text" name="ship_house_number" class="kb-form-input"
                   value="{{ old('ship_house_number') }}">
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label required">Address Line 1</label>
            <input type="text" name="ship_address_line_one" class="kb-form-input"
                   value="{{ old('ship_address_line_one') }}">
            @error('ship_address_line_one') <div class="kb-form-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="kb-form-group">
        <label class="kb-form-label">Address Line 2 <span class="kb-form-optional">(optional)</span></label>
        <input type="text" name="ship_address_line_two" class="kb-form-input"
               value="{{ old('ship_address_line_two') }}">
    </div>

    <div class="kb-form-row">
        <div class="kb-form-group">
            <label class="kb-form-label required">City</label>
            <input type="text" name="ship_city" class="kb-form-input"
                   value="{{ old('ship_city') }}">
            @error('ship_city') <div class="kb-form-error">{{ $message }}</div> @enderror
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">County <span class="kb-form-optional">(optional)</span></label>
            <input type="text" name="ship_county" class="kb-form-input"
                   value="{{ old('ship_county') }}">
        </div>
    </div>

    <div class="kb-form-row">
        <div class="kb-form-group">
            <label class="kb-form-label required">Postcode</label>
            <input type="text" name="ship_postcode" class="kb-form-input"
                value="{{ old('ship_postcode') }}">
            @error('ship_postcode') <div class="kb-form-error">{{ $message }}</div> @enderror
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label required">Country</label>
            <select name="ship_country_code" id="ship-country-select" class="kb-form-input"
                    onchange="updateCheckoutTotals()">
                @if(isset($countries))
                    @foreach($countries as $code => $name)
                        <option value="{{ $code }}"
                            {{ old('ship_country_code', 'GB') === $code ? 'selected' : '' }}>
                            {{ $name }} ({{ $code }})
                        </option>
                    @endforeach
                @else
                    <option value="GB" selected>United Kingdom (GB)</option>
                @endif
            </select>
            @error('ship_country_code') <div class="kb-form-error">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="kb-form-group">
        <label class="kb-form-label">Phone Number <span class="kb-form-optional">(optional)</span></label>
        <input type="text" name="ship_phone_number" class="kb-form-input"
               value="{{ old('ship_phone_number') }}">
    </div>
</div>