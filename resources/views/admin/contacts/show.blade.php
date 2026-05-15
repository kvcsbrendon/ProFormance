{{-- resources/views/admin/contacts/show.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.contacts.index') }}" class="kb-admin-back"><i class="bi bi-arrow-left"></i> Back to Tickets</a>
    <div class="kb-admin-page-header">
        <div>
            <h1 class="kb-admin-title">Ticket #{{ $contact->query_id }}</h1>
            <p class="kb-admin-subtitle">{{ $contact->first_name }} {{ $contact->last_name }} — {{ $contact->email_address }}</p>
        </div>
        <span class="kb-admin-pill kb-admin-pill-lg {{ $contact->contact_status === 'Pending' ? 'kb-pill-amber' : 'kb-pill-green' }}">{{ $contact->contact_status }}</span>
    </div>
</div>

<div class="kb-admin-row">
    <div class="kb-admin-card kb-admin-card-wide">
        <h3 class="kb-admin-card-title">Message</h3>
        <div class="kb-admin-ticket-message">
            {{ $contact->message_description }}
        </div>
    </div>

    <div class="kb-admin-card-sidebar-stack">
        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Details</h3>
            <p><strong>Subject:</strong> {{ $contact->subject_select }}</p>
            <p><strong>Email:</strong> {{ $contact->email_address }}</p>
            @if($contact->order_id)<p><strong>Order:</strong> <a href="{{ route('admin.orders.show', $contact->order_id) }}" class="kb-admin-link">#{{ $contact->order_id }}</a></p>@endif
            @if($contact->product_id)<p><strong>Product ID:</strong> {{ $contact->product_id }}</p>@endif
            @if($contact->product_search)<p><strong>Product Search:</strong> {{ $contact->product_search }}</p>@endif
        </div>

        <div class="kb-admin-card">
            <h3 class="kb-admin-card-title">Update Status</h3>
            <form method="POST" action="{{ route('admin.contacts.updateStatus', $contact->query_id) }}">
                @csrf
                <select name="contact_status" class="kb-form-input" style="margin-bottom:0.75rem;">
                    <option value="Pending" {{ $contact->contact_status === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="Solved" {{ $contact->contact_status === 'Solved' ? 'selected' : '' }}>Solved</option>
                </select>
                <button type="submit" class="kb-admin-btn" style="width:100%;">Update</button>
            </form>
        </div>

        <div class="kb-admin-card">
            <a href="mailto:{{ $contact->email_address }}?subject=Re: {{ $contact->subject_select }} (Ticket #{{ $contact->query_id }})" class="kb-admin-btn-outline" style="width:100%;text-align:center;justify-content:center;">
                <i class="bi bi-envelope"></i> Reply via Email
            </a>
        </div>
    </div>
</div>
@endsection
