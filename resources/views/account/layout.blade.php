@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/account.css') }}">
<link rel="stylesheet" href="{{ asset('css/2fa.css') }}">
@endsection

@section('content')
<div class="kb-account-page">
    <div class="kb-account-container">

        {{-- SIDEBAR --}}
        <aside class="kb-account-sidebar">
            <div class="kb-account-sidebar-header">
                <i class="bi bi-person-circle kb-account-sidebar-avatar"></i>
                <div class="kb-account-sidebar-user">
                    <span class="kb-account-sidebar-name">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                    <span class="kb-account-sidebar-email">{{ auth()->user()->email }}</span>
                </div>
            </div>

            <nav class="kb-account-nav">
                <a href="{{ route('account.dashboard') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('account.profile') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.profile') ? 'active' : '' }}">
                    <i class="bi bi-person"></i>
                    <span>My Profile</span>
                </a>
                <a href="{{ route('account.messages') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.messages*') ? 'active' : '' }}">
                    <i class="bi bi-envelope"></i>
                    <span>Messages</span>
                    @php
                        $unreadCount = Auth::check()
                            ? \App\Models\UserMessage::where('user_id', Auth::user()->user_id)->where('is_read', false)->count()
                            : 0;
                    @endphp
                    @if($unreadCount > 0)
                        <span class="kb-messages-badge">{{ $unreadCount }}</span>
                    @endif
                </a>
                <a href="{{ route('account.orders') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.orders*') ? 'active' : '' }}">
                    <i class="bi bi-bag"></i>
                    <span>My Orders</span>
                </a>
                <a href="{{ route('account.2fa') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.2fa*') ? 'active' : '' }}">
                    <i class="bi bi-shield-lock"></i>
                    <span>Two-Factor Auth</span>
                </a>
                <a href="{{ route('wishlist.index') }}"
                   class="kb-account-nav-item {{ request()->routeIs('wishlist.index') ? 'active' : '' }}">
                    <i class="bi bi-heart"></i>
                    <span>Wishlist</span>
                </a>
                <a href="{{ route('account.addresses') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.addresses*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt"></i>
                    <span>Addresses</span>
                </a>
                <a href="{{ route('account.cards') }}" 
                    class="kb-account-nav-item {{ request()->routeIs('account.cards*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card"></i>
                    <span>Saved Cards</span>
                </a>
                <a href="{{ route('account.subscription') }}"
                    class="kb-account-nav-item {{ request()->routeIs('account.subscription*') ? 'active' : '' }}">
                        <i class="bi bi-star"></i>
                        <span>Subscription</span>
                </a>
                <a href="{{ route('account.reviews') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.reviews*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots"></i>
                    <span>My Reviews</span>
                </a>
                <a href="{{ route('account.security') }}"
                   class="kb-account-nav-item {{ request()->routeIs('account.security*') ? 'active' : '' }}">
                    <i class="bi bi-lock"></i>
                    <span>Security</span>
                </a>

                <div class="kb-account-nav-divider"></div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="kb-account-nav-item kb-account-logout-btn">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </nav>
        </aside>

        {{-- MAIN CONTENT --}}
        <div class="kb-account-content">

            @yield('account-content')
        </div>
    </div>
</div>
@endsection
