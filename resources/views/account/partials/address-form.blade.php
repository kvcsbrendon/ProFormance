@php $addr = $addr ?? null; @endphp

<div class="kb-form-group">
    <label class="kb-form-label">Recipient Name</label>
    <input type="text" name="recipient_name" class="kb-form-input"
           value="{{ old('recipient_name', $addr->recipient_name ?? '') }}" required>
</div>

<div class="kb-form-row">
    <div class="kb-form-group kb-form-group-small">
        <label class="kb-form-label">House No.</label>
        <input type="text" name="house_number" class="kb-form-input"
               value="{{ old('house_number', $addr->house_number ?? '') }}" required>
    </div>
    <div class="kb-form-group">
        <label class="kb-form-label">Address Line 1</label>
        <input type="text" name="address_line_one" class="kb-form-input"
               value="{{ old('address_line_one', $addr->address_line_one ?? '') }}" required>
    </div>
</div>

<div class="kb-form-group">
    <label class="kb-form-label">Address Line 2 <span class="kb-form-optional">(optional)</span></label>
    <input type="text" name="address_line_two" class="kb-form-input"
           value="{{ old('address_line_two', $addr->address_line_two ?? '') }}">
</div>

<div class="kb-form-row">
    <div class="kb-form-group">
        <label class="kb-form-label">City</label>
        <input type="text" name="city" class="kb-form-input"
               value="{{ old('city', $addr->city ?? '') }}" required>
    </div>
    <div class="kb-form-group">
        <label class="kb-form-label">County <span class="kb-form-optional">(optional)</span></label>
        <input type="text" name="county" class="kb-form-input"
               value="{{ old('county', $addr->county ?? '') }}">
    </div>
</div>

<div class="kb-form-row">
    <div class="kb-form-group">
        <label class="kb-form-label">Postcode</label>
        <input type="text" name="postcode" class="kb-form-input"
               value="{{ old('postcode', $addr->postcode ?? '') }}" required>
    </div>
    <div class="kb-form-group">
    <label class="kb-form-label required">Country</label>
    <select name="country_code" class="kb-form-input">
        @if(isset($countries))
            @foreach($countries as $code => $name)
                <option value="{{ $code }}"
                    {{ (old('country_code', $addr->country_code ?? 'GB')) === $code ? 'selected' : '' }}>
                    {{ $name }} ({{ $code }})
                </option>
            @endforeach
        @else
            <option value="GB" selected>United Kingdom (GB)</option>
        @endif
    </select>
</div>
</div>

<div class="kb-form-row">
    <div class="kb-form-group kb-form-group-small">
        <label class="kb-form-label">Phone Code</label>
        <input type="number" name="country_phone_code" class="kb-form-input"
               value="{{ old('country_phone_code', $addr->country_phone_code ?? 44) }}" required>
    </div>
    <div class="kb-form-group">
        <label class="kb-form-label">Phone Number <span class="kb-form-optional">(optional)</span></label>
        <input type="text" name="phone_number" class="kb-form-input"
               value="{{ old('phone_number', $addr->phone_number ?? '') }}">
    </div>
</div>

<div class="kb-form-row kb-form-checkboxes">
    <label class="kb-checkbox-label">
        <input type="hidden" name="is_default_shipping_address" value="0">
        <input type="checkbox" name="is_default_shipping_address" value="1"
               {{ old('is_default_shipping_address', $addr->is_default_shipping_address ?? false) ? 'checked' : '' }}>
        <span>Set as default shipping address</span>
    </label>
    <label class="kb-checkbox-label">
        <input type="hidden" name="is_default_billing_address" value="0">
        <input type="checkbox" name="is_default_billing_address" value="1"
               {{ old('is_default_billing_address', $addr->is_default_billing_address ?? false) ? 'checked' : '' }}>
        <span>Set as default billing address</span>
    </label>
</div>
