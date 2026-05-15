{{-- resources/views/admin/shipping/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')

<div class="kb-admin-section">
    <div class="kb-admin-section-header">
        <div>
            <h1 class="kb-admin-title">Shipping Rates</h1>
            <p class="kb-admin-subtitle">Manage delivery costs per country and method. Prices are net (excl. VAT) — VAT is calculated at checkout.</p>
        </div>
    </div>
</div>

{{-- Existing Rates --}}
@foreach($grouped as $countryCode => $countryRates)
<div class="kb-admin-card" style="margin-bottom: 1rem;">
    <h3 class="kb-admin-card-title">
        <i class="bi bi-geo-alt"></i>
        {{ $countryCode }} — {{ $countryRates->first()->zone_name ?? $countryCode }}
    </h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Zone</th>
                    <th>Method</th>
                    <th>Label</th>
                    <th>Price (pence)</th>
                    <th>Display</th>
                    <th>Active</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($countryRates as $rate)
                <tr>
                    <form method="POST" action="{{ route('admin.shipping.update', $rate->rate_id) }}">
                        @csrf @method('PUT')
                        <td>
                            <input type="text" name="zone_name" value="{{ $rate->zone_name }}"
                                   class="kb-form-input" style="width:120px;">
                        </td>
                        <td><span class="kb-admin-mono">{{ $rate->method_key }}</span></td>
                        <td>
                            <input type="text" name="method_label" value="{{ $rate->method_label }}"
                                   class="kb-form-input" style="width:150px;">
                        </td>
                        <td>
                            <input type="number" name="price_penny" value="{{ $rate->price_penny }}"
                                   class="kb-form-input" style="width:100px;" min="0">
                        </td>
                        <td>&pound;{{ number_format($rate->price_penny / 100, 2) }}</td>
                        <td>
                            <input type="checkbox" name="is_active" value="1" {{ $rate->is_active ? 'checked' : '' }}>
                        </td>
                        <td>
                            <input type="number" name="sort_order" value="{{ $rate->sort_order }}"
                                   class="kb-form-input" style="width:60px;" min="0">
                        </td>
                        <td class="kb-admin-actions">
                            <button type="submit" class="kb-admin-btn-sm">Save</button>
                    </form>
                            <form method="POST" action="{{ route('admin.shipping.destroy', $rate->rate_id) }}" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-danger"
                                        onclick="return confirm('Delete this rate?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

@if($rates->isEmpty())
    <div class="kb-admin-card">
        <p class="kb-admin-muted">No shipping rates configured. Add your first rate below.</p>
    </div>
@endif

{{-- Add New Rate --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-plus-circle"></i> Add Shipping Rate</h3>
    <form method="POST" action="{{ route('admin.shipping.store') }}">
        @csrf
        <div class="kb-admin-form-row" style="flex-wrap: wrap; gap: 0.75rem;">
            <div class="kb-form-group">
                <label class="kb-form-label">Zone Name</label>
                <input type="text" name="zone_name" class="kb-form-input" style="width:130px;"
                       placeholder="e.g. UK" required>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Country Code</label>
                <input type="text" name="country_code" class="kb-form-input" style="width:70px;"
                       placeholder="GB" maxlength="4" required>
                <span class="kb-form-hint">Use INTL for international fallback</span>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Method Key</label>
                <input type="text" name="method_key" class="kb-form-input" style="width:120px;"
                       placeholder="standard" required>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Method Label</label>
                <input type="text" name="method_label" class="kb-form-input" style="width:160px;"
                       placeholder="Standard Delivery" required>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Price (pence, net)</label>
                <input type="number" name="price_penny" class="kb-form-input" style="width:100px;"
                       placeholder="799" min="0" required>
                <span class="kb-form-hint">e.g. 799 = £7.99</span>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Sort Order</label>
                <input type="number" name="sort_order" class="kb-form-input" style="width:60px;"
                       value="0" min="0">
            </div>
            <div class="kb-form-group" style="align-self: flex-end;">
                <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-accent">
                    <i class="bi bi-plus"></i> Add Rate
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Info --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-info-circle"></i> How It Works</h3>
    <p class="kb-admin-muted" style="line-height:1.7;">
        Prices are stored as <strong>net (excl. VAT)</strong>. VAT is automatically added at checkout for UK orders (20%).
        The system looks up rates by country code and method key. If no rate is found for a country, it falls back to the
        <strong>INTL</strong> rate. Common method keys are <code>standard</code> and <code>next_day</code>.
    </p>
</div>
@endsection
