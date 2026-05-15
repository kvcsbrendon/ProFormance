{{-- resources/views/admin/subscriptions/show-subscriber.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.subscriptions.subscribers') }}" class="kb-admin-back-link"><i class="bi bi-arrow-left"></i> All Subscribers</a>
    <h1 class="kb-admin-title">
        {{ $customer->first_name }} {{ $customer->last_name }}
        @if($subscription->status === 'Active' && $subscription->expires_at->isFuture())
            <span class="kb-admin-pill kb-pill-green" style="font-size:14px;vertical-align:middle;">Active</span>
        @elseif($subscription->status === 'Cancelled')
            <span class="kb-admin-pill kb-pill-amber" style="font-size:14px;vertical-align:middle;">Cancelled</span>
        @else
            <span class="kb-admin-pill" style="font-size:14px;vertical-align:middle;background:#f3f4f6;color:#6b7280;">Expired</span>
        @endif
    </h1>
    <p class="kb-admin-subtitle">Subscription #{{ $subscription->subscription_id }}</p>
</div>

<div class="kb-admin-row">
    {{-- Subscription Details --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title">Subscription Details</h3>
        <div class="kb-detail-grid">
            <div class="kb-detail-item"><span class="kb-detail-label">Plan</span><span>{{ $subscription->plan->name ?? '—' }}</span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Monthly Price</span><span>&pound;{{ number_format(($subscription->plan->monthly_price_penny ?? 0) / 100, 2) }}</span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Status</span><span>{{ $subscription->status }}</span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Started</span><span>{{ $subscription->started_at->format('d M Y H:i') }}</span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Expires</span><span>{{ $subscription->expires_at->format('d M Y H:i') }}</span></div>
            @if($subscription->cancelled_at)
                <div class="kb-detail-item"><span class="kb-detail-label">Cancelled</span><span>{{ $subscription->cancelled_at->format('d M Y H:i') }}</span></div>
            @endif
            @if($subscription->admin_note)
                <div class="kb-detail-item" style="grid-column:1/-1;"><span class="kb-detail-label">Admin Note</span><span>{{ $subscription->admin_note }}</span></div>
            @endif
        </div>

        @if($subscription->status === 'Active' && $subscription->expires_at->isFuture())
            <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--kb-button-border);">
            <form method="POST" action="{{ route('admin.subscriptions.cancelSubscription', $subscription->subscription_id) }}"
                  onsubmit="return confirm('Cancel this subscription? The customer will be notified.')">
                @csrf
                <div class="kb-form-group" style="margin-bottom: 10px;">
                    <label class="kb-form-label">Note to customer (optional)</label>
                    <textarea name="admin_note" class="kb-form-input" rows="2" maxlength="500" placeholder="Reason for cancellation..."></textarea>
                </div>
                <button type="submit" class="kb-admin-btn" style="background: #dc2626; border-color: #dc2626;">
                    <i class="bi bi-x-circle"></i> Cancel Subscription
                </button>
            </form>
        @endif
    </div>

    {{-- Customer Overview --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title">Customer</h3>
        <div class="kb-detail-grid">
            <div class="kb-detail-item"><span class="kb-detail-label">Name</span><span><a href="{{ route('admin.customers.show', $customer->user_id) }}" class="kb-admin-link">{{ $customer->first_name }} {{ $customer->last_name }}</a></span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Email</span><span>{{ $customer->loginDetail->email_address ?? '—' }}</span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Total Orders</span><span>{{ $orderCount }}</span></div>
            <div class="kb-detail-item"><span class="kb-detail-label">Total Spent</span><span>&pound;{{ number_format($totalSpent / 100, 2) }}</span></div>
        </div>
    </div>
</div>

{{-- S&S Items for this customer --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-arrow-repeat"></i> Subscribe &amp; Save Items</h3>

    @if($ssItems->isEmpty())
        <p class="kb-admin-muted">This customer has no Subscribe &amp; Save items.</p>
    @else
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead>
                    <tr><th>Product</th><th>Qty</th><th>Frequency</th><th>Next Delivery</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($ssItems as $item)
                        <tr>
                            <td><strong>{{ $item->variant->product->product_name ?? '—' }}</strong></td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->frequencyLabel() }}</td>
                            <td>{{ $item->next_delivery_at ? $item->next_delivery_at->format('d M Y') : '—' }}</td>
                            <td>
                                @if(!$item->is_active)
                                    <span class="kb-admin-pill kb-pill-red">Cancelled</span>
                                @elseif($item->suspended_at)
                                    <span class="kb-admin-pill kb-pill-amber">Suspended</span>
                                @else
                                    <span class="kb-admin-pill kb-pill-green">Active</span>
                                @endif
                            </td>
                            <td style="white-space: nowrap;">
                                @if($item->is_active && !$item->suspended_at)
                                    <form method="POST" action="{{ route('admin.subscriptions.suspendSs', $item->ss_item_id) }}" style="display:inline;" onsubmit="return confirm('Suspend this S&S item?')">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="">
                                        <button type="submit" class="kb-admin-btn-sm" style="background:#f59e0b;border-color:#f59e0b;color:#fff;">Suspend</button>
                                    </form>
                                @elseif($item->is_active && $item->suspended_at)
                                    <form method="POST" action="{{ route('admin.subscriptions.resumeSs', $item->ss_item_id) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="kb-admin-btn-sm" style="background:#16a34a;border-color:#16a34a;color:#fff;">Resume</button>
                                    </form>
                                @endif
                                @if($item->is_active)
                                    <form method="POST" action="{{ route('admin.subscriptions.cancelSs', $item->ss_item_id) }}" style="display:inline;" onsubmit="return confirm('Cancel this S&S item permanently?')">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="">
                                        <button type="submit" class="kb-admin-btn-sm" style="background:#dc2626;border-color:#dc2626;color:#fff;">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Subscription History --}}
@if($history->count() > 1)
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title">Subscription History</h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>Plan</th><th>Status</th><th>Started</th><th>Expired</th></tr></thead>
            <tbody>
                @foreach($history as $h)
                    <tr>
                        <td>{{ $h->plan->name ?? '—' }}</td>
                        <td>
                            @if($h->status === 'Active' && $h->expires_at->isFuture())
                                <span class="kb-admin-pill kb-pill-green">Active</span>
                            @elseif($h->status === 'Cancelled')
                                <span class="kb-admin-pill kb-pill-amber">Cancelled</span>
                            @else
                                <span class="kb-admin-pill" style="background:#f3f4f6;color:#6b7280;">Expired</span>
                            @endif
                        </td>
                        <td>{{ $h->started_at->format('d M Y') }}</td>
                        <td>{{ $h->expires_at->format('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<style>
    .kb-admin-back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--kb-secondary-font, #6b7280); text-decoration: none; font-size: 13px; margin-bottom: 8px; }
    .kb-admin-back-link:hover { color: var(--kb-accent); }
    .kb-admin-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
    .kb-admin-card-half { flex: 1; min-width: 300px; }
    .kb-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
    .kb-detail-item { display: flex; flex-direction: column; gap: 2px; padding: 4px 0; }
    .kb-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--kb-secondary-font, #6b7280); font-weight: 600; }
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-pill-red { background: #fee2e2; color: #991b1b; }
</style>
@endsection
