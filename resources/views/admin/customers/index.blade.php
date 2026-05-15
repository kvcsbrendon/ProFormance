{{-- resources/views/admin/customers/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Customers</h1>
    <p class="kb-admin-subtitle">View and manage customer accounts.</p>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search name or email…" value="{{ request('search') }}">
        <button type="submit" class="kb-admin-btn">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.customers.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                <tr>
                    <td><strong>{{ $c->first_name }} {{ $c->last_name }}</strong></td>
                    <td>{{ $c->loginDetail->email_address ?? '—' }}</td>
                    <td><span class="kb-admin-pill kb-pill-grey">{{ ucfirst($c->user_role) }}</span></td>
                    <td>{{ $c->orders_count ?? 0 }}</td>
                    <td>&pound;{{ number_format(($c->total_spent ?? 0) / 100, 2) }}</td>
                    <td>
                        <span class="kb-admin-pill {{ $c->is_active ? 'kb-pill-green' : 'kb-pill-red' }}">
                            {{ $c->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('admin.customers.show', $c->user_id) }}" class="kb-admin-btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="kb-admin-empty-row">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $customers->links() }}</div>
</div>
@endsection
