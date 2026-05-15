{{-- resources/views/admin/messages/create.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <h1 class="kb-admin-title">Send Message</h1>
    <p class="kb-admin-subtitle">Send a notification to one or all customers via the Message Centre.</p>
</div>

<form method="POST" action="{{ route('admin.messages.store') }}">
    @csrf
    <div class="kb-admin-card" style="max-width:700px;">
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Recipient *</label>
                <select name="recipient" class="kb-form-input" id="recipient-select" onchange="document.getElementById('user-select').style.display = this.value === 'single' ? 'block' : 'none';">
                    <option value="single">Single Customer</option>
                    <option value="all">All Active Customers</option>
                </select>
            </div>
            <div class="kb-form-group" id="user-select">
                <label class="kb-form-label">Customer *</label>
                <select name="user_id" class="kb-form-input">
                    <option value="">Select customer…</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->user_id }}">{{ $c->first_name }} {{ $c->last_name }} ({{ $c->loginDetail->email_address ?? '—' }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">Category *</label>
            <select name="category" class="kb-form-input">
                <option value="order">Order</option>
                <option value="security">Security</option>
                <option value="promotional" selected>Promotional</option>
                <option value="system">System</option>
            </select>
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">Title *</label>
            <input type="text" name="title" class="kb-form-input" required maxlength="200" value="{{ old('title') }}" placeholder="e.g. Flash Sale — 20% off everything!">
        </div>
        <div class="kb-form-group">
            <label class="kb-form-label">Message *</label>
            <textarea name="body" class="kb-form-input kb-form-textarea" rows="5" required maxlength="2000" placeholder="Write your message here…">{{ old('body') }}</textarea>
        </div>
        <div class="kb-form-row">
            <div class="kb-form-group">
                <label class="kb-form-label">Link URL <span class="kb-form-optional">(optional)</span></label>
                <input type="text" name="link_url" class="kb-form-input" value="{{ old('link_url') }}" placeholder="/products">
            </div>
            <div class="kb-form-group">
                <label class="kb-form-label">Link Label</label>
                <input type="text" name="link_label" class="kb-form-input" value="{{ old('link_label') }}" placeholder="e.g. Shop Now">
            </div>
        </div>
    </div>
    <div class="kb-admin-form-actions">
        <button type="submit" class="kb-admin-btn"><i class="bi bi-send"></i> Send Message</button>
    </div>
</form>
@endsection
