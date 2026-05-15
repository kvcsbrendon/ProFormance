{{-- resources/views/admin/discounts/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <div class="kb-admin-page-header">
        <div>
            <h1 class="kb-admin-title">Discounts</h1>
            <p class="kb-admin-subtitle">Manage discount codes and promotions.</p>
        </div>
        <a href="{{ route('admin.discounts.create') }}" class="kb-admin-btn"><i class="bi bi-plus-circle"></i> Create Discount</a>
    </div>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search code…" value="{{ request('search') }}">
        <select name="active" class="kb-form-input">
            <option value="">All</option>
            <option value="1" {{ request('active')==='1'?'selected':'' }}>Active</option>
            <option value="0" {{ request('active')==='0'?'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','active']))
            <a href="{{ route('admin.discounts.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Min Spend</th><th>Dates</th><th>Limits</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($discounts as $d)
                <tr>
                    <td class="kb-admin-mono"><strong>{{ $d->discount_code }}</strong></td>
                    <td>{{ $d->discoun_type === 'percentage' ? 'Percentage' : 'Fixed' }}</td>
                    <td>{{ $d->discoun_type === 'percentage' ? $d->discount_value.'%' : '£'.number_format($d->discount_value,2) }}</td>
                    <td>{{ $d->min_subtotal_penny ? '£'.number_format($d->min_subtotal_penny/100,2) : '—' }}</td>
                    <td>{{ $d->starts_at?->format('d/m/y') ?? '—' }} — {{ $d->ends_at?->format('d/m/y') ?? '∞' }}</td>
                    <td>{{ $d->usage_limit ?? '∞' }} total / {{ $d->per_user_limit ?? '∞' }} per user</td>
                    <td><span class="kb-admin-pill {{ $d->is_active ? 'kb-pill-green' : 'kb-pill-grey' }}">{{ $d->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="kb-admin-actions">
                        <a href="{{ route('admin.discounts.edit', $d->discount_id) }}" class="kb-admin-btn-sm">Edit</a>
                        <form method="POST" action="{{ route('admin.discounts.toggle', $d->discount_id) }}" style="display:inline;">@csrf
                            <button type="submit" class="kb-admin-btn-sm kb-admin-btn-sm-outline">{{ $d->is_active ? 'Disable' : 'Enable' }}</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="kb-admin-empty-row">No discounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $discounts->links() }}</div>
</div>
@endsection
