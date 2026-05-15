{{-- resources/views/admin/refunds/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Refund Requests</h1>
    <p class="kb-admin-subtitle">Review and manage customer refund requests.</p>
</div>

{{-- Stats --}}
<div class="kb-admin-stat-row" style="margin-bottom: 20px;">
    <div class="kb-admin-stat-card">
        <div class="kb-admin-stat-icon" style="background: #fef3c7; color: #92400e;">
            <i class="bi bi-clock"></i>
        </div>
        <div>
            <span class="kb-admin-stat-value">{{ $pendingCount }}</span>
            <span class="kb-admin-stat-label">Pending</span>
        </div>
    </div>
    <div class="kb-admin-stat-card">
        <div class="kb-admin-stat-icon" style="background: #dcfce7; color: #166534;">
            <i class="bi bi-currency-pound"></i>
        </div>
        <div>
            <span class="kb-admin-stat-value">£{{ number_format($totalRefunded / 100, 2) }}</span>
            <span class="kb-admin-stat-label">Total Refunded</span>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search by order number…" value="{{ request('search') }}">
        <select name="status" class="kb-form-input">
            <option value="">All Statuses</option>
            <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
            <option value="Succeeded" {{ request('status') === 'Succeeded' ? 'selected' : '' }}>Approved</option>
            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('admin.refunds.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

{{-- Refunds Table --}}
<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($refunds as $refund)
                    <tr class="kb-admin-clickable-row" onclick="window.location='{{ route('admin.refunds.show', $refund->refund_id) }}'">
                        <td>#{{ $refund->refund_id }}</td>
                        <td class="kb-admin-mono">{{ $refund->order->order_number ?? '—' }}</td>
                        <td>{{ $refund->order->bill_recipient_name ?? '—' }}</td>
                        <td><strong>£{{ number_format($refund->amount_penny / 100, 2) }}</strong></td>
                        <td><span class="kb-admin-muted">{{ Str::limit($refund->reason, 40) }}</span></td>
                        <td>
                            @if($refund->refund_status === 'Pending')
                                <span class="kb-admin-pill kb-pill-amber">Pending</span>
                            @elseif($refund->refund_status === 'Succeeded')
                                <span class="kb-admin-pill kb-pill-green">Approved</span>
                            @else
                                <span class="kb-admin-pill kb-pill-red">Rejected</span>
                            @endif
                        </td>
                        <td>{{ $refund->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.refunds.show', $refund->refund_id) }}" class="kb-admin-btn-sm" onclick="event.stopPropagation();">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="kb-admin-empty-row">No refund requests found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($refunds->hasPages())
        <div class="kb-admin-pagination">
            {{ $refunds->withQueryString()->links() }}
        </div>
    @endif
</div>

<style>
    .kb-admin-stat-row { display: flex; gap: 16px; flex-wrap: wrap; }
    .kb-admin-stat-card { display: flex; align-items: center; gap: 12px; background: var(--kb-white, #fff); border: 1px solid var(--kb-button-border, #e5e7eb); border-radius: 10px; padding: 14px 20px; min-width: 180px; }
    .kb-admin-stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .kb-admin-stat-value { display: block; font-size: 20px; font-weight: 700; color: var(--kb-primary-font, #111827); }
    .kb-admin-stat-label { display: block; font-size: 12px; color: var(--kb-secondary-font, #6b7280); }
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-pill-red { background: #fee2e2; color: #991b1b; }
    .kb-admin-clickable-row { cursor: pointer; transition: background 0.15s; }
    .kb-admin-clickable-row:hover { background: var(--kb-grey-50, #f9fafb); }
</style>
@endsection
