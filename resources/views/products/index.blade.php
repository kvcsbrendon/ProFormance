@extends('layouts.app')

@section('content')

<div class="kb-products-page">
    <div class="kb-products-container">
        <div class="kb-products-header">
            <h1 class="kb-products-title">{{ $pageTitle ?? 'All Products' }}</h1>

            <div class="kb-products-results">
                <p class="kb-products-count">
                    Showing {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
                </p>

                <div class="kb-products-sort">
                    <form method="GET" action="{{ url()->current() }}" class="kb-sort-form">
                        @if(request()->filled('search'))
                            <input type="hidden" name="search" value="{{ request('search') }}">
                        @endif
                        @if(request()->filled('deals'))
                            <input type="hidden" name="deals" value="1">
                        @endif
                        @foreach((array) request('category', []) as $cat)
                            <input type="hidden" name="category[]" value="{{ $cat }}">
                        @endforeach
                        @foreach((array) request('brand', []) as $b)
                            <input type="hidden" name="brand[]" value="{{ $b }}">
                        @endforeach
                        @if(request()->filled('min_price'))
                            <input type="hidden" name="min_price" value="{{ request('min_price') }}">
                        @endif
                        @if(request()->filled('max_price'))
                            <input type="hidden" name="max_price" value="{{ request('max_price') }}">
                        @endif
                        @if(request()->filled('per_page'))
                            <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                        @endif

                        <label for="sort">Sort by:</label>
                        <select name="sort" id="sort" onchange="this.form.submit()">
                            <option value="newest" {{ request()->get('sort', 'newest') == 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="price_low" {{ request()->get('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request()->get('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name_asc" {{ request()->get('sort') == 'name_asc' ? 'selected' : '' }}>Name: A to Z</option>
                            <option value="name_desc" {{ request()->get('sort') == 'name_desc' ? 'selected' : '' }}>Name: Z to A</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <div class="kb-products-layout">

            {{-- Filter toggle (mobile only) --}}
            <button type="button" class="kb-filter-toggle-btn" id="filter-toggle-btn"
                    onclick="toggleFilters()">
                <span><i class="bi bi-funnel"></i> Filters & Categories</span>
                <i class="bi bi-chevron-down" id="filter-chevron"></i>
            </button>

            <aside class="kb-products-sidebar" id="products-sidebar">
                <form method="GET" action="{{ url()->current() }}" class="kb-filter-form">
                    @if(request()->filled('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif
                    @if(request()->filled('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif
                    @if(request()->filled('per_page'))
                        <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                    @endif

                    @foreach($mainCategories as $parent)
                        <div class="kb-filter-parent">
                            @php
                                $parentUrl = request()->fullUrlWithQuery([
                                    'category' => [$parent->category_id],
                                    'page' => null,
                                ]);
                            @endphp

                            <a href="{{ $parentUrl }}" class="kb-filter-parent-title kb-filter-parent-link">
                                <span>{{ $parent->category_name }}</span>
                                <span class="kb-filter-count">({{ $parent->products_total ?? 0 }})</span>
                            </a>

                            @if($parent->children->count())
                                <div class="kb-filter-children">
                                    @foreach($parent->children as $child)
                                        <label class="kb-filter-checkbox kb-filter-checkbox--child">
                                            <input type="checkbox" name="category[]" value="{{ $child->category_id }}"
                                                {{ in_array($child->category_id, (array) request('category', [])) ? 'checked' : '' }}
                                                onchange="this.form.submit()">
                                            <span>{{ $child->category_name }}</span>
                                            <span class="kb-filter-count">({{ $child->products_count ?? 0 }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="kb-filter-section">
                        <h3 class="kb-filter-title">Price Range</h3>
                        <div class="kb-filter-price">
                            <div class="kb-price-inputs">
                                <input type="number" name="min_price" placeholder="Min"
                                    value="{{ request()->min_price }}" min="0" step="0.01">
                                <span>to</span>
                                <input type="number" name="max_price" placeholder="Max"
                                    value="{{ request()->max_price }}" min="0" step="0.01">
                            </div>
                            @if($priceRange)
                                <div class="kb-price-range">
                                    <span>£{{ number_format($priceRange->min_price, 2) }}</span>
                                    <span>£{{ number_format($priceRange->max_price, 2) }}</span>
                                </div>
                            @endif
                            <button type="submit" class="kb-filter-btn">Apply Price Filter</button>
                        </div>
                    </div>

                    <div class="kb-filter-actions">
                        <a href="{{ route('products.index') }}" class="kb-clear-filters">Clear All Filters</a>
                    </div>
                </form>
            </aside>

            <main class="kb-products-main">
                @if ($products->count() > 0)
                    <div class="kb-products-grid">
                        @foreach($products as $product)
                            @php
                                $bestPrice = $product->bestPriceForCurrency();
                                $primaryImage = $product->primaryImage()->first();
                                $imagePath = $primaryImage
                                    ? 'images/' . $primaryImage->image_url
                                    : 'images/placeholders/product-placeholder.jpg';
                                $variant = $product->variants()->first() ?? null;
                            @endphp

                            <article class="kb-product-card">
                                <div class="kb-product-card-media">
                                    <div class="kb-product-badge-container">
                                        @if($product->created_at && $product->created_at->gt(now()->subDays(30)))
                                            <span class="kb-product-badge kb-product-badge--new">New</span>
                                        @endif
                                        @if($bestPrice && $bestPrice->was && $bestPrice->was > $bestPrice->price)
                                            <span class="kb-product-badge kb-product-badge--sale">Sale</span>
                                        @endif
                                    </div>

                                    @if($variant)
                                        <button type="button"
                                                class="kb-wishlist-heart {{ in_array($variant->variant_id, $wishlistVariantIds ?? []) ? 'kb-wishlisted' : '' }}"
                                                data-variant-id="{{ $variant->variant_id }}"
                                                onclick="toggleWishlistCard(this)"
                                                aria-label="Add to wishlist">
                                            <i class="bi {{ in_array($variant->variant_id, $wishlistVariantIds ?? []) ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                        </button>
                                    @endif

                                    <a href="{{ route('products.show', $product->product_id) }}">
                                        <img src="{{ asset($imagePath) }}"
                                             alt="{{ $product->product_name }}"
                                             loading="lazy"
                                             onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'">
                                    </a>
                                </div>

                                <div class="kb-product-card-body">
                                    <h3 class="kb-product-card-title">
                                        <a href="{{ route('products.show', $product->product_id) }}">
                                            {{ $product->product_name }}
                                        </a>
                                    </h3>

                                    @if($product->brand)
                                        <p class="kb-product-card-brand">{{ $product->brand->brand_name }}</p>
                                    @endif

                                    @php
                                        $vatRate = ($vatRate ?? 0);
                                        $symbol  = $bestPrice->symbol ?? '£';
                                        $current = $bestPrice && $bestPrice->price
                                            ? $bestPrice->price * (1 + $vatRate) : 0;
                                        $old = ($bestPrice && $bestPrice->was && $bestPrice->was > $bestPrice->price)
                                            ? $bestPrice->was * (1 + $vatRate) : null;
                                    @endphp

                                    <div class="kb-product-card-prices">
                                        @if($old)
                                            <span class="kb-product-card-old">{{ $symbol }}{{ number_format($old, 2) }}</span>
                                        @endif
                                        <span class="kb-product-card-current">{{ $symbol }}{{ number_format($current, 2) }}</span>
                                        <div class="kb-product-card-vat-note">
                                            <small class="text-muted">{{ $vatLabel ?? '' }}</small>
                                        </div>
                                    </div>

                                    <div class="kb-new-card-actions">
                                        @if($variant)
                                            <div class="cart-control"
                                                data-variant="{{ $variant->variant_id }}"
                                                data-cart-url="{{ route('cart.add') }}">
                                                <button type="button" class="add-btn kb-product-page-add-btn">Add to Basket</button>
                                                <div class="qty-controls" hidden>
                                                    <button type="button" class="minus-btn">?</button>
                                                    <span class="qty">1</span>
                                                    <button type="button" class="plus-btn">+</button>
                                                </div>
                                            </div>
                                        @endif
                                        <a href="{{ route('products.show', $product->product_id) }}" class="kb-new-icon-btn" aria-label="View product">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="kb-no-products">
                        <div class="kb-no-products-icon"><i class="bi bi-search"></i></div>
                        <h3>No products found</h3>
                        <p>
                            @if(request()->has('search'))
                                We couldn't find any products matching "{{ request()->search }}".
                            @else
                                No products match your filters. Try adjusting your search criteria.
                            @endif
                        </p>
                        <a href="{{ route('products.index') }}" class="kb-back-to-products">Back to All Products</a>
                    </div>
                @endif

                @if ($products->hasPages())
                    <div class="kb-admin-pagination">
                        {{ $products->appends(request()->query())->links('vendor.pagination.kb-products') }}
                    </div>
                @endif
            </main>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function toggleFilters() {
    var sidebar = document.getElementById('products-sidebar');
    var btn = document.getElementById('filter-toggle-btn');
    var open = sidebar.style.display === 'block';

    sidebar.style.display = open ? 'none' : 'block';
    btn.classList.toggle('active', !open);
}

// Reset on desktop resize
window.addEventListener('resize', function() {
    var sidebar = document.getElementById('products-sidebar');
    if (window.innerWidth > 768) {
        sidebar.style.display = '';
    }
});
</script>
@endsection