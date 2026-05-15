{{-- resources/views/admin/orders/show.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.orders.index') }}" class="kb-admin-back"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    <div class="kb-admin-page-header">
        <div>
            <h1 class="kb-admin-title">Order {{ $order->order_number }}</h1>
            <p class="kb-admin-subtitle">Placed {{ $order->created_at->format('d F Y \a\t H:i') }}</p>
        </div>
        <span class="kb-admin-pill kb-admin-pill-lg kb-pill-{{ strtolower($order->order_status) }}">{{ $order->order_status }}</span>
    </div>
</div>

<div class="kb-admin-row">
    {{-- Items --}}
    <div class="kb-admin-card kb-admin-card-wide">
        <h3 class="kb-admin-card-title">Items</h3>
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead><tr><th>Item</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Tax</th><th>Line Total</th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                    @php
                        $net = $item->unit_price_penny / 100;
                        $lineNet = $net * $item->quantity;
                        $lineTax = $lineNet * ($item->tax_rate ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td class="kb-admin-mono">{{ $item->sku }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>&pound;{{ number_format($net, 2) }}</td>
                        <td>{{ number_format(($item->tax_rate ?? 0) * 100, 0) }}%</td>
                        <td>&pound;{{ number_format($lineNet + $lineTax, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="kb-admin-order-totals">
            <div class="kb-admin-total-row"><span>Subtotal</span><span>&pound;{{ number_format($order->subtotal_penny / 100, 2) }}</span></div>
            @if($order->discount_penny > 0)
            <div class="kb-admin-total-row kb-admin-discount-row"><span>Discount</span><span>-&pound;{{ number_format($order->discount_penny / 100, 2) }}</span></div>
            @endif
            <div class="kb-admin-total-row"><span>Shipping</span><span>&pound;{{ number_format($order->shipping_penny / 100, 2) }}</span></div>
            <div class="kb-admin-total-row"><span>VAT</span><span>&pound;{{ number_format($order->tax_penny / 100, 2) }}</span></div>
            <div class="kb-admin-total-row kb-admin-grand-total"><span>Total</span><span>&pound;{{ number_format($order->total_penny / 100, 2) }}</span></div>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="kb-admin-card-sidebar-stack">
        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Update Status</h3>
            <form method="POST" action="{{ route('admin.orders.updateStatus', $order->order_id) }}">
                @csrf
                <select name="order_status" class="kb-form-input" style="margin-bottom:0.75rem;">
                    @foreach(['Pending','Paid','Processing','Shipped','Delivered','Fulfilled','Cancelled','Refunded'] as $st)
                        <option value="{{ $st }}" {{ $order->order_status === $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
                <button type="submit" class="kb-admin-btn" style="width:100%;">Update Status</button>
            </form>
        </div>

        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Customer</h3>
            @if($user)
                <p><strong>{{ $user->first_name }} {{ $user->last_name }}</strong></p>
                <p class="kb-admin-muted">{{ $user->email }}</p>
                <a href="{{ route('admin.customers.show', $user->user_id) }}" class="kb-admin-link">View profile &rarr;</a>
            @else
                <p class="kb-admin-muted">Guest checkout</p>
            @endif
        </div>

        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Shipping Address</h3>
            <p class="kb-admin-address-text">
                {{ $order->ship_recipient_name }}<br>
                {{ $order->ship_house_number }} {{ $order->ship_address_line_one }}<br>
                @if($order->ship_address_line_two){{ $order->ship_address_line_two }}<br>@endif
                {{ $order->ship_city }}, {{ $order->ship_postcode }}<br>
                {{ $order->ship_country_code }}
            </p>
        </div>

        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Billing Address</h3>
            <p class="kb-admin-address-text">
                {{ $order->bill_recipient_name }}<br>
                {{ $order->bill_house_number }} {{ $order->bill_address_line_one }}<br>
                @if($order->bill_address_line_two){{ $order->bill_address_line_two }}<br>@endif
                {{ $order->bill_city }}, {{ $order->bill_postcode }}<br>
                {{ $order->bill_country_code }}
            </p>
        </div>

        <div class="kb-admin-card">
            <a href="{{ route('account.orders.invoice', $order->order_id) }}" class="kb-admin-btn-outline" style="width:100%;text-align:center;justify-content:center;">
                <i class="bi bi-file-earmark-pdf"></i> Download Invoice
            </a>
        </div>
    </div>
</div>
@endsection
