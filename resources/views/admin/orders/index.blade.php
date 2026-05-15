{{-- resources/views/admin/orders/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Orders</h1>
    <p class="kb-admin-subtitle">Manage and fulfil customer orders.</p>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search order # or name…" value="{{ request('search') }}">
        <select name="status" class="kb-form-input">
            <option value="">All Statuses</option>
            @foreach(['Pending','Paid','Fulfilled','Cancelled','Refunded'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <input type="date" name="from" class="kb-form-input" value="{{ request('from') }}">
        <input type="date" name="to" class="kb-form-input" value="{{ request('to') }}">
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','status','from','to']))
            <a href="{{ route('admin.orders.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td class="kb-admin-mono">{{ $order->order_number }}</td>
                    <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $order->ship_recipient_name ?? '—' }}</td>
                    <td>
                        <span class="kb-admin-pill kb-pill-{{ strtolower($order->order_status) }}">
                            {{ $order->order_status }}
                        </span>
                    </td>
                    <td>{{ $order->items->count() }}</td>
                    <td>&pound;{{ number_format($order->total_penny / 100, 2) }}</td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order->order_id) }}" class="kb-admin-btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="kb-admin-empty-row">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $orders->links() }}</div>
</div>
@endsection
