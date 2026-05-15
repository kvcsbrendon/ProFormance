@extends('account.layout')
@section('account-content')
<div class="kb-wishlist-page">
    <div class="kb-wishlist-container">

        <div class="kb-wishlist-header">
            <div>
                <h1 class="kb-wishlist-title">My Wishlists</h1>
                <p class="kb-wishlist-subtitle">{{ $wishlists->count() }} {{ $wishlists->count() === 1 ? 'wishlist' : 'wishlists' }}</p>
            </div>

            <button type="button" class="kb-account-btn kb-account-btn-primary" onclick="document.getElementById('create-wishlist-form').style.display = document.getElementById('create-wishlist-form').style.display === 'none' ? 'block' : 'none'">
                <i class="bi bi-plus-lg"></i> New Wishlist
            </button>
        </div>

        {{-- CREATE WISHLIST FORM --}}
        <div id="create-wishlist-form" class="kb-wishlist-create-card" style="display:none;">
            <form method="POST" action="{{ route('wishlist.store') }}">
                @csrf
                <h3>Create a New Wishlist</h3>
                <div class="kb-form-row-inline">
                    <div class="kb-form-group" style="flex:2;">
                        <label class="kb-form-label">Name</label>
                        <input type="text" name="wishlist_name" class="kb-form-input" placeholder="e.g. Birthday Wishlist" required>
                    </div>
                    <div class="kb-form-group" style="flex:2;">
                        <label class="kb-form-label">Custom URL slug <span class="kb-form-optional">(optional)</span></label>
                        <input type="text" name="slug" class="kb-form-input" placeholder="e.g. my-birthday-list">
                    </div>
                    <div class="kb-form-group" style="flex:0; align-self:flex-end;">
                        <button type="submit" class="kb-account-btn kb-account-btn-primary">Create</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- WISHLISTS GRID --}}
        @if($wishlists->isEmpty())
            <div class="kb-wishlist-empty">
                <i class="bi bi-heart"></i>
                <p>You don't have any wishlists yet.</p>
                <p>Create one to start saving products you love!</p>
            </div>
        @else
            <div class="kb-wishlists-grid">
                @foreach($wishlists as $wl)
                    <a href="{{ route('wishlist.show', $wl->wishlist_id) }}" class="kb-wishlists-card">
                        <div class="kb-wishlists-card-top">
                            <h3 class="kb-wishlists-card-name">{{ $wl->wishlist_name }}</h3>
                            <div class="kb-wishlists-card-badges">
                                @if($wl->is_public)
                                    <span class="kb-badge kb-badge-public"><i class="bi bi-globe"></i> Public</span>
                                @else
                                    <span class="kb-badge kb-badge-private"><i class="bi bi-lock"></i> Private</span>
                                @endif
                                @if($wl->delivery_address_id)
                                    <span class="kb-badge kb-badge-gift"><i class="bi bi-gift"></i> Gift</span>
                                @endif
                            </div>
                        </div>
                        <div class="kb-wishlists-card-bottom">
                            <span class="kb-wishlists-card-count">
                                <i class="bi bi-heart-fill"></i> {{ $wl->items_count }} {{ $wl->items_count === 1 ? 'item' : 'items' }}
                            </span>
                            <span class="kb-wishlists-card-date">
                                Created {{ $wl->created_at->format('d M Y') }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
