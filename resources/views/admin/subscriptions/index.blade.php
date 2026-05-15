{{-- resources/views/admin/subscriptions/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Subscriptions</h1>
    <p class="kb-admin-subtitle">Configure the membership plan, Subscribe &amp; Save settings, and manage customers.</p>
</div>

{{-- Quick Stats with links --}}
<div class="kb-admin-stats-grid" style="margin-bottom: 20px;">
    <a href="{{ route('admin.subscriptions.subscribers') }}" class="kb-admin-stat-card kb-admin-stat-clickable">
        <div class="kb-admin-stat-icon kb-stat-purple"><i class="bi bi-people"></i></div>
        <div class="kb-admin-stat-info">
            <span class="kb-admin-stat-value">{{ $activeSubscribers }}</span>
            <span class="kb-admin-stat-label">Active Subscribers</span>
        </div>
        <i class="bi bi-chevron-right kb-stat-arrow"></i>
    </a>
    <a href="{{ route('admin.subscriptions.ssItems') }}" class="kb-admin-stat-card kb-admin-stat-clickable">
        <div class="kb-admin-stat-icon kb-stat-green"><i class="bi bi-arrow-repeat"></i></div>
        <div class="kb-admin-stat-info">
            <span class="kb-admin-stat-value">{{ $totalSsItems }}</span>
            <span class="kb-admin-stat-label">Active S&amp;S Items</span>
        </div>
        <i class="bi bi-chevron-right kb-stat-arrow"></i>
    </a>
</div>

<div class="kb-admin-row">
    {{-- Membership Plan --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title"><i class="bi bi-star"></i> Membership Plan</h3>
        <form method="POST" action="{{ route('admin.subscriptions.updatePlan') }}">
            @csrf
            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-label">Plan Name</label>
                <input type="text" name="name" class="kb-form-input" value="{{ $plan->name ?? 'ProFormance Plus' }}" required maxlength="100">
            </div>
            <div class="kb-form-row">
                <div class="kb-form-group" style="margin-bottom: 12px;">
                    <label class="kb-form-label">Monthly Price (pence)</label>
                    <input type="number" name="monthly_price_penny" class="kb-form-input" value="{{ $plan->monthly_price_penny ?? 999 }}" required min="0">
                    <small class="kb-form-hint">999 = &pound;9.99</small>
                </div>
                <div class="kb-form-group" style="margin-bottom: 12px;">
                    <label class="kb-form-label">Order Discount %</label>
                    <input type="number" name="order_discount_percent" class="kb-form-input" value="{{ $plan->order_discount_percent ?? 10 }}" required min="0" max="100">
                </div>
            </div>
            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-check"><input type="checkbox" name="free_shipping" value="1" {{ ($plan->free_shipping ?? true) ? 'checked' : '' }}><span>Free shipping for subscribers</span></label>
            </div>
            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-check"><input type="checkbox" name="is_active" value="1" {{ ($plan->is_active ?? true) ? 'checked' : '' }}><span>Plan is active (visible to customers)</span></label>
            </div>
            <button type="submit" class="kb-admin-btn"><i class="bi bi-check"></i> Save Plan</button>
        </form>
    </div>

    {{-- Subscribe & Save --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title"><i class="bi bi-arrow-repeat"></i> Subscribe &amp; Save</h3>
        <form method="POST" action="{{ route('admin.subscriptions.updateSs') }}">
            @csrf
            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-label">Discount Percentage</label>
                <input type="number" name="discount_percent" class="kb-form-input" value="{{ $ssSettings->discount_percent ?? 5 }}" required min="0" max="50" style="max-width: 120px;">
                <small class="kb-form-hint">Discount applied to items with an active S&amp;S setup.</small>
            </div>
            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-check"><input type="checkbox" name="is_active" value="1" {{ ($ssSettings->is_active ?? true) ? 'checked' : '' }}><span>Subscribe &amp; Save is enabled</span></label>
            </div>
            <button type="submit" class="kb-admin-btn"><i class="bi bi-check"></i> Save Settings</button>
        </form>

        <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--kb-button-border);">
    </div>
</div>

<style>
    .kb-admin-row { display: flex; gap: 20px; flex-wrap: wrap; }
    .kb-admin-card-half { flex: 1; min-width: 300px; }
    .kb-form-row { display: flex; gap: 14px; }
    .kb-form-row .kb-form-group { flex: 1; }
    .kb-form-check { display: flex; align-items: center; gap: 8px; font-size: 14px; cursor: pointer; }
    .kb-form-check input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--kb-accent, #EB7347); }
    .kb-admin-stat-clickable { text-decoration: none; color: inherit; transition: box-shadow 0.15s; cursor: pointer; position: relative; }
    .kb-admin-stat-clickable:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .kb-stat-arrow { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--kb-secondary-font, #9ca3af); font-size: 14px; }
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
</style>
@endsection
