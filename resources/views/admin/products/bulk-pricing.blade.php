{{-- resources/views/admin/products/bulk-pricing.blade.php --}}
@extends('admin.layout')
@section('admin-content')

<div class="kb-admin-section">
    <div class="kb-admin-section-header">
        <div>
            <h1 class="kb-admin-title">Bulk Pricing</h1>
            <p class="kb-admin-subtitle">
                {{ $variant->product->product_name }} — {{ $variant->title }}
                <span class="kb-admin-mono">({{ $variant->sku }})</span>
            </p>
        </div>
        <a href="{{ route('admin.products.edit', $variant->product_id) }}" class="kb-admin-btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Product
        </a>
    </div>
</div>

{{-- Existing Tiers --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-layers"></i> Pricing Tiers</h3>

    @if($tiers->isEmpty())
        <p class="kb-admin-muted">No bulk pricing tiers set. Customers will pay the standard price regardless of quantity.</p>
    @else
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead>
                    <tr>
                        <th>Currency</th>
                        <th>Min Quantity</th>
                        <th>Price per Unit (pence)</th>
                        <th>Display Price</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tiers as $tier)
                    <tr>
                        <form method="POST" action="{{ route('admin.bulk-pricing.update', $tier->bulk_pricing_id) }}">
                            @csrf @method('PUT')
                            <td>{{ $tier->currency_code }}</td>
                            <td>
                                <input type="number" name="min_quantity" value="{{ $tier->min_quantity }}"
                                       class="kb-form-input" style="width:80px;" min="2">
                            </td>
                            <td>
                                <input type="number" name="price_penny" value="{{ $tier->price_penny }}"
                                       class="kb-form-input" style="width:120px;" min="1">
                            </td>
                            <td>&pound;{{ number_format($tier->price_penny / 100, 2) }}</td>
                            <td>
                                <select name="is_active" class="kb-form-input" style="width:80px;">
                                    <option value="1" {{ $tier->is_active ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ !$tier->is_active ? 'selected' : '' }}>No</option>
                                </select>
                            </td>
                            <td class="kb-admin-actions">
                                <button type="submit" class="kb-admin-btn-sm">Save</button>
                        </form>
                                <form method="POST" action="{{ route('admin.bulk-pricing.destroy', $tier->bulk_pricing_id) }}" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="kb-admin-btn-sm kb-admin-btn-danger"
                                            onclick="return confirm('Remove this tier?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Add New Tier --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-plus-circle"></i> Add Tier</h3>
    <form method="POST" action="{{ route('admin.bulk-pricing.store', $variant->variant_id) }}">
        @csrf
        <div class="kb-admin-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Currency</label>
                <input type="text" name="currency_code" value="GBP" class="kb-form-input"
                       style="width:80px;" maxlength="3" placeholder="GBP">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Minimum Quantity</label>
                <input type="number" name="min_quantity" class="kb-form-input"
                       style="width:100px;" min="2" placeholder="e.g. 2" required>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Price per Unit (pence)</label>
                <input type="number" name="price_penny" class="kb-form-input"
                       style="width:140px;" min="1" placeholder="e.g. 9500" required>
                <span class="kb-form-hint">e.g. 9500 = £95.00</span>
            </div>
            <div class="kb-form-group" style="align-self:flex-end;">
                <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-accent">
                    <i class="bi bi-plus"></i> Add Tier
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Quick guide --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-info-circle"></i> How It Works</h3>
    <p class="kb-admin-muted" style="line-height:1.7;">
        Each tier defines a price per unit when a customer buys at least that many.
        For example, if the standard price is £100 and you add tiers for 2+ at £95 and 5+ at £85,
        a customer buying 3 units pays £95 each, and a customer buying 6 pays £85 each.
        The highest matching tier is always used. Tiers are per-variant and per-currency.
    </p>
</div>
@endsection
