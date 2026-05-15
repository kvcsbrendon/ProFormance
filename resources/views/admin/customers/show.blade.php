{{-- resources/views/admin/customers/show.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.customers.index') }}" class="kb-admin-back"><i class="bi bi-arrow-left"></i> Back to Customers</a>
    <h1 class="kb-admin-title">{{ $customer->first_name }} {{ $customer->last_name }}</h1>
    <p class="kb-admin-subtitle">{{ $customer->loginDetail->email_address ?? '—' }}</p>
</div>

<div class="kb-admin-stats-grid">
    <div class="kb-admin-stat-card kb-admin-stat-compact">
        <i class="bi bi-bag"></i>
        <div><span class="kb-admin-stat-value">{{ $orderCount }}</span><span class="kb-admin-stat-label">Total Orders</span></div>
    </div>
    <div class="kb-admin-stat-card kb-admin-stat-compact">
        <i class="bi bi-currency-pound"></i>
        <div><span class="kb-admin-stat-value">&pound;{{ number_format($totalSpent / 100, 2) }}</span><span class="kb-admin-stat-label">Total Spent</span></div>
    </div>
    <div class="kb-admin-stat-card kb-admin-stat-compact">
        <i class="bi bi-calendar"></i>
        <div><span class="kb-admin-stat-value">{{ $customer->created_at?->format('d M Y') ?? '—' }}</span><span class="kb-admin-stat-label">Joined</span></div>
    </div>
    <div class="kb-admin-stat-card kb-admin-stat-compact">
        <i class="bi bi-circle-fill" style="color:{{ $customer->is_active ? 'var(--kb-green-500)' : 'var(--kb-red-500)' }};font-size:0.75rem"></i>
        <div><span class="kb-admin-stat-value">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span><span class="kb-admin-stat-label">Status</span></div>
    </div>
</div>

<div class="kb-admin-row">
    <div class="kb-admin-card kb-admin-card-wide">
        <h3 class="kb-admin-card-title">Order History</h3>
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Items</th><th>Total</th><th></th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td class="kb-admin-mono">{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('d M Y') }}</td>
                        <td><span class="kb-admin-pill kb-pill-{{ strtolower($order->order_status) }}">{{ $order->order_status }}</span></td>
                        <td>{{ $order->items->count() }}</td>
                        <td>&pound;{{ number_format($order->total_penny / 100, 2) }}</td>
                        <td><a href="{{ route('admin.orders.show', $order->order_id) }}" class="kb-admin-btn-sm">View</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="kb-admin-empty-row">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="kb-admin-card-sidebar-stack">
        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Details</h3>
            <p><strong>Phone:</strong> +{{ $customer->country_phone_code }} {{ $customer->phone_number }}</p>
            @if($customer->company_name)
                <p><strong>Company:</strong> {{ $customer->company_name }}</p>
            @endif
            <p><strong>Role:</strong> {{ ucfirst($customer->user_role) }}</p>
            <p><strong>Email verified:</strong> {{ $customer->email_verified ? 'Yes' : 'No' }}</p>
        </div>

        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Addresses ({{ $addresses->count() }})</h3>
            @forelse($addresses as $addr)
                <div class="kb-admin-address-block">
                    <p>
                        <strong>{{ $addr->recipient_name }}</strong><br>
                        {{ $addr->house_number }} {{ $addr->address_line_one }}<br>
                        @if($addr->address_line_two){{ $addr->address_line_two }}<br>@endif
                        {{ $addr->city }}, {{ $addr->postcode }}<br>
                        {{ $addr->country_code }}
                    </p>
                    @if($addr->is_default_shipping_address)
                        <span class="kb-admin-pill kb-pill-green" style="font-size:10px;">Default Shipping</span>
                    @endif
                    @if($addr->is_default_billing_address)
                        <span class="kb-admin-pill kb-pill-paid" style="font-size:10px;">Default Billing</span>
                    @endif
                </div>
            @empty
                <p class="kb-admin-muted">No saved addresses.</p>
            @endforelse
        </div>

        <div class="kb-admin-card">
            <form method="POST" action="{{ route('admin.customers.toggle', $customer->user_id) }}">
                @csrf
                <button type="submit" class="{{ $customer->is_active ? 'kb-admin-btn kb-admin-btn-danger' : 'kb-admin-btn' }}" style="width:100%;">
                    <i class="bi {{ $customer->is_active ? 'bi-person-x' : 'bi-person-check' }}"></i>
                    {{ $customer->is_active ? 'Deactivate Account' : 'Activate Account' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
