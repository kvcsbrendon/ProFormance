@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@endsection

@section('content')
<div class="kb-admin-page">
    <div class="kb-admin-container">
        <aside class="kb-admin-sidebar">
            <div class="kb-admin-sidebar-header">
                <div class="kb-admin-sidebar-badge"><i class="bi bi-speedometer2"></i></div>
                <div class="kb-admin-sidebar-user">
                    <span class="kb-admin-sidebar-name">Admin Panel</span>
                    <span class="kb-admin-sidebar-email">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                </div>
            </div>
            <nav class="kb-admin-nav">
                <span class="kb-admin-nav-label">Overview</span>
                <a href="{{ route('admin.dashboard') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-grid"></i><span>Dashboard</span></a>
                <a href="{{ route('admin.analytics.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.analytics*') ? 'active' : '' }}"><i class="bi bi-graph-up"></i><span>Analytics</span></a>
                <span class="kb-admin-nav-label">Operations</span>
                <a href="{{ route('admin.orders.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.orders*') ? 'active' : '' }}"><i class="bi bi-bag"></i><span>Orders</span></a>
                <a href="{{ route('admin.subscriptions.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.subscriptions*') ? 'active' : '' }}"><i class="bi bi-star"></i><span>Subscriptions</span></a>
                <a href="{{ route('admin.refunds.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.refunds*') ? 'active' : '' }}"><i class="bi bi-arrow-counterclockwise"></i><span>Refunds</span></a>
                <a href="{{ route('admin.customers.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.customers*') ? 'active' : '' }}"><i class="bi bi-people"></i><span>Customers</span></a>
                <a href="{{ route('admin.contacts.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.contacts*') ? 'active' : '' }}"><i class="bi bi-chat-dots"></i><span>Support Tickets</span></a>
                <a href="{{ route('admin.shipping.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.shipping*') ? 'active' : '' }}"><i class="bi bi-truck"></i><span>Shipping</span></a>
				<a href="{{ route('admin.info-pages.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.info-pages*') ? 'active' : '' }}"><i class="bi bi-file-text"></i><span>Info Pages</span></a>

                <span class="kb-admin-nav-label">Catalogue</span>
                <a href="{{ route('admin.products.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i><span>Products</span></a>
                <a href="{{ route('admin.brands.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.brands*') ? 'active' : '' }}"><i class="bi bi-bookmark"></i><span>Brands</span></a>
                <a href="{{ route('admin.variant-attributes.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.variant-attributes*') ? 'active' : '' }}"><i class="bi bi-sliders"></i><span>Variant Options</span></a>
                <a href="{{ route('admin.inventory.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.inventory*') ? 'active' : '' }}"><i class="bi bi-clipboard-data"></i><span>Inventory</span></a>
                <a href="{{ route('admin.reviews.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.reviews*') ? 'active' : '' }}"><i class="bi bi-star"></i><span>Reviews</span></a>

                <span class="kb-admin-nav-label">Marketing</span>
                <a href="{{ route('admin.discounts.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.discounts*') ? 'active' : '' }}"><i class="bi bi-tag"></i><span>Discounts</span></a>
                <a href="{{ route('admin.newsletters.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.newsletters*') ? 'active' : '' }}"><i class="bi bi-envelope-paper"></i><span>Newsletter</span></a>
		        <a href="{{ route('admin.messages.create') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.messages*') ? 'active' : '' }}"><i class="bi bi-megaphone"></i><span>Send Message</span></a>
                <a href="{{ route('admin.stock-notifications.index') }}" class="kb-admin-nav-item {{ request()->routeIs('admin.stock-notifications*') ? 'active' : '' }}">
                    <i class="bi bi-bell"></i><span>Stock Alerts</span>
                    @if($totalPendingStockAlerts ?? 0 > 0)
                        <span class="kb-admin-badge">{{ $totalPendingStockAlerts }}</span>
                    @endif
                </a>

                <div class="kb-admin-nav-divider"></div>
                <a href="{{ route('home') }}" class="kb-admin-nav-item"><i class="bi bi-shop"></i><span>View Store</span></a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" class="kb-admin-nav-item kb-admin-logout-btn"><i class="bi bi-box-arrow-left"></i><span>Sign Out</span></button>
                </form>
            </nav>
        </aside>
        <div class="kb-admin-content">
            @yield('admin-content')
        </div>
    </div>
</div>
@endsection
