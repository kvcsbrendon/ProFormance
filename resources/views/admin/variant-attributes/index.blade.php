@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Variant Attributes &amp; Options</h1>
    <p class="kb-admin-subtitle">Manage variant types (Size, Colour, etc.) and their selectable values.</p>
</div>

{{-- Add New Attribute --}}
<div class="kb-admin-card" style="margin-bottom: 1.5rem;">
    <h3 class="kb-admin-card-title"><i class="bi bi-plus-circle"></i> Add Attribute</h3>
    <form method="POST" action="{{ route('admin.variant-attributes.store') }}">
        @csrf
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">System Name *</label>
                <input type="text" name="attribute_name" class="kb-form-input" required maxlength="50" value="{{ old('attribute_name') }}" placeholder="e.g. material (lowercase, no spaces)">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Display Name *</label>
                <input type="text" name="display_name" class="kb-form-input" required maxlength="100" value="{{ old('display_name') }}" placeholder="e.g. Material">
            </div>
            <div class="kb-form-group" style="display:flex; align-items:flex-end; padding-bottom:1rem;">
                <button type="submit" class="kb-admin-btn"><i class="bi bi-plus"></i> Add Attribute</button>
            </div>
        </div>
    </form>
</div>

{{-- Existing Attributes --}}
@foreach($attributes as $attr)
<div class="kb-admin-card kb-admin-attr-card" style="margin-bottom: 1rem;">
    {{-- Attribute Header --}}
    <div class="kb-admin-attr-header" onclick="this.parentElement.classList.toggle('kb-attr-collapsed')" style="cursor:pointer;">
        <div style="display:flex; align-items:center; gap:10px;">
            <h3 class="kb-admin-card-title" style="margin:0;">{{ $attr->display_name }}</h3>
            <span class="kb-admin-pill {{ $attr->is_active ? 'kb-pill-green' : 'kb-pill-grey' }}">{{ $attr->is_active ? 'Active' : 'Inactive' }}</span>
            <span class="kb-admin-muted" style="font-size:12px;">{{ $attr->options->count() }} option(s) · Order: {{ $attr->selection_order }}</span>
        </div>
        <i class="bi bi-chevron-down kb-admin-collapse-icon"></i>
    </div>

    <div class="kb-admin-attr-body">
        {{-- Edit Attribute --}}
        <form method="POST" action="{{ route('admin.variant-attributes.update', $attr->attribute_id) }}" class="kb-admin-attr-edit-row">
            @csrf @method('PUT')
            <div class="kb-form-row" style="margin-bottom:0.5rem;">
                <div class="kb-form-group" style="margin-bottom:0;">
                    <label class="kb-form-label">Display Name</label>
                    <input type="text" name="display_name" class="kb-form-input" value="{{ $attr->display_name }}" required>
                </div>
                <div class="kb-form-group" style="margin-bottom:0;">
                    <label class="kb-form-label">Sort Order</label>
                    <input type="number" name="selection_order" class="kb-form-input" value="{{ $attr->selection_order }}" min="0" style="max-width:80px;">
                </div>
                <div class="kb-form-group" style="margin-bottom:0; display:flex; align-items:flex-end; gap:6px; padding-bottom:0;">
                    <label class="kb-checkbox-label"><input type="checkbox" name="is_active" value="1" {{ $attr->is_active ? 'checked' : '' }}> Active</label>
                    <button type="submit" class="kb-admin-btn-sm">Save</button>
                </div>
            </div>
        </form>

        {{-- Options Table --}}
        <div class="kb-admin-table-wrapper" style="margin-top:0.75rem;">
            <table class="kb-admin-table" style="font-size:13px;">
                <thead>
                    <tr><th>Display Value</th><th>Sort Order</th><th>Active</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($attr->options as $opt)
                    <tr>
                        <form method="POST" action="{{ route('admin.variant-attributes.options.update', $opt->option_id) }}">
                            @csrf @method('PUT')
                            <td>
                                <input type="text" name="display_value" class="kb-form-input" value="{{ $opt->display_value }}" required style="min-width:120px;">
                            </td>
                            <td>
                                <input type="number" name="sort_order" class="kb-form-input" value="{{ $opt->sort_order }}" min="0" style="width:60px;">
                            </td>
                            <td>
                                <label class="kb-checkbox-label"><input type="checkbox" name="is_active" value="1" {{ $opt->is_active ? 'checked' : '' }}></label>
                            </td>
                            <td style="white-space:nowrap; display:flex; gap:4px;">
                                <button type="submit" class="kb-admin-btn-sm" title="Save">Save</button>
                        </form>
                                <form method="POST" action="{{ route('admin.variant-attributes.options.destroy', $opt->option_id) }}" onsubmit="return confirm('Delete option {{ $opt->display_value }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                    </tr>
                    @endforeach

                    {{-- Add option row --}}
                    <tr class="kb-admin-add-option-row">
                        <form method="POST" action="{{ route('admin.variant-attributes.options.store', $attr->attribute_id) }}">
                            @csrf
                            <td>
                                <input type="text" name="display_value" class="kb-form-input" required placeholder="e.g. XXL" style="min-width:120px;">
                            </td>
                            <td colspan="2"></td>
                            <td>
                                <button type="submit" class="kb-admin-btn-sm" style="background:var(--kb-green-500, #22c55e); border-color:var(--kb-green-600, #16a34a); color:#fff;">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            </td>
                        </form>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Delete Attribute --}}
        <div style="margin-top:0.75rem; padding-top:0.75rem; border-top:1px solid var(--kb-button-border);">
            <form method="POST" action="{{ route('admin.variant-attributes.destroy', $attr->attribute_id) }}" onsubmit="return confirm('Delete attribute {{ $attr->display_name }} and ALL its options? This cannot be undone.')">
                @csrf @method('DELETE')
                <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-danger"><i class="bi bi-trash"></i> Delete Attribute</button>
                <span class="kb-admin-muted" style="font-size:11px; margin-left:8px;">Only works if no options are assigned to variants.</span>
            </form>
        </div>
    </div>
</div>
@endforeach

@if($attributes->isEmpty())
<div class="kb-admin-card">
    <p class="kb-admin-muted" style="text-align:center; padding:2rem;">No variant attributes yet. Create one above to get started.</p>
</div>
@endif

<style>
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-admin-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .kb-admin-alert-error p { margin: 0; }

    .kb-admin-attr-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--kb-button-border);
        margin-bottom: 0.75rem;
    }
    .kb-admin-collapse-icon { transition: transform 0.2s; color: var(--kb-secondary-font); }
    .kb-attr-collapsed .kb-admin-attr-body { display: none; }
    .kb-attr-collapsed .kb-admin-collapse-icon { transform: rotate(-90deg); }

    .kb-admin-add-option-row { background: var(--kb-grey-50, #f9fafb); }
    .kb-admin-add-option-row td { border-top: 2px dashed var(--kb-button-border); }
</style>
@endsection