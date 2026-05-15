@extends('account.layout')

@section('account-content')

<div class="kb-wishlist-page">
    <div class="kb-wishlist-container">

        <div class="kb-wishlist-breadcrumb">
            <a href="{{ route('wishlist.index') }}">My Wishlists</a>
            <i class="bi bi-chevron-right"></i>
            <span>{{ $wishlist->wishlist_name }}</span>
        </div>

        <div class="kb-wishlist-header">
            <div>
                <h1 class="kb-wishlist-title">{{ $wishlist->wishlist_name }}</h1>
                <p class="kb-wishlist-subtitle">{{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }}</p>
            </div>

            <div class="kb-wishlist-actions">
                <form method="POST" action="{{ route('wishlist.toggleShare', $wishlist->wishlist_id) }}">
                    @csrf
                    <button type="submit" class="kb-account-btn {{ $wishlist->is_public ? 'kb-account-btn-primary' : 'kb-account-btn-outline' }}">
                        <i class="bi {{ $wishlist->is_public ? 'bi-globe' : 'bi-lock' }}"></i>
                        {{ $wishlist->is_public ? 'Public' : 'Private' }}
                    </button>
                </form>

                @if($wishlist->is_public && $shareUrl)
                    <button type="button" class="kb-account-btn kb-account-btn-outline" onclick="copyShareLink()" id="copy-link-btn">
                        <i class="bi bi-link-45deg"></i> Copy Link
                    </button>
                    <input type="hidden" id="share-url" value="{{ $shareUrl }}">
                @endif

                <button type="button" class="kb-account-btn kb-account-btn-outline"
                        onclick="document.getElementById('wishlist-settings').style.display = document.getElementById('wishlist-settings').style.display === 'none' ? 'block' : 'none'">
                    <i class="bi bi-gear"></i> Settings
                </button>
            </div>
        </div>

        @if($wishlist->is_public && $shareUrl)
            <div class="kb-wishlist-share-info"><i class="bi bi-info-circle"></i> <span>Share link: <code>{{ $shareUrl }}</code></span></div>
        @endif

        {{-- SETTINGS PANEL --}}
        <div id="wishlist-settings" class="kb-wishlist-settings-card" style="display:none;">
            <form method="POST" action="{{ route('wishlist.update', $wishlist->wishlist_id) }}">
                @csrf
                @method('PUT')

                <h3>Wishlist Settings</h3>

                <div class="kb-form-row-two">
                    <div class="kb-form-group">
                        <label class="kb-form-label">Wishlist Name</label>
                        <input type="text" name="wishlist_name" class="kb-form-input" value="{{ $wishlist->wishlist_name }}" required>
                    </div>
                    <div class="kb-form-group">
                        <label class="kb-form-label">Share URL Slug</label>
                        <input type="text" name="slug" class="kb-form-input" value="{{ $wishlist->slug }}" placeholder="e.g. my-birthday-list">
                    </div>
                </div>

                <div class="kb-form-group">
                    <label class="kb-form-label">Delivery Address <span class="kb-form-optional">(for gift purchases)</span></label>
                    <select name="delivery_address_id" class="kb-form-input">
                        <option value="">— No delivery address (gifts disabled) —</option>
                        @foreach($addresses as $addr)
                            <option value="{{ $addr->address_id }}" {{ $wishlist->delivery_address_id == $addr->address_id ? 'selected' : '' }}>
                                {{ $addr->recipient_name }} — {{ $addr->house_number }} {{ $addr->address_line_one }}, {{ $addr->city }}, {{ $addr->postcode }}
                            </option>
                        @endforeach
                    </select>
                    <p class="kb-form-hint">If set, visitors can buy items as a gift. They'll be delivered here — the buyer won't see the address.</p>
                </div>

                <div class="kb-wishlist-settings-actions">
                    <button type="submit" class="kb-account-btn kb-account-btn-primary">Save Changes</button>
                </div>
            </form>

            <form method="POST" action="{{ route('wishlist.destroy', $wishlist->wishlist_id) }}" class="kb-wishlist-delete-form"
                  onsubmit="return confirm('Delete this wishlist and all its items?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="kb-account-btn kb-account-btn-danger"><i class="bi bi-trash"></i> Delete Wishlist</button>
            </form>
        </div>

        {{-- ITEMS --}}
        @if($items->isEmpty())
            <div class="kb-wishlist-empty">
                <i class="bi bi-heart"></i>
                <p>This wishlist is empty.</p>
                <a href="{{ route('products.index') }}" class="kb-account-btn kb-account-btn-primary">Browse Products</a>
            </div>
        @else
            <div class="kb-wishlist-grid">
                @foreach($items as $item)
                    @php
                        $variant = $item->variant;
                        $product = $variant?->product;
                        if (!$product) continue;

                        $image = $variant->images->sortBy('sort_order')->first();
                        $imagePath = $image ? 'images/' . $image->image_url : 'images/placeholders/product-placeholder.jpg';

                        $currency = strtoupper(session('currency', 'GBP'));
                        $priceRow = $variant->prices->where('currency_code', $currency)->first();
                        $symbol = \App\Models\Currency::where('currency_code', $currency)->first()?->symbol ?? '£';
                        $price = $priceRow ? $priceRow->price_penny / 100 : 0;
                        $wasPrice = ($priceRow && $priceRow->was_price_penny) ? $priceRow->was_price_penny / 100 : null;
                    @endphp

                    <div class="kb-wishlist-item-card">
                        <a href="{{ route('products.show', $product->product_id) }}" class="kb-wishlist-item-image">
                            <img src="{{ asset($imagePath) }}" alt="{{ $product->product_name }}"
                                 onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'">
                        </a>
                        <div class="kb-wishlist-item-info">
                            <h3 class="kb-wishlist-item-name"><a href="{{ route('products.show', $product->product_id) }}">{{ $product->product_name }}</a></h3>
                            @if($product->brand)
                                <p class="kb-wishlist-item-brand">{{ $product->brand->brand_name }}</p>
                            @endif
                            <div class="kb-wishlist-item-prices">
                                @if($wasPrice && $wasPrice > $price)
                                    <span class="kb-product-card-old">{{ $symbol }}{{ number_format($wasPrice, 2) }}</span>
                                @endif
                                <span class="kb-product-card-current">{{ $symbol }}{{ number_format($price, 2) }}</span>
                            </div>
                            <div class="kb-wishlist-item-actions">
                                <form method="POST" action="{{ route('cart.add') }}">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->variant_id }}">
                                    <button type="submit" class="kb-account-btn kb-account-btn-primary"><i class="bi bi-bag-plus"></i> Add to Basket</button>
                                </form>
                                <form method="POST" action="{{ route('wishlist.remove') }}">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->variant_id }}">
                                    <input type="hidden" name="wishlist_id" value="{{ $wishlist->wishlist_id }}">
                                    <button type="submit" class="kb-wishlist-remove-btn" title="Remove"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <span class="kb-wishlist-item-date">Added {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->format('d M Y') : '' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<script>
function copyShareLink() {
    const url = document.getElementById('share-url').value;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.getElementById('copy-link-btn');
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        setTimeout(() => { btn.innerHTML = '<i class="bi bi-link-45deg"></i> Copy Link'; }, 2000);
    });
}
</script>
@endsection
