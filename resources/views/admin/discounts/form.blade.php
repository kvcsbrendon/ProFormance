{{-- resources/views/admin/discounts/form.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.discounts.index') }}" class="kb-admin-back"><i class="bi bi-arrow-left"></i> Back to Discounts</a>
    <h1 class="kb-admin-title">{{ $discount ? 'Edit Discount' : 'Create Discount' }}</h1>
</div>

<form method="POST" action="{{ $discount ? route('admin.discounts.update', $discount->discount_id) : route('admin.discounts.store') }}">
    @csrf
    @if($discount) @method('PUT') @endif

    <div class="kb-admin-card" style="max-width:700px;">
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Discount Code *</label>
                <input type="text" name="discount_code" class="kb-form-input" required style="text-transform:uppercase"
                       value="{{ old('discount_code', $discount->discount_code ?? '') }}">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Status</label>
                <select name="is_active" class="kb-form-input">
                    <option value="1" {{ old('is_active', $discount->is_active ?? 1) ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ !old('is_active', $discount->is_active ?? 1) ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Type *</label>
                <select name="discoun_type" class="kb-form-input" required>
                    <option value="percentage" {{ old('discoun_type', $discount->discoun_type ?? '') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                    <option value="fixed_amount" {{ old('discoun_type', $discount->discoun_type ?? '') === 'fixed_amount' ? 'selected' : '' }}>Fixed Amount (£)</option>
                </select>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Value *</label>
                <input type="number" name="discount_value" class="kb-form-input" step="0.01" min="0" required
                       value="{{ old('discount_value', $discount->discount_value ?? '') }}">
            </div>
        </div>
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Start Date</label>
                <input type="date" name="starts_at" class="kb-form-input"
                       value="{{ old('starts_at', $discount && $discount->starts_at ? $discount->starts_at->format('Y-m-d') : '') }}">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">End Date</label>
                <input type="date" name="ends_at" class="kb-form-input"
                       value="{{ old('ends_at', $discount && $discount->ends_at ? $discount->ends_at->format('Y-m-d') : '') }}">
            </div>
        </div>
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Usage Limit (total)</label>
                <input type="number" name="usage_limit" class="kb-form-input" min="1" placeholder="Unlimited"
                       value="{{ old('usage_limit', $discount->usage_limit ?? '') }}">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Per-User Limit</label>
                <input type="number" name="per_user_limit" class="kb-form-input" min="1" placeholder="Unlimited"
                       value="{{ old('per_user_limit', $discount->per_user_limit ?? '') }}">
            </div>
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">Min Subtotal (pence)</label>
            <input type="number" name="min_subtotal_penny" class="kb-form-input" min="0" placeholder="e.g. 5000 = £50.00"
                   value="{{ old('min_subtotal_penny', $discount->min_subtotal_penny ?? '') }}">
        </div>
        @if($discount && $redemptions > 0)
            <p class="kb-admin-muted" style="margin-top:1rem;"><i class="bi bi-info-circle"></i> This code has been redeemed <strong>{{ $redemptions }}</strong> time(s).</p>
        @endif
    </div>

    <div class="kb-admin-form-actions">
        <button type="submit" class="kb-admin-btn"><i class="bi bi-check-circle"></i> {{ $discount ? 'Save Changes' : 'Create Discount' }}</button>
        <a href="{{ route('admin.discounts.index') }}" class="kb-admin-btn-outline">Cancel</a>
    </div>
</form>
@endsection
