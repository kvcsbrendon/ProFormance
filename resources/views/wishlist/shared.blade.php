@extends('account.layout')

@section('content')

<div class="kb-wishlist-page">
    <div class="kb-wishlist-container">

        <div class="kb-wishlist-header">
            <div>
                <h1 class="kb-wishlist-title">{{ $owner->first_name }}'s {{ $wishlist->wishlist_name }}</h1>
                <p class="kb-wishlist-subtitle">{{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }}</p>
            </div>
        </div>

        @if($canGift)
            <div class="kb-wishlist-gift-banner">
                <i class="bi bi-gift-fill"></i>
                <div>
                    <strong>Gift purchases enabled!</strong>
                    <p>Buy an item as a gift and it will be delivered directly to {{ $owner->first_name }} — you won't see their address.</p>
                </div>
            </div>
        @endif

        @if($items->isEmpty())
            <div class="kb-wishlist-empty">
                <i class="bi bi-heart"></i>
                <p>This wishlist is empty.</p>
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
                            <h3 class="kb-wishlist-item-name">
                                <a href="{{ route('products.show', $product->product_id) }}">{{ $product->product_name }}</a>
                            </h3>
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
                                @if($canGift)
                                    <form method="POST" action="{{ route('wishlist.giftAdd') }}">
                                        @csrf
                                        <input type="hidden" name="variant_id" value="{{ $variant->variant_id }}">
                                        <input type="hidden" name="wishlist_id" value="{{ $wishlist->wishlist_id }}">
                                        <button type="submit" class="kb-account-btn kb-account-btn-gift">
                                            <i class="bi bi-gift"></i> Buy as Gift for {{ $owner->first_name }}
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('cart.add') }}">
                                    @csrf
                                    <input type="hidden" name="variant_id" value="{{ $variant->variant_id }}">
                                    <button type="submit" class="kb-account-btn kb-account-btn-outline">
                                        <i class="bi bi-bag-plus"></i> Buy for Yourself
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
