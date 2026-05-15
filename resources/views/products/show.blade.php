@extends('layouts.app')

@section('content')
<div class="kb-pdp">
    <div class="kb-pdp-container">

        {{-- ── Breadcrumb ── --}}
        <nav class="kb-pdp-breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <i class="bi bi-chevron-right"></i>
            <a href="{{ route('products.index') }}">Products</a>
            @if($product->categories->first())
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('products.category', $product->categories->first()->slug) }}">
                    {{ $product->categories->first()->category_name }}
                </a>
            @endif
            <i class="bi bi-chevron-right"></i>
            <span>{{ $product->product_name }}</span>
        </nav>

        {{-- ══════════════════════════════════════════════
             TOP SECTION: Images + Product Info
        ══════════════════════════════════════════════ --}}
        <div class="kb-pdp-top">

            {{-- ── Left: Image Gallery (variant-filtered) ── --}}
            <div class="kb-pdp-gallery">
                <div class="kb-pdp-thumbs" id="pdp-thumbs">
                    {{-- Thumbnails are rendered by JS based on selected variant --}}
                </div>

                <div class="kb-pdp-main-image" id="pdp-main-image">
                    <img src="{{ asset('images/' . $allImages[0]['url']) }}"
                         alt="{{ $allImages[0]['alt'] }}"
                         id="pdp-main-img"
                         onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'">
                </div>
            </div>

            {{-- ── Right: Product Info ── --}}
            <div class="kb-pdp-info">

                @if($product->brand)
                    <a href="{{ route('products.index', ['brand' => $product->brand->slug]) }}" class="kb-pdp-brand">
                        {{ $product->brand->brand_name }}
                    </a>
                @endif

                <h1 class="kb-pdp-title">{{ $product->product_name }}</h1>

                @if($reviewStats['count'] > 0)
                    <a href="#reviews" class="kb-pdp-rating">
                        <span class="kb-pdp-stars">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($reviewStats['avg']))
                                    <i class="bi bi-star-fill"></i>
                                @elseif($i - $reviewStats['avg'] < 1 && $i - $reviewStats['avg'] > 0)
                                    <i class="bi bi-star-half"></i>
                                @else
                                    <i class="bi bi-star"></i>
                                @endif
                            @endfor
                        </span>
                        <span class="kb-pdp-review-count">
                            {{ number_format($reviewStats['avg'], 1) }} ({{ $reviewStats['count'] }} {{ $reviewStats['count'] === 1 ? 'review' : 'reviews' }})
                        </span>
                    </a>
                @endif

                {{-- Price --}}
                @php
                    $currentPrice = ($bestPrice && $bestPrice->price) ? $bestPrice->price * (1 + $vatRate) : 0;
                    $oldPrice     = ($bestPrice && $bestPrice->was && $bestPrice->was > $bestPrice->price)
                                  ? $bestPrice->was * (1 + $vatRate) : null;
                    $symbol       = $bestPrice->symbol ?? '£';
                @endphp

                <div class="kb-pdp-price-block">
                    @if($oldPrice)
                        <span class="kb-pdp-price-was" id="pdp-price-was">{{ $symbol }}{{ number_format($oldPrice, 2) }}</span>
                    @endif
                    <span class="kb-pdp-price-now" id="pdp-price-now">{{ $symbol }}{{ number_format($currentPrice, 2) }}</span>
                    @if($oldPrice)
                        @php $savings = round((1 - $currentPrice / $oldPrice) * 100); @endphp
                        <span class="kb-pdp-savings" id="pdp-savings">Save {{ $savings }}%</span>
                    @endif
                    <small class="kb-pdp-vat">{{ $vatLabel }}</small>
                </div>

                {{-- Stock status --}}
                <div class="kb-pdp-stock" id="pdp-stock">
                    @if($defaultStock > 0)
                        <span class="kb-pdp-stock-in">
                            <i class="bi bi-check-circle-fill"></i>
                            In Stock{{ $defaultStock <= 5 ? " — only {$defaultStock} left" : '' }}
                        </span>
                    @else
                        <span class="kb-pdp-stock-out">
                            <i class="bi bi-x-circle-fill"></i>
                            Out of Stock
                        </span>
                    @endif
                </div>

                {{-- ── Variant Selectors ── --}}
                @if(count($attributeMap) > 0)
                    <div class="kb-pdp-variants" id="pdp-variants">
                        @foreach($attributeMap as $attrId => $attr)
                            <div class="kb-pdp-variant-group" data-attribute-id="{{ $attrId }}">
                                <label class="kb-pdp-variant-label">
                                    {{ $attr['name'] }}:
                                    <strong id="pdp-selected-{{ $attrId }}">
                                        {{ collect($attr['options'])->first()['value'] ?? '' }}
                                    </strong>
                                </label>
                                <div class="kb-pdp-variant-options">
                                    @foreach($attr['options'] as $optId => $opt)
                                        <button type="button"
                                                class="kb-pdp-opt {{ $loop->first ? 'active' : '' }}"
                                                data-attribute-id="{{ $attrId }}"
                                                data-option-id="{{ $optId }}"
                                                data-value="{{ $opt['value'] }}"
                                                onclick="selectOption(this)">
                                            {{ $opt['value'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ── Bulk Pricing (dynamic per variant) ── --}}
                <div class="kb-bulk-pricing" id="bulk-pricing-section" style="{{ empty($defaultBulkTiers) ? 'display:none;' : '' }}">
                    <div class="kb-bulk-header">
                        <i class="bi bi-box-seam"></i>
                        <span class="kb-bulk-title">Buy More, Save More</span>
                    </div>
                    <div class="kb-bulk-tiers" id="bulk-tiers">
                        @foreach($defaultBulkTiers as $tier)
                        <div class="kb-bulk-tier">
                            <span class="kb-bulk-tier-qty">{{ $tier['min_quantity'] }}+</span>
                            <span class="kb-bulk-tier-price">{{ $symbol ?? '£' }}{{ number_format($tier['price'], 2) }} each</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── Add to Cart / Notify Me / Wishlist ── --}}
                <div class="kb-pdp-actions" id="pdp-actions">
                    <form method="POST" action="{{ route('cart.add') }}" class="kb-pdp-cart-form" id="pdp-cart-form"
                          style="{{ $defaultStock <= 0 ? 'display:none;' : '' }}">
                        @csrf
                        <input type="hidden" name="variant_id" id="pdp-variant-id"
                               value="{{ $defaultVariant->variant_id ?? '' }}">

                        <div class="kb-pdp-qty-row">
                            <label class="kb-pdp-qty-label">Qty</label>
                            <div class="kb-pdp-qty-control">
                                <button type="button" class="kb-pdp-qty-btn" onclick="adjustQty(-1)">−</button>
                                <input type="number" name="quantity" id="pdp-qty" value="1" min="1"
                                       max="{{ $defaultStock }}" class="kb-pdp-qty-input">
                                <button type="button" class="kb-pdp-qty-btn" onclick="adjustQty(1)">+</button>
                            </div>
                        </div>

                        <button type="submit" class="kb-pdp-add-btn">
                            <i class="bi bi-bag-plus"></i>
                            Add to Basket
                        </button>
                    </form>

                    @if($defaultVariant)
                        <button type="button"
                                class="kb-wishlist-btn {{ $inWishlist ? 'kb-wishlisted' : '' }}"
                                id="pdp-wishlist-btn"
                                data-variant-id="{{ $defaultVariant->variant_id }}"
                                onclick="toggleWishlist(this)"
                                title="{{ $inWishlist ? 'Remove from wishlist' : 'Add to wishlist' }}">
                            <i class="bi {{ $inWishlist ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                            <span>{{ $inWishlist ? 'In Wishlist' : 'Add to Wishlist' }}</span>
                        </button>
                    @endif

                    <div class="kb-pdp-notify" id="pdp-notify-block"
                         style="{{ $defaultStock > 0 ? 'display:none;' : '' }}">
                        <p class="kb-pdp-notify-text">
                            <i class="bi bi-bell"></i>
                            Get notified when this item is back in stock
                        </p>
                        <form class="kb-pdp-notify-form" id="pdp-notify-form">
                            @csrf
                            <input type="hidden" name="variant_id" id="pdp-notify-variant-id"
                                   value="{{ $defaultVariant->variant_id ?? '' }}">
                            <input type="email" name="email" class="kb-pdp-notify-email"
                                   placeholder="Enter your email" required
                                   value="{{ auth()->check() ? auth()->user()->loginDetail->email_address ?? '' : '' }}">
                            <button type="submit" class="kb-pdp-notify-btn">Notify Me</button>
                        </form>
                        <p class="kb-pdp-notify-success" id="pdp-notify-success" style="display:none;">
                            <i class="bi bi-check-circle-fill"></i>
                            We'll email you when it's back!
                        </p>
                    </div>
                </div>

                @if(!empty($product->short_description))
                    <div class="kb-pdp-short-desc">
                        <p>{{ $product->short_description }}</p>
                    </div>
                @endif

                <div class="kb-pdp-meta">
                    @if($defaultVariant)
                        <span>SKU: <strong id="pdp-sku">{{ $defaultVariant->sku }}</strong></span>
                    @endif
                    @if($product->categories->first())
                        <span>Category: <a href="{{ route('products.category', $product->categories->first()->slug) }}">
                            {{ $product->categories->first()->category_name }}
                        </a></span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════
             TABS: Description / Specs
        ══════════════════════════════════════════════ --}}
        <div class="kb-pdp-bottom">
            <div class="kb-pdp-tabs">
                <button class="kb-pdp-tab active" data-tab="description" onclick="switchTab(this)">Description</button>
                @if($specifications->count() > 0)
                    <button class="kb-pdp-tab" data-tab="specifications" onclick="switchTab(this)">Specifications</button>
                @endif
            </div>

            <div class="kb-pdp-tab-content active" id="tab-description">
                <div class="kb-pdp-description">
                    {!! nl2br(e($product->product_description)) !!}
                </div>
            </div>

            @if($specifications->count() > 0)
                <div class="kb-pdp-tab-content" id="tab-specifications">
                    @foreach($specifications as $group => $specs)
                        <table class="kb-pdp-specs-table">
                            @if($group)
                                <thead><tr><th colspan="2">{{ $group }}</th></tr></thead>
                            @endif
                            <tbody>
                                @foreach($specs as $spec)
                                    <tr>
                                        <td class="kb-pdp-spec-name">{{ $spec->spec_name }}</td>
                                        <td class="kb-pdp-spec-value">{{ $spec->spec_value }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════
             RELATED PRODUCTS CAROUSEL
        ══════════════════════════════════════════════ --}}
        @if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
        <section class="kb-related-products">
            <div class="kb-related-header">
                <h2 class="kb-related-title">You Might Also Like</h2>
                <div class="kb-related-nav">
                    <button class="kb-related-nav-btn" onclick="relatedScroll(-1)" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
                    <button class="kb-related-nav-btn" onclick="relatedScroll(1)" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
            <div class="kb-related-carousel" id="related-carousel">
                @foreach($relatedProducts as $rp)
                <a href="{{ route('products.show', $rp['product_id']) }}" class="kb-related-card">
                    <div class="kb-related-img-wrap">
                        <img src="{{ asset('images/' . $rp['image']) }}"
                             alt="{{ $rp['product_name'] }}"
                             class="kb-related-img"
                             loading="lazy"
                             onerror="this.src='{{ asset('images/placeholders/product-placeholder.jpg') }}'">
                    </div>
                    <div class="kb-related-info">
                        @if($rp['brand_name'])
                            <span class="kb-related-brand">{{ $rp['brand_name'] }}</span>
                        @endif
                        <span class="kb-related-name">{{ $rp['product_name'] }}</span>
                        <div class="kb-related-pricing">
                            @if($rp['price'])
                                <span class="kb-related-price">{{ $rp['symbol'] }}{{ number_format($rp['price'], 2) }}</span>
                                @if($rp['was_price'])
                                    <span class="kb-related-was-price">{{ $rp['symbol'] }}{{ number_format($rp['was_price'], 2) }}</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- ══════════════════════════════════════════════
             REVIEWS SECTION
        ══════════════════════════════════════════════ --}}
        <div class="kb-reviews-section" id="reviews">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="kb-reviews-title" style="margin: 0;">Customer Reviews</h2>
                
                @if($reviews->isNotEmpty())
                    <form method="GET" action="{{ url()->current() }}#reviews" id="review-sort-form" style="margin: 0;">
                        @foreach(request()->except('review_sort') as $key => $value)
                            @if(!is_array($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        
                        <select name="review_sort" onchange="this.form.submit()" class="kb-form-input" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.9rem; cursor: pointer; background-color: #f9fafb;">
                            <option value="newest" {{ request('review_sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                            <option value="oldest" {{ request('review_sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                            <option value="highest_rating" {{ request('review_sort') == 'highest_rating' ? 'selected' : '' }}>Highest Rating</option>
                            <option value="lowest_rating" {{ request('review_sort') == 'lowest_rating' ? 'selected' : '' }}>Lowest Rating</option>
                            <option value="most_helpful" {{ request('review_sort') == 'most_helpful' ? 'selected' : '' }}>Most Helpful</option>
                        </select>
                    </form>
                @endif
            </div>

            @if($reviewStats['count'] > 0)
                <div class="kb-reviews-overview">
                    <div class="kb-reviews-avg">
                        <span class="kb-reviews-avg-num">{{ number_format($reviewStats['avg'], 1) }}</span>
                        <div class="kb-stars-display">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($reviewStats['avg']))
                                    <i class="bi bi-star-fill"></i>
                                @elseif($i - $reviewStats['avg'] < 1 && $i - $reviewStats['avg'] > 0)
                                    <i class="bi bi-star-half"></i>
                                @else
                                    <i class="bi bi-star"></i>
                                @endif
                            @endfor
                        </div>
                        <span class="kb-reviews-count">Based on {{ $reviewStats['count'] }} {{ $reviewStats['count'] === 1 ? 'review' : 'reviews' }}</span>
                    </div>

                    <div class="kb-reviews-breakdown">
                        @for($star = 5; $star >= 1; $star--)
                            @php
                                $starCount = $reviews->where('rating', $star)->count();
                                $pct = $reviewStats['count'] > 0 ? ($starCount / $reviewStats['count']) * 100 : 0;
                            @endphp
                            <div class="kb-reviews-bar-row">
                                <span class="kb-reviews-bar-label">{{ $star }} <i class="bi bi-star-fill"></i></span>
                                <div class="kb-reviews-bar-track">
                                    <div class="kb-reviews-bar-fill" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="kb-reviews-bar-count">{{ $starCount }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            @endif

            @auth
                @if(!$userReview)
                    <div class="kb-review-form-card">
                        <h3>Write a Review</h3>
                        @if($errors->has('review'))
                            <div class="kb-form-error" style="margin-bottom: 1rem;">{{ $errors->first('review') }}</div>
                        @endif
                        <form method="POST" action="{{ route('reviews.store', $product->product_id) }}" enctype="multipart/form-data">
                            @csrf
                            
                            <div class="kb-form-group">
                                <label class="kb-form-label">Your Rating</label>
                                <div class="kb-star-rating-input">
                                    @for($i = 5; $i >= 1; $i--)
                                        <input type="radio" name="rating" value="{{ $i }}" id="star-{{ $i }}"
                                            {{ old('rating') == $i ? 'checked' : '' }}>
                                        <label for="star-{{ $i }}" title="{{ $i }} stars">
                                            <i class="bi bi-star-fill"></i>
                                        </label>
                                    @endfor
                                </div>
                                @error('rating') <div class="kb-form-error">{{ $message }}</div> @enderror
                            </div>
                            
                            <div class="kb-form-group">
                                <label class="kb-form-label">Title <span class="kb-form-optional">(optional)</span></label>
                                <input type="text" name="title" class="kb-form-input"
                                    value="{{ old('title') }}" placeholder="Summarise your experience">
                            </div>
                            
                            <div class="kb-form-group">
                                <label class="kb-form-label">Your Review</label>
                                <textarea name="body" class="kb-form-input kb-form-textarea" rows="4"
                                        placeholder="What did you like or dislike?">{{ old('body') }}</textarea>
                            </div>

                            {{-- NEW: Image Upload Field --}}
                            <div class="kb-form-group">
                                <label class="kb-form-label">Add Images <span class="kb-form-optional">(optional)</span></label>
                                <input type="file" name="images[]" multiple accept="image/jpeg, image/png, image/webp" class="kb-form-input" style="padding: 8px; background: #fff;">
                                <div style="font-size: 0.8rem; color: #6b7280; margin-top: 4px;">You can select multiple images. Max 2MB per image (JPG, PNG, WEBP).</div>
                                @error('images.*') <div class="kb-form-error">{{ $message }}</div> @enderror
                            </div>

                            <button type="submit" class="kb-account-btn kb-account-btn-primary">Submit Review</button>
                        </form>
                @else
                    <div class="kb-review-already">
                        <i class="bi bi-check-circle"></i>
                        <span>You've already reviewed this product. <a href="{{ route('account.reviews') }}">Edit it from your account</a>.</span>
                    </div>
                @endif
            @else
                <div class="kb-review-login-prompt">
                    <a href="{{ route('login') }}">Sign in</a> to leave a review.
                </div>
            @endauth

            @if($reviews->isNotEmpty())
                <div class="kb-reviews-list">
                    @foreach($reviews as $review)
                        <div class="kb-review-card" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb;">
                            <div class="kb-review-card-header" style="display:flex; justify-content:space-between; margin-bottom: 10px;">
                                <div class="kb-review-card-user" style="display:flex; align-items:center;">
                                    <i class="bi bi-person-circle" style="margin-right: 8px; color: #6b7280;"></i>
                                    <span class="kb-review-card-name" style="font-weight: 600;">{{ $review->user->first_name ?? 'Anonymous' }}</span>
                                    @if($review->is_verified_purchase)
                                        <span class="kb-badge-success" style="font-size:0.75rem; color:#10b981; margin-left:8px; display:flex; align-items:center; gap:3px;">
                                            <i class="bi bi-patch-check-fill"></i> Verified Purchase
                                        </span>
                                    @endif
                                </div>
                                <span class="kb-review-card-date" style="font-size:0.85rem; color:#6b7280;">{{ $review->created_at->format('d M Y') }}</span>
                            </div>

                            <div class="kb-stars-display kb-stars-sm" style="color: var(--kb-amber-500); margin-bottom: 10px;">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="bi {{ $i <= $review->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                                @endfor
                            </div>

                            @if($review->title) 
                                <h4 class="kb-review-card-title" style="margin: 0 0 8px 0; font-size: 1.05rem;">{{ $review->title }}</h4> 
                            @endif
                            
                            @if($review->body) 
                                <p class="kb-review-card-body">{{ $review->body }}</p> 
                            @endif

                            @if($review->images && $review->images->count() > 0)
                                <div class="kb-review-images" style="display:flex; gap:10px; margin-bottom:15px;">
                                    @foreach($review->images as $img)
                                        <a href="{{ asset('images/' . $img->image_path) }}" target="_blank">
                                            <img src="{{ asset('images/' . $img->image_path) }}" style="width:80px; height:80px; object-fit:cover; border-radius:4px; border: 1px solid #e5e7eb;">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if($review->admin_reply)
                                <div class="kb-review-admin-reply" style="background: #f8fafc; border-left: 3px solid var(--kb-primary); padding: 12px; margin-bottom: 15px; border-radius: 4px;">
                                    <strong style="font-size: 0.9rem; color: #1f2937;">Response from ProFormance:</strong>
                                    <p style="margin: 5px 0 0 0; font-size: 0.85rem; color: #4b5563;">{{ $review->admin_reply }}</p>
                                </div>
                            @endif

                            <div class="kb-review-actions" style="font-size:0.85rem; display:flex; gap:15px; align-items:center; padding-top: 10px;">
                                @php $helpfulCount = $review->helpfulVotes->count(); @endphp
                                <button type="button" 
        								class="btn-helpful" 
        								data-review-id="{{ $review->review_id }}"
       									data-url="{{ route('reviews.helpful', ['review' => $review->review_id]) }}" 
        								style="background:none; border:none; color:var(--kb-primary); cursor:pointer; padding: 0;">
    								<i class="bi bi-hand-thumbs-up{{ $review->user_vote ? '-fill' : '' }}"></i> Helpful (<span class="helpful-count">{{ $helpfulCount }}</span>)
								</button>
                                <span style="color:#6b7280;">{{ $helpfulCount }} people found this helpful.</span>
                                
                                <button type="button" onclick="openReportModal({{ $review->review_id }})" style="margin-left:auto; background:none; border:none; color:#dc2626; cursor:pointer; padding: 0;">
                                    <i class="bi bi-flag"></i> Report
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="kb-reviews-empty">
                    <i class="bi bi-chat-square-text"></i>
                    <p>No reviews yet. Be the first to review this product!</p>
                </div>
            @endif
        </div>

    </div>
</div>
<div id="reportReviewModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div class="kb-report-background" style="padding:24px; border-radius:8px; width:100%; max-width:400px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h3 class="kb-report-background-h3" style="margin:0; font-size:1.2rem;">Report Review</h3>
            <button type="button" onclick="closeReportModal()" style="background:none; border:none; font-size:1.5rem; color:#6b7280; cursor:pointer; line-height:1;">&times;</button>
        </div>
        
        <form id="reportReviewForm" method="POST" action="">
            @csrf
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:8px; font-size:0.9rem; color:#374151;">Why are you reporting this review?</label>
                <select name="reason" required class="kb-form-input" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px; font-size:0.9rem;">
                    <option value="" disabled selected>Select a reason...</option>
                    <option value="spam">Spam or Promotional</option>
                    <option value="inappropriate">Offensive or Inappropriate</option>
                    <option value="unrelated">Unrelated to the Product</option>
                    <option value="fake">Fake or Paid Review</option>
                </select>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeReportModal()" style="padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; cursor:pointer; background:#fff; color:#374151; font-weight:500;">Cancel</button>
                <button type="submit" style="padding:8px 16px; background:#dc2626; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500;">Submit Report</button>
            </div>
        </form>
    </div>
</div>
<script>
// ── Data from server ──
const variants    = @json($variantsJson);
const allImages   = @json($allImages);
const bulkTiers   = @json($bulkTiers ?? []);
const symbol      = '{{ $symbol }}';
const imgBase     = '{{ asset("images") }}/';
const placeholder = '{{ asset("images/placeholders/product-placeholder.jpg") }}';
let   currentImg  = 0;
let   selectedOpts = {};
let   currentVariantId = {{ $defaultVariant->variant_id ?? 'null' }};

// Init selected options
document.querySelectorAll('.kb-pdp-variant-group').forEach(group => {
    const firstBtn = group.querySelector('.kb-pdp-opt.active');
    if (firstBtn) {
        selectedOpts[firstBtn.dataset.attributeId] = parseInt(firstBtn.dataset.optionId);
    }
});

// ── Gallery: render thumbnails for a variant ──
function renderGallery(variantId) {
    const thumbsEl = document.getElementById('pdp-thumbs');
    const mainImg  = document.getElementById('pdp-main-img');

    // Filter images for this variant
    let imgs = allImages.filter(img => img.variant_id === variantId);

    // Fallback: if variant has no images, show all
    if (imgs.length === 0) {
        imgs = allImages;
    }

    // Render thumbnails
    thumbsEl.innerHTML = '';
    imgs.forEach((img, i) => {
        const btn = document.createElement('button');
        btn.className = 'kb-pdp-thumb' + (i === 0 ? ' active' : '');
        btn.onclick = () => {
            mainImg.src = imgBase + img.url;
            mainImg.alt = img.alt;
            thumbsEl.querySelectorAll('.kb-pdp-thumb').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        };
        const thumbImg = document.createElement('img');
        thumbImg.src = imgBase + img.url;
        thumbImg.alt = img.alt;
        thumbImg.onerror = function() { this.src = placeholder; };
        btn.appendChild(thumbImg);
        thumbsEl.appendChild(btn);
    });

    // Set main image to first of this variant
    if (imgs.length > 0) {
        mainImg.src = imgBase + imgs[0].url;
        mainImg.alt = imgs[0].alt;
    }
}

// ── Bulk Pricing: render tiers for a variant ──
function renderBulkTiers(variantId) {
    const section = document.getElementById('bulk-pricing-section');
    const container = document.getElementById('bulk-tiers');
    const tiers = bulkTiers[variantId] || [];

    if (tiers.length === 0) {
        section.style.display = 'none';
        return;
    }

    section.style.display = '';
    container.innerHTML = '';
    tiers.forEach(tier => {
        const div = document.createElement('div');
        div.className = 'kb-bulk-tier';
        div.innerHTML = `<span class="kb-bulk-tier-qty">${tier.min_quantity}+</span>` +
                         `<span class="kb-bulk-tier-price">${symbol}${tier.price.toFixed(2)} each</span>`;
        container.appendChild(div);
    });
}

// ── Variant Selection ──
function selectOption(btn) {
    const attrId = btn.dataset.attributeId;
    const optId  = parseInt(btn.dataset.optionId);
    const value  = btn.dataset.value;

    btn.closest('.kb-pdp-variant-options').querySelectorAll('.kb-pdp-opt').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const label = document.getElementById('pdp-selected-' + attrId);
    if (label) label.textContent = value;

    selectedOpts[attrId] = optId;

    const match = findVariant();
    if (match) updateProductForVariant(match);
}

function findVariant() {
    const selectedIds = Object.values(selectedOpts).sort((a, b) => a - b);

    for (const v of variants) {
        const vOpts = [...v.options].sort((a, b) => a - b);
        if (vOpts.length === selectedIds.length && vOpts.every((val, i) => val === selectedIds[i])) {
            return v;
        }
    }

    let bestMatch = null, bestScore = 0;
    for (const v of variants) {
        const score = Object.values(selectedOpts).filter(id => v.options.includes(id)).length;
        if (score > bestScore) { bestScore = score; bestMatch = v; }
    }
    return bestMatch;
}

function updateProductForVariant(v) {
    currentVariantId = v.id;
    document.getElementById('pdp-variant-id').value = v.id;
    document.getElementById('pdp-notify-variant-id').value = v.id;

    const wlBtn = document.getElementById('pdp-wishlist-btn');
    if (wlBtn) wlBtn.dataset.variantId = v.id;

    // Price
    if (v.price !== null) {
        document.getElementById('pdp-price-now').textContent = symbol + v.price.toFixed(2);
        const wasEl = document.getElementById('pdp-price-was');
        const saveEl = document.getElementById('pdp-savings');

        if (v.was_price && v.was_price > v.price) {
            if (wasEl) { wasEl.textContent = symbol + v.was_price.toFixed(2); wasEl.style.display = ''; }
            if (saveEl) {
                saveEl.textContent = 'Save ' + Math.round((1 - v.price / v.was_price) * 100) + '%';
                saveEl.style.display = '';
            }
        } else {
            if (wasEl) wasEl.style.display = 'none';
            if (saveEl) saveEl.style.display = 'none';
        }
    }

    // Stock
    const stockEl = document.getElementById('pdp-stock');
    const cartForm = document.getElementById('pdp-cart-form');
    const notifyBlock = document.getElementById('pdp-notify-block');

    if (v.stock > 0) {
        const lowStock = v.stock <= 5 ? ` — only ${v.stock} left` : '';
        stockEl.innerHTML = `<span class="kb-pdp-stock-in"><i class="bi bi-check-circle-fill"></i> In Stock${lowStock}</span>`;
        cartForm.style.display = '';
        notifyBlock.style.display = 'none';
        document.getElementById('pdp-qty').max = v.stock;
        document.getElementById('pdp-qty').value = 1;
    } else {
        stockEl.innerHTML = '<span class="kb-pdp-stock-out"><i class="bi bi-x-circle-fill"></i> Out of Stock</span>';
        cartForm.style.display = 'none';
        notifyBlock.style.display = '';
    }

    // SKU
    const skuEl = document.getElementById('pdp-sku');
    if (skuEl) skuEl.textContent = v.sku;

    // Gallery — show only this variant's images
    renderGallery(v.id);

    // Bulk pricing — show this variant's tiers
    renderBulkTiers(v.id);
}

// ── Quantity ──
function adjustQty(delta) {
    const input = document.getElementById('pdp-qty');
    input.value = Math.max(1, Math.min(parseInt(input.max) || 99, parseInt(input.value) + delta));
}

// ── Tabs ──
function switchTab(btn) {
    document.querySelectorAll('.kb-pdp-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.kb-pdp-tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
}

// ── Related Products Carousel ──
function relatedScroll(dir) {
    const el = document.getElementById('related-carousel');
    if (!el) return;
    const card = el.querySelector('.kb-related-card');
    const scrollAmt = card ? (card.offsetWidth + 16) * 2 : 300;
    el.scrollBy({ left: dir * scrollAmt, behavior: 'smooth' });
}

// ── Notify Me (AJAX) ──
document.getElementById('pdp-notify-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('{{ route("stock.notify") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            this.style.display = 'none';
            document.getElementById('pdp-notify-success').style.display = 'flex';
        } else {
            alert(data.message || 'Something went wrong.');
        }
    })
    .catch(() => alert('Could not submit. Please try again.'));
});

// ── Wishlist toggle ──
function toggleWishlist(btn) {
    const variantId = btn.dataset.variantId;
    const icon = btn.querySelector('i');
    const text = btn.querySelector('span');

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
            if (text) text.textContent = 'In Wishlist';
        } else {
            btn.classList.remove('kb-wishlisted');
            icon.className = 'bi bi-heart';
            if (text) text.textContent = 'Add to Wishlist';
        }
    })
    .catch(err => console.error('Wishlist error:', err));
}

// ── Init: render gallery for default variant ──
if (currentVariantId) {
    renderGallery(currentVariantId);
}

// ── Report Modal Logic ──
function openReportModal(reviewId) {
    const modal = document.getElementById('reportReviewModal');
    const form = document.getElementById('reportReviewForm');
    
    // Set the dynamic action URL for the form based on the review ID
    form.action = `/reviews/${reviewId}/report`;
    
    // Show the modal
    modal.style.display = 'flex';
}

function closeReportModal() {
    document.getElementById('reportReviewModal').style.display = 'none';
}

// Close modal if user clicks on the dark background outside of it
window.addEventListener('click', function(event) {
    const modal = document.getElementById('reportReviewModal');
    if (event.target === modal) {
        closeReportModal();
    }
});

window.loginUrl = "{{ route('login') }}";
</script>
@endsection
