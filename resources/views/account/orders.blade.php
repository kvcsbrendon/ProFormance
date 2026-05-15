{{-- resources/views/account/orders.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">My Orders</h1>
    <p class="kb-account-subtitle">View and track your order history.</p>
</div>

<div class="kb-account-filters">
    <a href="{{ route('account.orders') }}"
       class="kb-filter-tag {{ !request('status') ? 'active' : '' }}">All</a>
    @foreach(['Pending', 'Paid', 'Processing', 'Shipped', 'Delivered', 'Fulfilled', 'Cancelled', 'Refunded'] as $status)
        <a href="{{ route('account.orders', ['status' => $status]) }}"
           class="kb-filter-tag {{ request('status') === $status ? 'active' : '' }}">
            {{ $status }}
        </a>
    @endforeach
</div>

@if($orders->isEmpty())
    <div class="kb-account-empty">
        <i class="bi bi-bag-x"></i>
        <p>No orders found.</p>
        <a href="{{ route('products.index') }}" class="kb-account-btn kb-account-btn-primary">Start Shopping</a>
    </div>
@else
    <div class="kb-account-table-wrapper">
        <table class="kb-account-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td>
                            <span class="kb-order-status kb-order-status-{{ strtolower($order->order_status) }}">
                                {{ $order->order_status }}
                            </span>
                            @if($order->is_subscribe_save)
                                <span class="kb-order-ss-tag"><i class="bi bi-arrow-repeat"></i> S&amp;S</span>
                            @endif
                        </td>
                        <td>{{ $order->items->count() }} item(s)</td>
                        <td>£{{ number_format($order->total_penny / 100, 2) }}</td>
                        <td class="kb-order-actions">
                            <a href="{{ route('account.orders.show', $order->order_id) }}" class="kb-account-btn kb-account-btn-small">
                                View
                            </a>
                            <form method="POST" action="{{ route('account.orders.reorder', $order->order_id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="kb-account-btn kb-account-btn-small kb-account-btn-outline" title="Add all items to basket">
                                    <i class="bi bi-arrow-repeat"></i> Buy Again
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="kb-admin-pagination">{{ $orders->links() }}</div>
@endif

<style>
    .kb-order-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
    .kb-order-status-processing { background: #e0e7ff; color: #3730a3; }
    .kb-order-status-shipped { background: #dbeafe; color: #1e40af; }
    .kb-order-status-delivered { background: #dcfce7; color: #166534; }
    .kb-order-ss-tag { display: inline-flex; align-items: center; gap: 3px; background: #f0fdf4; color: #16a34a; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px; margin-left: 4px; }
</style>
@endsection
