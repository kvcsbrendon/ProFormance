@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Dashboard</h1>
    <p class="kb-admin-subtitle">Welcome back. Here's what's happening today.</p>
</div>

<div class="kb-admin-stats-grid">
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-blue"><i class="bi bi-bag-check"></i></div><div class="kb-admin-stat-info"><span class="kb-admin-stat-value">{{ $todayOrders }}</span><span class="kb-admin-stat-label">Orders Today</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-green"><i class="bi bi-currency-pound"></i></div><div class="kb-admin-stat-info"><span class="kb-admin-stat-value">&pound;{{ number_format($todayRevenue / 100, 2) }}</span><span class="kb-admin-stat-label">Revenue Today</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-purple"><i class="bi bi-bag"></i></div><div class="kb-admin-stat-info"><span class="kb-admin-stat-value">{{ $monthOrders }}</span><span class="kb-admin-stat-label">Orders This Month</span></div></div>
    <div class="kb-admin-stat-card"><div class="kb-admin-stat-icon kb-stat-amber"><i class="bi bi-graph-up-arrow"></i></div><div class="kb-admin-stat-info"><span class="kb-admin-stat-value">&pound;{{ number_format($monthRevenue / 100, 2) }}</span><span class="kb-admin-stat-label">Revenue This Month</span></div></div>
</div>

<div class="kb-admin-row">
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title">Quick Actions</h3>
        <div class="kb-admin-quick-actions">
            <a href="{{ route('admin.orders.index', ['status'=>'Pending']) }}" class="kb-admin-action-btn"><i class="bi bi-clock-history"></i><span>Pending Orders</span>@if($pendingOrders>0)<span class="kb-admin-badge">{{ $pendingOrders }}</span>@endif</a>
            <a href="{{ route('admin.contacts.index', ['status'=>'Pending']) }}" class="kb-admin-action-btn"><i class="bi bi-chat-dots"></i><span>Support Tickets</span>@if($pendingTickets>0)<span class="kb-admin-badge">{{ $pendingTickets }}</span>@endif</a>
            <a href="{{ route('admin.reviews.index', ['approved'=>'0']) }}" class="kb-admin-action-btn"><i class="bi bi-star-half"></i><span>Pending Reviews</span>@if($pendingReviews>0)<span class="kb-admin-badge">{{ $pendingReviews }}</span>@endif</a>
            <a href="{{ route('admin.products.create') }}" class="kb-admin-action-btn"><i class="bi bi-plus-circle"></i><span>Add Product</span></a>
            <a href="{{ route('admin.discounts.create') }}" class="kb-admin-action-btn"><i class="bi bi-tag"></i><span>Create Discount</span></a>
            <a href="{{ route('admin.inventory.index') }}" class="kb-admin-action-btn"><i class="bi bi-clipboard-data"></i><span>Manage Stock</span></a>
            <a href="{{ route('admin.refunds.index', ['status'=>'Pending']) }}" class="kb-admin-action-btn"><i class="bi bi-arrow-counterclockwise"></i><span>Refund Requests</span>@if($pendingRefunds>0)<span class="kb-admin-badge">{{ $pendingRefunds }}</span>@endif</a>
            <a href="{{ route('admin.stock-notifications.index', ['status'=>'pending']) }}" class="kb-admin-action-btn"><i class="bi bi-bell"></i><span>Stock Alerts</span>@if($pendingStockAlerts ?? 0 > 0)<span class="kb-admin-badge">{{ $pendingStockAlerts }}</span>@endif</a>
            <a href="{{ route('admin.reviews.index', ['reported' => '1']) }}" class="kb-admin-action-btn"><i class="bi bi-flag"></i><span>Reported Reviews</span>@if(isset($pendingReports) && $pendingReports > 0)<span class="kb-admin-badge">{{ $pendingReports }}</span>@endif
</a>
        </div>
    </div>
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title">Order Status</h3>
        <div class="kb-admin-status-bars">
            @foreach(['Pending','Paid','Processing','Shipped','Delivered','Fulfilled','Cancelled','Refunded'] as $st)
            <div class="kb-admin-status-row"><span class="kb-admin-status-label"><span class="kb-order-dot kb-order-dot-{{ strtolower($st) }}"></span>{{ $st }}</span><span class="kb-admin-status-count">{{ $ordersByStatus[$st] ?? 0 }}</span></div>
            @endforeach
            <div class="kb-admin-status-row kb-admin-status-total"><span>Total</span><span>{{ $totalOrders }}</span></div>
        </div>
    </div>
</div>

@if($outOfStock->count() > 0 || $lowStock->count() > 0)
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-exclamation-triangle"></i> Inventory Alerts</h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table"><thead><tr><th>Product</th><th>Variant</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($outOfStock as $inv)
            <tr><td>{{ $inv->variant->product->product_name ?? '—' }}</td><td>{{ $inv->variant->sku ?? '—' }}</td><td><strong>{{ $inv->available_stock }}</strong></td><td><span class="kb-admin-pill kb-pill-red">Out of Stock</span></td></tr>
            @endforeach
            @foreach($lowStock as $inv)
            <tr><td>{{ $inv->variant->product->product_name ?? '—' }}</td><td>{{ $inv->variant->sku ?? '—' }}</td><td><strong>{{ $inv->available_stock }}</strong></td><td><span class="kb-admin-pill kb-pill-amber">Low Stock</span></td></tr>
            @endforeach
        </tbody></table>
    </div>
</div>
@endif

<div class="kb-admin-card">
    <div class="kb-admin-card-header"><h3 class="kb-admin-card-title">Recent Orders</h3><a href="{{ route('admin.orders.index') }}" class="kb-admin-link">View all &rarr;</a></div>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table"><thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Items</th><th>Total</th><th></th></tr></thead>
        <tbody>
            @forelse($recentOrders as $order)
            <tr><td class="kb-admin-mono">{{ $order->order_number }}</td><td>{{ $order->created_at->format('d M Y H:i') }}</td><td><span class="kb-admin-pill kb-pill-{{ strtolower($order->order_status) }}">{{ $order->order_status }}</span></td><td>{{ $order->items->count() }}</td><td>&pound;{{ number_format($order->total_penny / 100, 2) }}</td><td><a href="{{ route('admin.orders.show', $order->order_id) }}" class="kb-admin-btn-sm">View</a></td></tr>
            @empty
            <tr><td colspan="6" class="kb-admin-empty-row">No orders yet.</td></tr>
            @endforelse
        </tbody></table>
    </div>
</div>

<div class="kb-admin-stats-grid kb-admin-stats-secondary">
    <div class="kb-admin-stat-card kb-admin-stat-compact"><i class="bi bi-people"></i><div><span class="kb-admin-stat-value">{{ $totalCustomers }}</span><span class="kb-admin-stat-label">Total Customers</span></div></div>
    <div class="kb-admin-stat-card kb-admin-stat-compact"><i class="bi bi-person-plus"></i><div><span class="kb-admin-stat-value">{{ $newCustomersMonth }}</span><span class="kb-admin-stat-label">New This Month</span></div></div>
    <div class="kb-admin-stat-card kb-admin-stat-compact"><i class="bi bi-box-seam"></i><div><span class="kb-admin-stat-value">{{ $activeProducts }}/{{ $totalProducts }}</span><span class="kb-admin-stat-label">Active Products</span></div></div>
    <div class="kb-admin-stat-card kb-admin-stat-compact"><i class="bi bi-currency-pound"></i><div><span class="kb-admin-stat-value">&pound;{{ number_format($totalRevenue / 100, 2) }}</span><span class="kb-admin-stat-label">All-Time Revenue</span></div></div>
</div>
@endsection