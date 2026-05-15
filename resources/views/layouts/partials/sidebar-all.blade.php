<div class="kb-sidebar-overlay kb-sidebar-overlay-all"></div>

<div class="kb-sidebar kb-sidebar-all">
    <div class="kb-sidebar-header">
        <span class="kb-sidebar-title">All Categories</span>
        <button class="kb-sidebar-close kb-sidebar-close-all">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="kb-sidebar-content">
        @foreach($mainCategories as $category)
            <div class="kb-category-group">
                <a href="{{ route('products.category', $category->slug) }}"
                class="kb-category-main @if($category->children->count()) has-subcategories @endif">

                    <span class="kb-category-name">
                        {{ $category->category_name }}
                    </span>

                    @if($category->children->count())
                        <span class="kb-category-arrow"><i class="bi bi-chevron-down"></i></span>
                    @endif
                </a>

                @if($category->children->count())
                    <div class="kb-subcategories">
                        @foreach($category->children as $subcategory)
                            <a href="{{ route('products.category', $subcategory->slug) }}" class="kb-subcategory">
                                <span class="kb-subcategory-name">{{ $subcategory->category_name }}</span>
                                <span class="kb-subcategory-count">({{ $subcategory->products_count ?? 0 }})</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        
        <div class="kb-category-group">
            <a href="{{ route('products.index') }}?sort=price_low" class="kb-category-main kb-category-deals">
                <span class="kb-category-name">Hot Deals</span>
                <span class="kb-category-badge">Sale</span>
            </a>
        </div>
        
        <div class="kb-category-group">
            <a href="{{ route('products.index') }}?sort=newest" class="kb-category-main kb-category-new">
                <span class="kb-category-name">New Arrivals</span>
                <span class="kb-category-badge">New</span>
            </a>
        </div>
    </div>
</div>