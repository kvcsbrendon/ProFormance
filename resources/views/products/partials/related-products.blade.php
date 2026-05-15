{{-- resources/views/products/partials/related-products.blade.php --}}

@if(isset($relatedProducts) && $relatedProducts->isNotEmpty())
<section class="kb-related-products">
    <div class="kb-related-header">
        <h2 class="kb-related-title">You Might Also Like</h2>
    </div>
    <div class="kb-related-grid">
        @foreach($relatedProducts as $rp)
        <a href="{{ route('products.show', $rp['product_id']) }}" class="kb-related-card">
            <div class="kb-related-img-wrap">
                <img src="{{ asset('images/' . $rp['image']) }}"
                     alt="{{ $rp['product_name'] }}"
                     class="kb-related-img"
                     loading="lazy">
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
