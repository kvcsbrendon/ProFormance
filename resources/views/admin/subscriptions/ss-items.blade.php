{{-- resources/views/admin/subscriptions/ss-items.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.subscriptions.index') }}" class="kb-admin-back-link"><i class="bi bi-arrow-left"></i> Subscription Settings</a>
    <h1 class="kb-admin-title">Subscribe &amp; Save Items</h1>
    <p class="kb-admin-subtitle">All customer S&amp;S subscriptions across the store.</p>
</div>

<div class="kb-admin-stats-grid" style="margin-bottom: 16px;">
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-green"><i class="bi bi-check-circle"></i></div><div><span class="kb-admin-stat-value">{{ $stats['active'] }}</span><span class="kb-admin-stat-label">Active</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-amber"><i class="bi bi-pause-circle"></i></div><div><span class="kb-admin-stat-value">{{ $stats['suspended'] }}</span><span class="kb-admin-stat-label">Suspended</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon" style="background:#fee2e2;color:#991b1b;"><i class="bi bi-x-circle"></i></div><div><span class="kb-admin-stat-value">{{ $stats['cancelled'] }}</span><span class="kb-admin-stat-label">Cancelled</span></div></div>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search customer or product..." value="{{ request('search') }}">
        <select name="status" class="kb-form-input">
            <option value="">All</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('admin.subscriptions.ssItems') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Frequency</th>
                    <th>Next Delivery</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <a href="{{ route('admin.customers.show', $item->user->user_id ?? 0) }}" class="kb-admin-link">
                                {{ $item->user->first_name ?? '' }} {{ $item->user->last_name ?? '' }}
                            </a>
                            <br><small class="kb-admin-muted">{{ $item->user->loginDetail->email_address ?? '' }}</small>
                        </td>
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
                                <form method="POST" action="{{ route('admin.subscriptions.suspendSs', $item->ss_item_id) }}" style="display:inline;" onsubmit="return confirm('Suspend?')">
                                    @csrf
                                    <input type="hidden" name="admin_note" value="">
                                    <button type="submit" class="kb-admin-btn-sm" style="background:#f59e0b;border-color:#f59e0b;color:#fff;" title="Suspend">
                                        <i class="bi bi-pause-fill"></i>
                                    </button>
                                </form>
                            @elseif($item->is_active && $item->suspended_at)
                                <form method="POST" action="{{ route('admin.subscriptions.resumeSs', $item->ss_item_id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="kb-admin-btn-sm" style="background:#16a34a;border-color:#16a34a;color:#fff;" title="Resume">
                                        <i class="bi bi-play-fill"></i>
                                    </button>
                                </form>
                            @endif
                            @if($item->is_active)
                                <form method="POST" action="{{ route('admin.subscriptions.cancelSs', $item->ss_item_id) }}" style="display:inline;" onsubmit="return confirm('Cancel this S&S permanently?')">
                                    @csrf
                                    <input type="hidden" name="admin_note" value="">
                                    <button type="submit" class="kb-admin-btn-sm" style="background:#dc2626;border-color:#dc2626;color:#fff;" title="Cancel">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kb-admin-empty-row">No Subscribe &amp; Save items found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($items->hasPages())
        <div class="kb-admin-pagination">{{ $items->withQueryString()->links() }}</div>
    @endif
</div>

<style>
    .kb-admin-back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--kb-secondary-font, #6b7280); text-decoration: none; font-size: 13px; margin-bottom: 8px; }
    .kb-admin-back-link:hover { color: var(--kb-accent); }
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-pill-red { background: #fee2e2; color: #991b1b; }
</style>
@endsection
