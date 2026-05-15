@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <h1 class="kb-account-title">Welcome back, {{ $user->first_name }}!</h1>
    <p class="kb-account-subtitle">Here's a quick overview of your account.</p>
</div>

{{-- STATS CARDS --}}
<div class="kb-account-stats">
    <a href="{{ route('account.orders') }}" class="kb-account-stat-card">
        <div class="kb-stat-icon"><i class="bi bi-bag"></i></div>
        <div class="kb-stat-info">
            <span class="kb-stat-number">{{ $totalOrders }}</span>
            <span class="kb-stat-label">Orders</span>
        </div>
    </a>
    <a href="{{ route('account.addresses') }}" class="kb-account-stat-card">
        <div class="kb-stat-icon"><i class="bi bi-geo-alt"></i></div>
        <div class="kb-stat-info">
            <span class="kb-stat-number">{{ $addressCount }}</span>
            <span class="kb-stat-label">Saved Addresses</span>
        </div>
    </a>
    <div class="kb-account-stat-card">
        <div class="kb-stat-icon"><i class="bi bi-heart"></i></div>
        <div class="kb-stat-info">
            <span class="kb-stat-number">{{ $wishlistCount }}</span>
            <span class="kb-stat-label">Wishlist Items</span>
        </div>
    </div>
    <a href="{{ route('account.reviews') }}" class="kb-account-stat-card">
        <div class="kb-stat-icon"><i class="bi bi-star"></i></div>
        <div class="kb-stat-info">
            <span class="kb-stat-number">{{ $reviewCount }}</span>
            <span class="kb-stat-label">Reviews</span>
        </div>
    </a>
</div>

{{-- RECENT ORDERS --}}
<div class="kb-account-section">
    <div class="kb-account-section-header">
        <h2 class="kb-account-section-title">Recent Orders</h2>
        <a href="{{ route('account.orders') }}" class="kb-account-link">View all →</a>
    </div>

    @if($recentOrders->isEmpty())
        <div class="kb-account-empty">
            <i class="bi bi-bag-x"></i>
            <p>You haven't placed any orders yet.</p>
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
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td>{{ $order->order_number }}</td>
                            <td>{{ $order->created_at->format('d M Y') }}</td>
                            <td>
                                <span class="kb-order-status kb-order-status-{{ strtolower($order->order_status) }}">
                                    {{ $order->order_status }}
                                </span>
                            </td>
                            <td>£{{ number_format($order->total_penny / 100, 2) }}</td>
                            <td>
                                <a href="{{ route('account.orders.show', $order->order_id) }}" class="kb-account-link">
                                    View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
