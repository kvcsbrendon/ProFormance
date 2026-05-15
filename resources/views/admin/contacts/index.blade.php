{{-- resources/views/admin/contacts/index.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Support Tickets</h1>
    <p class="kb-admin-subtitle">Customer enquiries and support requests.</p>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search name, email, message…" value="{{ request('search') }}">
        <select name="status" class="kb-form-input">
            <option value="">All Status</option>
            <option value="Pending" {{ request('status')==='Pending'?'selected':'' }}>Pending</option>
            <option value="Solved" {{ request('status')==='Solved'?'selected':'' }}>Solved</option>
        </select>
        <select name="subject" class="kb-form-input">
            <option value="">All Subjects</option>
            <option value="General" {{ request('subject')==='General'?'selected':'' }}>General</option>
            <option value="Support" {{ request('subject')==='Support'?'selected':'' }}>Support</option>
            <option value="Feedback" {{ request('subject')==='Feedback'?'selected':'' }}>Feedback</option>
        </select>
        <button type="submit" class="kb-admin-btn">Filter</button>
        @if(request()->hasAny(['search','status','subject']))
            <a href="{{ route('admin.contacts.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Status</th><th>Preview</th><th></th></tr></thead>
            <tbody>
                @forelse($contacts as $c)
                <tr>
                    <td>{{ $c->query_id }}</td>
                    <td>{{ $c->first_name }} {{ $c->last_name }}</td>
                    <td>{{ $c->email_address }}</td>
                    <td><span class="kb-admin-pill kb-pill-grey">{{ $c->subject_select }}</span></td>
                    <td><span class="kb-admin-pill {{ $c->contact_status === 'Pending' ? 'kb-pill-amber' : 'kb-pill-green' }}">{{ $c->contact_status }}</span></td>
                    <td class="kb-admin-muted">{{ Str::limit($c->message_description, 50) }}</td>
                    <td><a href="{{ route('admin.contacts.show', $c->query_id) }}" class="kb-admin-btn-sm">View</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="kb-admin-empty-row">No tickets found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="kb-admin-pagination">{{ $contacts->links() }}</div>
</div>
@endsection
