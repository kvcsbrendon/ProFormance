{{-- resources/views/admin/subscriptions/subscribers.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.subscriptions.index') }}" class="kb-admin-back-link"><i class="bi bi-arrow-left"></i> Subscription Settings</a>
    <h1 class="kb-admin-title">Subscribers</h1>
    <p class="kb-admin-subtitle">All membership subscribers.</p>
</div>

<div class="kb-admin-stats-grid" style="margin-bottom: 16px;">
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-green"><i class="bi bi-check-circle"></i></div><div><span class="kb-admin-stat-value">{{ $stats['active'] }}</span><span class="kb-admin-stat-label">Active</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-amber"><i class="bi bi-x-circle"></i></div><div><span class="kb-admin-stat-value">{{ $stats['cancelled'] }}</span><span class="kb-admin-stat-label">Cancelled</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon" style="background:#f3f4f6;color:#6b7280;"><i class="bi bi-clock-history"></i></div><div><span class="kb-admin-stat-value">{{ $stats['expired'] }}</span><span class="kb-admin-stat-label">Expired</span></div></div>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search customer..." value="{{ request('search') }}">
        <select name="status" class="kb-form-input">
            <option value="">All</option>
            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
            <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
            <option value="Expired" {{ request('status') === 'Expired' ? 'selected' : '' }}>Expired</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('admin.subscriptions.subscribers') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th>Expires</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    <tr>
                        <td>
                            <a href="{{ route('admin.subscriptions.showSubscriber', $sub->subscription_id) }}" class="kb-admin-link">
                                {{ $sub->user->first_name ?? '' }} {{ $sub->user->last_name ?? '' }}
                            </a>
                        </td>
                        <td class="kb-admin-muted">{{ $sub->user->loginDetail->email_address ?? '—' }}</td>
                        <td>{{ $sub->plan->name ?? '—' }}</td>
                        <td>
                            @if($sub->status === 'Active' && $sub->expires_at->isFuture())
                                <span class="kb-admin-pill kb-pill-green">Active</span>
                            @elseif($sub->status === 'Cancelled')
                                <span class="kb-admin-pill kb-pill-amber">Cancelled</span>
                            @else
                                <span class="kb-admin-pill" style="background:#f3f4f6;color:#6b7280;">Expired</span>
                            @endif
                        </td>
                        <td>{{ $sub->started_at->format('d M Y') }}</td>
                        <td>{{ $sub->expires_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.subscriptions.showSubscriber', $sub->subscription_id) }}" class="kb-admin-btn-sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kb-admin-empty-row">No subscribers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subscriptions->hasPages())
        <div class="kb-admin-pagination">{{ $subscriptions->withQueryString()->links() }}</div>
    @endif
</div>

<style>
    .kb-admin-back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--kb-secondary-font, #6b7280); text-decoration: none; font-size: 13px; margin-bottom: 8px; }
    .kb-admin-back-link:hover { color: var(--kb-accent); }
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
</style>
@endsection
