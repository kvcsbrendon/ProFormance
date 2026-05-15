@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Brands</h1>
    <p class="kb-admin-subtitle">Manage product brands. {{ $brands->count() }} brand(s) total.</p>
</div>

{{-- Add Brand --}}
<div class="kb-admin-card" style="margin-bottom: 1.5rem;">
    <h3 class="kb-admin-card-title"><i class="bi bi-plus-circle"></i> Add Brand</h3>
    <form method="POST" action="{{ route('admin.brands.store') }}">
        @csrf
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Brand Name *</label>
                <input type="text" name="brand_name" class="kb-form-input" required maxlength="100" value="{{ old('brand_name') }}" placeholder="e.g. IronForge Fitness">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Description</label>
                <input type="text" name="brand_description" class="kb-form-input" maxlength="500" value="{{ old('brand_description') }}" placeholder="Optional">
            </div>
            <div class="kb-form-group" style="display:flex; align-items:flex-end; gap:0.5rem; padding-bottom:1rem;">
                <label class="kb-checkbox-label"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button type="submit" class="kb-admin-btn"><i class="bi bi-plus"></i> Add</button>
            </div>
        </div>
    </form>
</div>

{{-- Brands List --}}
<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr><th>Brand</th><th>Slug</th><th>Products</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($brands as $brand)
                <tr>
                    <form method="POST" action="{{ route('admin.brands.update', $brand->brand_id) }}">
                        @csrf @method('PUT')
                        <td>
                            <input type="text" name="brand_name" class="kb-form-input" value="{{ $brand->brand_name }}" required style="min-width:160px;">
                        </td>
                        <td class="kb-admin-mono" style="font-size:12px;">{{ $brand->slug }}</td>
                        <td>{{ $brand->products_count }}</td>
                        <td>
                            <label class="kb-checkbox-label"><input type="checkbox" name="is_active" value="1" {{ $brand->is_active ? 'checked' : '' }}> Active</label>
                        </td>
                        <td style="white-space:nowrap; display:flex; gap:4px;">
                            <button type="submit" class="kb-admin-btn-sm" title="Save">Save</button>
                    </form>
                            <form method="POST" action="{{ route('admin.brands.destroy', $brand->brand_id) }}" onsubmit="return confirm('Delete {{ $brand->brand_name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<style>
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-admin-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .kb-admin-alert-error p { margin: 0; }
</style>
@endsection