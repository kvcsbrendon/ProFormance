{{-- resources/views/admin/inventory/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Inventory</h1>
    <p class="kb-admin-subtitle">
        {{ $totalVariants }} variants tracked.
        <span class="kb-admin-pill kb-pill-red">{{ $outOfStock }} out of stock</span>
        <span class="kb-admin-pill kb-pill-amber">{{ $lowStock }} low stock</span>
    </p>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search product or SKU…" value="{{ request('search') }}">
        <select name="stock_filter" class="kb-form-input">
            <option value="">All Stock Levels</option>
            <option value="out" {{ request('stock_filter') === 'out' ? 'selected' : '' }}>Out of Stock</option>
            <option value="low" {{ request('stock_filter') === 'low' ? 'selected' : '' }}>Low Stock</option>
            <option value="ok" {{ request('stock_filter') === 'ok' ? 'selected' : '' }}>In Stock</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','stock_filter']))
            <a href="{{ route('admin.inventory.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant / SKU</th>
                    <th>Current Stock</th>
                    <th>Reorder Point</th>
                    <th>Status</th>
                    <th>Update Stock</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventory as $inv)
                <tr>
                    <td>{{ $inv->variant->product->product_name ?? '—' }}</td>
                    <td>
                        <span class="kb-admin-mono">{{ $inv->variant->sku ?? '—' }}</span>
                        @if($inv->variant->title)<br><small>{{ $inv->variant->title }}</small>@endif
                    </td>
                    <td><strong>{{ $inv->available_stock }}</strong></td>
                    <td>{{ $inv->reorder_point ?? '—' }}</td>
                    <td>
                        @if($inv->available_stock <= 0)
                            <span class="kb-admin-pill kb-pill-red">Out</span>
                        @elseif($inv->available_stock <= ($inv->reorder_point ?? 5))
                            <span class="kb-admin-pill kb-pill-amber">Low</span>
                        @else
                            <span class="kb-admin-pill kb-pill-green">OK</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.inventory.update', $inv->variant_id) }}" class="kb-admin-inline-form">
                            @csrf @method('PUT')
                            <input type="number" name="available_stock" value="{{ $inv->available_stock }}" min="0" class="kb-form-input kb-form-input-sm">
                            <input type="number" name="reorder_point" value="{{ $inv->reorder_point }}" min="0" class="kb-form-input kb-form-input-sm" placeholder="Reorder">
                            <button type="submit" class="kb-admin-btn-sm">Save</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="kb-admin-empty-row">No inventory records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $inventory->links() }}</div>
</div>
@endsection
