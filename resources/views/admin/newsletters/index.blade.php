{{-- resources/views/admin/newsletters/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Newsletter Subscribers</h1>
    <p class="kb-admin-subtitle">
        <span class="kb-admin-pill kb-pill-green">{{ $totalSubscribed }} subscribed</span>
        <span class="kb-admin-pill kb-pill-grey">{{ $totalUnsubscribed }} unsubscribed</span>
    </p>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search email…" value="{{ request('search') }}">
        <select name="status" class="kb-form-input">
            <option value="">All</option>
            <option value="subscribed" {{ request('status')==='subscribed'?'selected':'' }}>Subscribed</option>
            <option value="unsubscribed" {{ request('status')==='unsubscribed'?'selected':'' }}>Unsubscribed</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('admin.newsletters.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>Email</th><th>Status</th><th>Subscribed At</th></tr></thead>
            <tbody>
                @forelse($subscribers as $s)
                <tr>
                    <td>{{ $s->email_address }}</td>
                    <td><span class="kb-admin-pill {{ $s->status === 'subscribed' ? 'kb-pill-green' : 'kb-pill-grey' }}">{{ ucfirst($s->status) }}</span></td>
                    <td>{{ $s->subscribed_at ? \Carbon\Carbon::parse($s->subscribed_at)->format('d M Y H:i') : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="kb-admin-empty-row">No subscribers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $subscribers->links() }}</div>
</div>
@endsection
