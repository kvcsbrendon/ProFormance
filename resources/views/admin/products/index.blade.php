{{-- resources/views/admin/products/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <div class="kb-admin-page-header">
        <div>
            <h1 class="kb-admin-title">Products</h1>
            <p class="kb-admin-subtitle">Manage your product catalogue.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="kb-admin-btn"><i class="bi bi-plus-circle"></i> Add Product</a>
    </div>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search products…" value="{{ request('search') }}">
        <select name="brand" class="kb-form-input">
            <option value="">All Brands</option>
            @foreach($brands as $b)
                <option value="{{ $b->brand_id }}" {{ request('brand') == $b->brand_id ? 'selected' : '' }}>{{ $b->brand_name }}</option>
            @endforeach
        </select>
        <select name="active" class="kb-form-input">
            <option value="">All Status</option>
            <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Active</option>
            <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inactive</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','brand','active']))
            <a href="{{ route('admin.products.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>Product</th><th>Brand</th><th>Variants</th><th>Total Stock</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($products as $product)
                @php $totalStock = $product->variants->sum(fn($v) => $v->inventory?->available_stock ?? 0); @endphp
                <tr>
                    <td><strong>{{ $product->product_name }}</strong></td>
                    <td>{{ $product->brand->brand_name ?? '—' }}</td>
                    <td>{{ $product->variants->count() }}</td>
                    <td>
                        @if($totalStock <= 0)<span class="kb-admin-pill kb-pill-red">0</span>
                        @elseif($totalStock <= 5)<span class="kb-admin-pill kb-pill-amber">{{ $totalStock }}</span>
                        @else {{ $totalStock }} @endif
                    </td>
                    <td><span class="kb-admin-pill {{ $product->is_active ? 'kb-pill-green' : 'kb-pill-grey' }}">{{ $product->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="kb-admin-actions">
                        <a href="{{ route('admin.products.edit', $product->product_id) }}" class="kb-admin-btn-sm">Edit</a>
                        <form method="POST" action="{{ route('admin.products.toggle', $product->product_id) }}" style="display:inline;">@csrf
                            <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-outline">{{ $product->is_active ? 'Deactivate' : 'Activate' }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="kb-admin-empty-row">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $products->links() }}</div>
</div>
@endsection
