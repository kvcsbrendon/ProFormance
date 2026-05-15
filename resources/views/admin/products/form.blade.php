{{-- resources/views/admin/products/form.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.products.index') }}" class="kb-admin-back"><i class="bi bi-arrow-left"></i> Back to Products</a>
    <h1 class="kb-admin-title">{{ $product ? 'Edit Product' : 'Add Product' }}</h1>
</div>

{{-- ═══ PRODUCT DETAILS ═══ --}}
<form method="POST" action="{{ $product ? route('admin.products.update', $product->product_id) : route('admin.products.store') }}">
    @csrf
    @if($product) @method('PUT') @endif
    <div class="kb-admin-row">
        <div class="kb-admin-card kb-admin-card-wide">
            <h3 class="kb-admin-card-title">Product Details</h3>
            <div class="kb-form-group">
                <label class="kb-form-label">Product Name *</label>
                <input type="text" name="product_name" class="kb-form-input" required value="{{ old('product_name', $product->product_name ?? '') }}">
            </div>
            <div class="kb-form-row">
                <div class="kb-form-group">
                    <label class="kb-form-label">Brand *</label>
                    <select name="brand_id" class="kb-form-input" required>
                        <option value="">Select brand</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->brand_id }}" {{ old('brand_id', $product->brand_id ?? '') == $b->brand_id ? 'selected' : '' }}>{{ $b->brand_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="kb-form-group">
                    <label class="kb-form-label">Status</label>
                    <select name="is_active" class="kb-form-input">
                        <option value="1" {{ old('is_active', $product->is_active ?? 1) ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ !old('is_active', $product->is_active ?? 1) ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Short Description</label>
                <input type="text" name="short_description" class="kb-form-input" maxlength="500" value="{{ old('short_description', $product->short_description ?? '') }}">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Full Description</label>
                <textarea name="product_description" class="kb-form-input kb-form-textarea" rows="5">{{ old('product_description', $product->product_description ?? '') }}</textarea>
            </div>
        </div>
        <div class="kb-admin-card-sidebar-stack">
            <div class="kb-admin-card">
                <h3 class="kb-admin-card-title">Categories</h3>
                <div class="kb-admin-checkbox-list">
                    @foreach($categories as $cat)
                        <label class="kb-checkbox-label"><input type="checkbox" name="categories[]" value="{{ $cat->category_id }}" {{ in_array($cat->category_id, old('categories', $selectedCategories)) ? 'checked' : '' }}> {{ $cat->category_name }}</label>
                        @foreach($cat->children as $child)
                            <label class="kb-checkbox-label" style="padding-left:1.5rem;"><input type="checkbox" name="categories[]" value="{{ $child->category_id }}" {{ in_array($child->category_id, old('categories', $selectedCategories)) ? 'checked' : '' }}> {{ $child->category_name }}</label>
                        @endforeach
                    @endforeach
                </div>
            </div>
            <div class="kb-admin-card">
                <h3 class="kb-admin-card-title">SEO</h3>
                <div class="kb-form-group"><label class="kb-form-label">Meta Title</label><input type="text" name="meta_title" class="kb-form-input" maxlength="200" value="{{ old('meta_title', $product->meta_title ?? '') }}"></div>
                <div class="kb-form-group"><label class="kb-form-label">Meta Description</label><textarea name="meta_description" class="kb-form-input kb-form-textarea" rows="3" maxlength="300">{{ old('meta_description', $product->meta_description ?? '') }}</textarea></div>
            </div>
        </div>
    </div>
    <div class="kb-admin-form-actions">
        <button type="submit" class="kb-admin-btn"><i class="bi bi-check-circle"></i> {{ $product ? 'Save Changes' : 'Create Product' }}</button>
        <a href="{{ route('admin.products.index') }}" class="kb-admin-btn-outline">Cancel</a>
    </div>
</form>

@if($product)
{{-- ═══ VARIANTS ═══ --}}
<div class="kb-admin-card" style="margin-top:1.5rem;" id="variants">
    <div class="kb-admin-card-header">
        <h3 class="kb-admin-card-title"><i class="bi bi-layers"></i> Variants ({{ $product->variants->count() }})</h3>
        <button type="button" class="kb-admin-btn" onclick="document.getElementById('add-variant-form').classList.toggle('kb-hidden')"><i class="bi bi-plus-circle"></i> Add Variant</button>
    </div>

    {{-- Add variant form (hidden by default) --}}
    <div id="add-variant-form" class="kb-hidden kb-admin-variant-form-box">
        <form method="POST" action="{{ route('admin.products.variants.store', $product->product_id) }}">
            @csrf
            <h4 style="margin:0 0 0.75rem;">New Variant</h4>
            <div class="kb-form-row">
                <div class="kb-form-group"><label class="kb-form-label">Title</label><input type="text" name="title" class="kb-form-input" placeholder="e.g. Red / Large"></div>
                <div class="kb-form-group"><label class="kb-form-label">SKU *</label><input type="text" name="sku" class="kb-form-input" required></div>
                <div class="kb-form-group"><label class="kb-form-label">Barcode</label><input type="text" name="barcode" class="kb-form-input"></div>
            </div>
            <div class="kb-form-row">
                <div class="kb-form-group"><label class="kb-form-label">Stock *</label><input type="number" name="stock" class="kb-form-input" min="0" value="0" required></div>
                <div class="kb-form-group"><label class="kb-form-label">Reorder Point</label><input type="number" name="reorder" class="kb-form-input" min="0"></div>
                <div class="kb-form-group"><label class="kb-form-label">Active</label><select name="is_active" class="kb-form-input"><option value="1" selected>Yes</option><option value="0">No</option></select></div>
            </div>

            {{-- Variant Options (Size, Colour, etc.) --}}
            @if(isset($variantAttributes) && $variantAttributes->isNotEmpty())
                <p class="kb-admin-muted" style="margin:0.75rem 0 0.5rem;"><strong>Variant Options</strong> — select which options this variant represents</p>
                <div class="kb-form-row">
                    @foreach($variantAttributes as $attr)
                        <div class="kb-form-group">
                            <label class="kb-form-label">{{ $attr->display_name }}</label>
                            <select name="options[]" class="kb-form-input">
                                <option value="">— None —</option>
                                @foreach($attr->options as $opt)
                                    <option value="{{ $opt->option_id }}">{{ $opt->display_value }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="kb-admin-muted" style="margin:0.5rem 0;"><strong>Prices</strong> (in pence — e.g. 4999 = £49.99)</p>
            @foreach(($currencies ?? collect()) as $curr)
            <div class="kb-form-row">
                <div class="kb-form-group"><label class="kb-form-label">{{ $curr->currency_code }} Price *</label>
                    <input type="hidden" name="prices[{{ $loop->index }}][currency_code]" value="{{ $curr->currency_code }}">
                    <input type="number" name="prices[{{ $loop->index }}][price_penny]" class="kb-form-input" min="0" required placeholder="e.g. 4999">
                </div>
                <div class="kb-form-group"><label class="kb-form-label">{{ $curr->currency_code }} Was Price</label>
                    <input type="number" name="prices[{{ $loop->index }}][was_price_penny]" class="kb-form-input" min="0" placeholder="Optional">
                </div>
            </div>
            @endforeach
            <div class="kb-admin-form-actions"><button type="submit" class="kb-admin-btn"><i class="bi bi-plus"></i> Add Variant</button></div>
        </form>
    </div>

    {{-- Existing variants --}}
    @foreach($product->variants as $v)
        <div class="kb-admin-variant-block">
            <div class="kb-admin-variant-header" onclick="this.parentElement.classList.toggle('kb-collapsed')" style="cursor:pointer;">
                <div>
                    <strong>{{ $v->title ?? $v->sku }}</strong>
                    <span class="kb-admin-pill {{ $v->is_active ? 'kb-pill-green' : 'kb-pill-grey' }}">{{ $v->is_active ? 'Active' : 'Inactive' }}</span>
                    @if($v->options->isNotEmpty())
                        @foreach($v->options as $opt)
                            <span class="kb-admin-pill kb-pill-blue" style="font-size: 10px;">{{ $opt->attribute->display_name ?? '' }}: {{ $opt->display_value }}</span>
                        @endforeach
                    @else
                        <span class="kb-admin-pill kb-pill-amber" style="font-size: 10px;">No options set</span>
                    @endif
                </div>
                <i class="bi bi-chevron-down kb-admin-collapse-icon"></i>
            </div>
            <div class="kb-admin-variant-body">
                <form method="POST" action="{{ route('admin.products.variants.update', [$product->product_id, $v->variant_id]) }}">
                    @csrf @method('PUT')
                    <div class="kb-form-row">
                        <div class="kb-form-group"><label class="kb-form-label">Title</label><input type="text" name="title" class="kb-form-input" value="{{ $v->title }}"></div>
                        <div class="kb-form-group"><label class="kb-form-label">SKU *</label><input type="text" name="sku" class="kb-form-input" value="{{ $v->sku }}" required></div>
                        <div class="kb-form-group"><label class="kb-form-label">Barcode</label><input type="text" name="barcode" class="kb-form-input" value="{{ $v->barcode }}"></div>
                    </div>
                    <div class="kb-form-row">
                        <div class="kb-form-group"><label class="kb-form-label">Stock</label><input type="number" name="stock" class="kb-form-input" min="0" value="{{ $v->inventory?->available_stock ?? 0 }}" required></div>
                        <div class="kb-form-group"><label class="kb-form-label">Reorder Pt</label><input type="number" name="reorder" class="kb-form-input" min="0" value="{{ $v->inventory?->reorder_point }}"></div>
                        <div class="kb-form-group"><label class="kb-form-label">Active</label><select name="is_active" class="kb-form-input"><option value="1" {{ $v->is_active ? 'selected' : '' }}>Yes</option><option value="0" {{ !$v->is_active ? 'selected' : '' }}>No</option></select></div>
                    </div>

                    {{-- Variant Options --}}
                    @if(isset($variantAttributes) && $variantAttributes->isNotEmpty())
                        <p class="kb-admin-muted" style="margin:0.5rem 0;"><strong>Variant Options</strong></p>
                        <div class="kb-form-row">
                            @foreach($variantAttributes as $attr)
                                @php
                                    $selectedOpt = $v->options->firstWhere('attribute_id', $attr->attribute_id);
                                @endphp
                                <div class="kb-form-group">
                                    <label class="kb-form-label">{{ $attr->display_name }}</label>
                                    <select name="options[]" class="kb-form-input">
                                        <option value="">— None —</option>
                                        @foreach($attr->options as $opt)
                                            <option value="{{ $opt->option_id }}" {{ $selectedOpt && $selectedOpt->option_id == $opt->option_id ? 'selected' : '' }}>
                                                {{ $opt->display_value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Prices --}}
                    @foreach(($currencies ?? collect()) as $curr)
                    @php $price = $v->prices->firstWhere('currency_code', $curr->currency_code); @endphp
                    <div class="kb-form-row">
                        <div class="kb-form-group"><label class="kb-form-label">{{ $curr->currency_code }} Price</label>
                            <input type="hidden" name="prices[{{ $loop->index }}][currency_code]" value="{{ $curr->currency_code }}">
                            <input type="number" name="prices[{{ $loop->index }}][price_penny]" class="kb-form-input" min="0" value="{{ $price->price_penny ?? '' }}" required>
                        </div>
                        <div class="kb-form-group"><label class="kb-form-label">{{ $curr->currency_code }} Was</label>
                            <input type="number" name="prices[{{ $loop->index }}][was_price_penny]" class="kb-form-input" min="0" value="{{ $price->was_price_penny ?? '' }}">
                        </div>
                    </div>
                    @endforeach

                    {{-- Actions row --}}
                    <div class="kb-admin-form-actions" style="margin-top: 0.75rem;">
                        <button type="submit" class="kb-admin-btn-sm">Save Variant</button>
                </form>
                        <a href="{{ route('admin.bulk-pricing.index', $v->variant_id) }}" class="kb-admin-btn-sm kb-admin-btn-sm-outline">
                            <i class="bi bi-layers"></i> Bulk Pricing
                        </a>
                        <form method="POST" action="{{ route('admin.products.variants.destroy', [$product->product_id, $v->variant_id]) }}" onsubmit="return confirm('Delete this variant and all its images/prices?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-danger">Delete</button>
                        </form>
                    </div>

                {{-- Images for this variant --}}
                <div class="kb-admin-variant-images">
                    @foreach($v->images()->orderBy('sort_order')->get() as $img)
                    <div class="kb-admin-img-thumb">
                        <img src="{{ asset('images/' . $img->image_url) }}" alt="{{ $img->alt_text }}">
                        <form method="POST" action="{{ route('admin.products.images.destroy', [$product->product_id, $img->image_id]) }}" class="kb-admin-img-delete">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete image"><i class="bi bi-x-circle-fill"></i></button>
                        </form>
                    </div>
                    @endforeach
                    <form method="POST" action="{{ route('admin.products.images.store', [$product->product_id, $v->variant_id]) }}" enctype="multipart/form-data" class="kb-admin-img-upload">
                        @csrf
                        <label class="kb-admin-img-add">
                            <i class="bi bi-plus-lg"></i>
                            <span>Add Images</span>
                            <input type="file" name="images[]" multiple accept="image/*" onchange="this.form.submit()" hidden>
                        </label>
                    </form>
                </div>
            </div>{{-- .kb-admin-variant-body --}}
        </div>{{-- .kb-admin-variant-block --}}
    @endforeach
</div>

{{-- ═══ SPECIFICATIONS ═══ --}}
<div class="kb-admin-card" style="margin-top:1.5rem;" id="specs">
    <h3 class="kb-admin-card-title"><i class="bi bi-list-check"></i> Specifications</h3>
    <form method="POST" action="{{ route('admin.products.specs.update', $product->product_id) }}" id="specs-form">
        @csrf
        <div id="specs-list">
            @foreach(($specs ?? collect()) as $i => $spec)
            <div class="kb-admin-spec-row" data-index="{{ $i }}">
                <input type="text" name="specs[{{ $i }}][spec_group]" class="kb-form-input" placeholder="Group (e.g. Dimensions)" value="{{ $spec->spec_group }}">
                <input type="text" name="specs[{{ $i }}][spec_name]" class="kb-form-input" placeholder="Name *" value="{{ $spec->spec_name }}" required>
                <input type="text" name="specs[{{ $i }}][spec_value]" class="kb-form-input" placeholder="Value *" value="{{ $spec->spec_value }}" required>
                <input type="hidden" name="specs[{{ $i }}][sort_order]" value="{{ $i }}">
                <button type="button" class="kb-admin-btn-sm kb-admin-btn-sm-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
            </div>
            @endforeach
        </div>
        <button type="button" class="kb-admin-btn-outline" style="margin-top:0.5rem;" onclick="addSpecRow()"><i class="bi bi-plus"></i> Add Row</button>
        <div class="kb-admin-form-actions"><button type="submit" class="kb-admin-btn"><i class="bi bi-check-circle"></i> Save Specifications</button></div>
    </form>
</div>

<script>
function addSpecRow() {
    const list = document.getElementById('specs-list');
    const i = list.children.length;
    const row = document.createElement('div');
    row.className = 'kb-admin-spec-row';
    row.innerHTML = `
        <input type="text" name="specs[${i}][spec_group]" class="kb-form-input" placeholder="Group">
        <input type="text" name="specs[${i}][spec_name]" class="kb-form-input" placeholder="Name *" required>
        <input type="text" name="specs[${i}][spec_value]" class="kb-form-input" placeholder="Value *" required>
        <input type="hidden" name="specs[${i}][sort_order]" value="${i}">
        <button type="button" class="kb-admin-btn-sm kb-admin-btn-sm-danger" onclick="this.parentElement.remove()"><i class="bi bi-trash"></i></button>
    `;
    list.appendChild(row);
}
</script>
@endif
@endsection