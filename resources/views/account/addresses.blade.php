@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <div class="kb-account-section-header">
        <div>
            <h1 class="kb-account-title">My Addresses</h1>
            <p class="kb-account-subtitle">Manage your shipping and billing addresses.</p>
        </div>
        <button type="button" class="kb-account-btn kb-account-btn-primary" onclick="document.getElementById('add-address-form').style.display = document.getElementById('add-address-form').style.display === 'none' ? 'block' : 'none'">
            <i class="bi bi-plus"></i> Add Address
        </button>
    </div>
</div>

{{-- ADD ADDRESS FORM (hidden by default) --}}
<div id="add-address-form" class="kb-account-card" style="display: none;">
    <h2 class="kb-account-card-title">Add New Address</h2>
    <form method="POST" action="{{ route('account.addresses.store') }}">
        @csrf
        @include('account.partials.address-form')
        <div class="kb-form-actions">
            <button type="submit" class="kb-account-btn kb-account-btn-primary">Save Address</button>
            <button type="button" class="kb-account-btn kb-account-btn-outline" onclick="document.getElementById('add-address-form').style.display='none'">Cancel</button>
        </div>
    </form>
</div>

{{-- ADDRESSES LIST --}}
@if($addresses->isEmpty())
    <div class="kb-account-empty">
        <i class="bi bi-geo-alt"></i>
        <p>No saved addresses yet.</p>
    </div>
@else
    <div class="kb-account-address-grid">
        @foreach($addresses as $address)
            <div class="kb-account-card kb-address-card">
                <div class="kb-address-card-header">
                    <div class="kb-address-badges">
                        @if($address->is_default_shipping_address)
                            <span class="kb-badge kb-badge-shipping">Default Shipping</span>
                        @endif
                        @if($address->is_default_billing_address)
                            <span class="kb-badge kb-badge-billing">Default Billing</span>
                        @endif
                    </div>
                    <div class="kb-address-card-actions">
                        <button type="button" class="kb-icon-btn" title="Edit"
                                onclick="toggleEdit({{ $address->address_id }})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form method="POST" action="{{ route('account.addresses.destroy', $address->address_id) }}"
                              onsubmit="return confirm('Remove this address?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kb-icon-btn kb-icon-btn-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="kb-address-display">
                    <p><strong>{{ $address->recipient_name }}</strong></p>
                    <p>{{ $address->house_number }} {{ $address->address_line_one }}</p>
                    @if($address->address_line_two)
                        <p>{{ $address->address_line_two }}</p>
                    @endif
                    <p>{{ $address->city }}{{ $address->county ? ', ' . $address->county : '' }}</p>
                    <p>{{ $address->postcode }}</p>
                    <p>{{ $address->country_code }}</p>
                    @if($address->phone_number)
                        <p><i class="bi bi-telephone"></i> +{{ $address->country_phone_code }} {{ $address->phone_number }}</p>
                    @endif
                </div>

                {{-- INLINE EDIT FORM (hidden by default) --}}
                <div id="edit-address-{{ $address->address_id }}" class="kb-address-edit-form" style="display: none;">
                    <form method="POST" action="{{ route('account.addresses.update', $address->address_id) }}">
                        @csrf
                        @method('PUT')
                        @include('account.partials.address-form', ['addr' => $address])
                        <div class="kb-form-actions">
                            <button type="submit" class="kb-account-btn kb-account-btn-primary">Update</button>
                            <button type="button" class="kb-account-btn kb-account-btn-outline"
                                    onclick="toggleEdit({{ $address->address_id }})">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection

@section('scripts')
<script>
    function toggleEdit(id) {
        const form = document.getElementById('edit-address-' + id);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>
@endsection
