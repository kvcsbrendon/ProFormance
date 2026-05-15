@extends('layouts.app')
@section('content')
       {{-- ═══ HERO SLIDER ═══ --}}
    <section class="kb-hero">
        <div class="kb-hero-inner">
            <div class="kb-hero-slider" id="hero-slider">

                {{-- SLIDE 1: Deals --}}
                <div class="kb-hero-slide is-active" data-slide="0">
                    <div class="kb-hero-slide-content kb-hero-deals">
                        <div class="kb-hero-deals-text">
                            <span class="kb-hero-tag">Limited Time</span>
                            <h2 class="kb-hero-title">Today's Top Deals</h2>
                            <p class="kb-hero-subtitle">Grab these while they last — updated daily.</p>
                            <a href="{{ route('products.index', ['deals' => 1]) }}" class="kb-hero-cta">
                                Shop All Deals <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="kb-hero-deals-grid">
                            @forelse ($heroProducts->take(4) as $product)
                                @php
                                    $bestPrice = $product->bestPriceForCurrency();
                                    $primaryImage = $product->primaryImage()->first();
                                    $imagePath = $primaryImage
                                        ? 'images/' . $primaryImage->image_url
                                        : 'images/placeholders/product-placeholder.jpg';
                                    $rate   = $vatRate ?? 0;
                                    $symbol = $bestPrice->symbol ?? '£';
                                    $current = ($bestPrice && isset($bestPrice->price))
                                        ? $bestPrice->price * (1 + $rate) : 0;
                                    $old = ($bestPrice && isset($bestPrice->was) && $bestPrice->was > $bestPrice->price)
                                        ? $bestPrice->was * (1 + $rate) : null;
                                @endphp
                                <a href="{{ route('products.show', $product->product_id) }}" class="kb-hero-deal-card">
                                    <div class="kb-hero-deal-img">
                                        <img src="{{ asset($imagePath) }}" alt="{{ $product->product_name }}" loading="lazy">
                                        @if($old)
                                            <span class="kb-hero-deal-badge">Sale</span>
                                        @endif
                                    </div>
                                    <div class="kb-hero-deal-info">
                                        <h3>{{ Str::limit($product->product_name, 40) }}</h3>
                                        <div class="kb-hero-deal-prices">
                                            @if($old)
                                                <span class="kb-hero-deal-was">{{ $symbol }}{{ number_format($old, 2) }}</span>
                                            @endif
                                            <span class="kb-hero-deal-now">{{ $symbol }}{{ number_format($current, 2) }}</span>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <p class="kb-hero-empty">No deals right now — check back soon.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- SLIDE 2: Promises --}}
                <div class="kb-hero-slide" data-slide="1">
                    <div class="kb-hero-slide-content kb-hero-promises-slide">
                        <h2 class="kb-hero-title">Why Choose ProFormance</h2>
                        <p class="kb-hero-subtitle">We're built different — here's why thousands trust us.</p>
                        <div class="kb-hero-promises">
                            <div class="kb-hero-promise">
                                <div class="kb-hero-promise-icon"><i class="bi bi-truck"></i></div>
                                <h3>Free Shipping</h3>
                                <p>On UK orders over £50. Fast, tracked delivery every time.</p>
                            </div>
                            <div class="kb-hero-promise">
                                <div class="kb-hero-promise-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
                                <h3>30-Day Returns</h3>
                                <p>Changed your mind? No hassle, no questions asked.</p>
                            </div>
                            <div class="kb-hero-promise">
                                <div class="kb-hero-promise-icon"><i class="bi bi-shield-check"></i></div>
                                <h3>Secure Checkout</h3>
                                <p>Your data is encrypted and your payment is protected.</p>
                            </div>
                            <div class="kb-hero-promise">
                                <div class="kb-hero-promise-icon"><i class="bi bi-chat-right-text"></i></div>
                                <h3>Real Support</h3>
                                <p>Actual humans, 7 days a week. Not a bot wall.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SLIDE 3: Coming Soon --}}
                <div class="kb-hero-slide" data-slide="2">
                    <div class="kb-hero-slide-content kb-hero-coming">
                        <span class="kb-hero-tag">Coming Soon</span>
                        <h2 class="kb-hero-title">Get Ready for What's Next</h2>
                        <p class="kb-hero-subtitle">
                            Fresh drops in gym wear, limited edition flavours, and brand collabs landing soon.
                        </p>
                        <a href="{{ route('products.index') }}" class="kb-hero-cta">
                            Browse Collection <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Nav --}}
            <button class="kb-hero-nav kb-hero-nav--prev" aria-label="Previous slide" onclick="heroSlide(-1)">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button class="kb-hero-nav kb-hero-nav--next" aria-label="Next slide" onclick="heroSlide(1)">
                <i class="bi bi-chevron-right"></i>
            </button>

            {{-- Dots --}}
            <div class="kb-hero-dots">
                <button class="kb-hero-dot is-active" onclick="heroGoTo(0)"></button>
                <button class="kb-hero-dot" onclick="heroGoTo(1)"></button>
                <button class="kb-hero-dot" onclick="heroGoTo(2)"></button>
            </div>
        </div>
    </section>

    <section class="kb-section kb-new-products">
        <div class="kb-new-products-inner">
            <div class="kb-section-header">
                <h2 class="kb-section-title">New Arrivals</h2>
                <div class="kb-section-controls">
                    <button class="kb-carousel-btn kb-carousel-btn--prev" aria-label="Previous products">‹</button>
                    <button class="kb-carousel-btn kb-carousel-btn--next" aria-label="Next products">›</button>
                </div>
            </div>
            <div class="kb-new-carousel-container">
                <div class="kb-new-carousel-track">
                    @forelse ($newProducts as $product)
                        @php
                            $bestPrice = $product->bestPriceForCurrency();
                            $primaryImage = $product->primaryImage()->first();
                            $imagePath = $primaryImage
                                ? 'images/' . $primaryImage->image_url
                                : 'images/placeholders/product-placeholder.jpg';
                            $variant = $product->activeVariants->first();
                        @endphp

                        <article class="kb-new-card">
                            {{-- <div class="kb-new-card-media">
                                @if($product->created_at && $product->created_at->gt(now()->subDays(30)))
                                    <span class="kb-new-badge kb-new-badge--new">New</span>
                                @endif

                                @if($bestPrice && isset($bestPrice->was) && $bestPrice->was > $bestPrice->price)
                                    <span class="kb-new-badge kb-new-badge--sale">Sale</span>
                                @endif 

                                <a href="{{ route('products.show', $product->product_id) }}">
                                    <img
                                        src="{{ asset($imagePath) }}"
                                        alt="{{ $product->product_name }}"
                                        loading="lazy"
                                        onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'"
                                    >
                                </a> --}}
                            <div class="kb-new-card-media">
                                <div class="kb-badge-container">
                                    @if($product->created_at && $product->created_at->gt(now()->subDays(30)))
                                        <span class="kb-new-badge kb-new-badge--new">New</span>
                                    @endif

                                    @if($bestPrice && isset($bestPrice->was) && $bestPrice->was > $bestPrice->price)
                                        <span class="kb-new-badge kb-new-badge--sale">Sale</span>
                                    @endif
                                </div>

                                <a href="{{ route('products.show', $product->product_id) }}">
                                    <img
                                        src="{{ asset($imagePath) }}"
                                        alt="{{ $product->product_name }}"
                                        loading="lazy"
                                        onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'"
                                    >
                                </a>   

                                {{-- Wishlist heart — inside media for correct absolute positioning --}}
                                @if($variant)
                                    <button type="button"
                                            class="kb-wishlist-heart {{ in_array($variant->variant_id, $wishlistVariantIds ?? []) ? 'kb-wishlisted' : '' }}"
                                            data-variant-id="{{ $variant->variant_id }}"
                                            onclick="toggleWishlistCard(this)"
                                            aria-label="Add to wishlist">
                                        <i class="bi {{ in_array($variant->variant_id, $wishlistVariantIds ?? []) ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                    </button>
                                @endif
                            </div>

                            <div class="kb-new-card-body">
                                <h3 class="kb-new-card-title">
                                    <a href="{{ route('products.show', $product->product_id) }}">
                                        {{ $product->product_name }}
                                    </a>
                                </h3>

                                <div class="kb-new-card-prices">
                                    @php
                                        $rate   = $vatRate ?? 0;
                                        $symbol = $bestPrice->symbol ?? '£';
                                        $current = ($bestPrice && isset($bestPrice->price))
                                            ? $bestPrice->price * (1 + $rate) : 0;
                                        $old = ($bestPrice && isset($bestPrice->was) && $bestPrice->was > $bestPrice->price)
                                            ? $bestPrice->was * (1 + $rate) : null;
                                    @endphp

                                    @if($old)
                                        <span class="kb-new-price-old">{{ $symbol }}{{ number_format($old, 2) }}</span>
                                    @endif
                                    <span class="kb-new-price-current">
                                        {{ $symbol }}{{ number_format($current, 2) }}
                                        <span class="kb-muted">{{ $vatLabel }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="kb-new-card-actions">
                                @if($variant)
                                    <div class="cart-control"
                                        data-variant="{{ $variant->variant_id }}"
                                        data-cart-url="{{ route('cart.add') }}">
                                        <button type="button" class="add-btn kb-product-page-add-btn">Add to Basket</button>
                                        <div class="qty-controls" hidden>
                                            <button type="button" class="minus-btn">🗑</button>
                                            <span class="qty">1</span>
                                            <button type="button" class="plus-btn">+</button>
                                        </div>
                                    </div>
                                @endif

                                <a href="{{ route('products.show', $product->product_id) }}" class="kb-new-icon-btn" aria-label="View product">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="kb-no-products">
                            <p>No new products yet. Check back soon.</p>
                        </div>
                    @endforelse
                </div>
                <div class="kb-carousel-dots"></div>
            </div>

            <div class="kb-view-all-container">
                <a href="{{ route('products.index') }}" class="kb-view-all-btn">
                    View All New Products <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <section class="kb-section kb-category-section">
        <div class="kb-category-inner">
            <h2 class="kb-section-title">Shop by Category</h2>
            <div class="kb-category-grid">
                <a href="{{ route('products.index', ['category' => 'gym-apparel']) }}" class="kb-category-card">
                    <div class="kb-category-media">
                        <img src="{{ asset('images/categories/apparel.jpeg') }}" alt="Unisex Apparel">
                        <div class="kb-category-overlay">
                            <span class="kb-category-name-pic">Unisex Apparel</span>
                            <span class="kb-category-cta-pic">Shop clothing</span>
                        </div>
                    </div>
                </a>
                <a href="{{ route('products.index', ['category' => 'nutrition-and-supplements']) }}" class="kb-category-card">
                    <div class="kb-category-media">
                        <img src="{{ asset('images/categories/nutrition.jpg') }}" alt="Performance Nutrition">
                        <div class="kb-category-overlay">
                            <span class="kb-category-name-pic">Performance Nutrition</span>
                            <span class="kb-category-cta-pic">Shop protein &amp; more</span>
                        </div>
                    </div>
                </a>
                <a href="{{ route('products.index', ['category' => 'recovery-and-wellness']) }}" class="kb-category-card">
                    <div class="kb-category-media">
                        <img src="{{ asset('images/categories/supplements.jpg') }}" alt="Advanced Supplements">
                        <div class="kb-category-overlay">
                            <span class="kb-category-name-pic">Advanced Supplements</span>
                            <span class="kb-category-cta-pic">Shop pre-workout, creatine</span>
                        </div>
                    </div>
                </a>
                <a href="{{ route('products.index', ['category' => 'accessories']) }}" class="kb-category-card">
                    <div class="kb-category-media">
                        <img src="{{ asset('images/categories/accessories.jpg') }}" alt="Gym Accessories">
                        <div class="kb-category-overlay">
                            <span class="kb-category-name-pic">Gym Accessories</span>
                            <span class="kb-category-cta-pic">Shop gloves, belts &amp; more</span>
                        </div>
                    </div>
                </a>
                <a href="{{ route('products.index', ['category' => 'gym-equipment']) }}" class="kb-category-card">
                    <div class="kb-category-media">
                        <img src="{{ asset('images/categories/equipment.jpg') }}" alt="Bags & Equipment">
                        <div class="kb-category-overlay">
                            <span class="kb-category-name-pic">Bags &amp; Equipment</span>
                            <span class="kb-category-cta-pic">Shop bags &amp; home gym</span>
                        </div>
                    </div>
                </a>
                <a href="{{ route('products.index', ['deals' => 1]) }}" class="kb-category-card">
                    <div class="kb-category-media">
                        <img src="{{ asset('images/categories/deals.png') }}" alt="Deals">
                        <div class="kb-category-overlay">
                            <span class="kb-category-name-pic">Deals</span>
                            <span class="kb-category-cta-pic">Save on top picks</span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <section class="kb-section kb-testimonials">
        <div class="kb-testimonials-inner">
            <div class="kb-testimonials-header">
                <h2 class="kb-section-title">What our customers say</h2>
                <p class="kb-testimonials-subtitle">
                    Real feedback from people training, lifting and recovering with ProFormance.
                </p>
            </div>
            <div class="kb-testimonials-grid">
                @forelse(($testimonials ?? collect()) as $review)
                    <article class="kb-testimonial-card">
                        <div class="kb-testimonial-rating">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->rating)
                                    <i class="bi bi-star-fill"></i>
                                @elseif($i - 0.5 <= $review->rating)
                                    <i class="bi bi-star-half"></i>
                                @else
                                    <i class="bi bi-star"></i>
                                @endif
                            @endfor
                        </div>
                        <p class="kb-testimonial-text">"{{ Str::limit($review->body, 200) }}"</p>
                        <div class="kb-testimonial-footer">
                            <div class="kb-testimonial-avatar">
                                <span>{{ strtoupper(substr($review->user->first_name ?? '?', 0, 1)) }}</span>
                            </div>
                            <div class="kb-testimonial-meta">
                                <span class="kb-testimonial-name">{{ $review->user->first_name ?? 'Customer' }} {{ strtoupper(substr($review->user->last_name ?? '', 0, 1)) }}.</span>
                                <span class="kb-testimonial-role">Verified buyer{{ $review->product ? ' · ' . ($review->product->brand->brand_name ?? '') : '' }}</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="kb-testimonial-card">
                        <div class="kb-testimonial-rating">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                        </div>
                        <p class="kb-testimonial-text">"Great products and fast delivery. Highly recommended!"</p>
                        <div class="kb-testimonial-footer">
                            <div class="kb-testimonial-avatar"><span>P</span></div>
                            <div class="kb-testimonial-meta">
                                <span class="kb-testimonial-name">ProFormance Customer</span>
                                <span class="kb-testimonial-role">Verified buyer</span>
                            </div>
                        </div>
                    </article>
                @endforelse
            </div>
        </div>
    </section>

<script>
function toggleWishlistCard(btn) {
    const variantId = btn.dataset.variantId;
    const icon = btn.querySelector('i');

    fetch('{{ route("wishlist.toggle") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ variant_id: variantId }),
    })
    .then(res => {
        if (res.status === 401) { window.location.href = '{{ route("login") }}'; return; }
        return res.json();
    })
    .then(data => {
        if (!data) return;
        if (data.in_wishlist) {
            btn.classList.add('kb-wishlisted');
            icon.className = 'bi bi-heart-fill';
        } else {
            btn.classList.remove('kb-wishlisted');
            icon.className = 'bi bi-heart';
        }
    })
    .catch(err => console.error('Wishlist error:', err));
}

    (function() {
        let current = 0;
        const slides = document.querySelectorAll('.kb-hero-slide');
        const dots = document.querySelectorAll('.kb-hero-dot');
        let autoTimer = null;

        function show(idx) {
            slides.forEach((s, i) => {
                s.classList.toggle('is-active', i === idx);
            });
            dots.forEach((d, i) => {
                d.classList.toggle('is-active', i === idx);
            });
            current = idx;
        }

        window.heroSlide = function(dir) {
            let next = (current + dir + slides.length) % slides.length;
            show(next);
            resetAuto();
        };

        window.heroGoTo = function(idx) {
            show(idx);
            resetAuto();
        };

        function resetAuto() {
            clearInterval(autoTimer);
            autoTimer = setInterval(() => {
                heroSlide(1);
            }, 6000);
        }

        resetAuto();
    })();
</script>
@endsection