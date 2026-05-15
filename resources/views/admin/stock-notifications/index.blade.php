@extends('admin.layout')

@section('admin-content')
<div class="kb-admin-section">
    <div class="kb-admin-page-header">
        <div>
            <h1 class="kb-admin-title">Stock Notifications</h1>
            <p class="kb-admin-subtitle">
                <span class="kb-admin-pill kb-pill-amber">{{ $totalPending }} pending</span>
                <span class="kb-admin-pill kb-pill-green">{{ $totalNotified }} notified</span>
                <span class="kb-admin-pill kb-pill-grey">{{ $totalSubscribers }} total</span>
            </p>
        </div>
        
        {{-- Trigger emails modal button --}}
        <button type="button" class="kb-admin-btn" onclick="openTriggerModal()">
            <i class="bi bi-send"></i> Trigger Back-in-Stock Emails
        </button>
    </div>
</div>

<div class="kb-admin-filters">
    <form method="GET" class="kb-admin-filter-form">
        <input type="text" name="search" class="kb-form-input" placeholder="Search email, product, SKU…" value="{{ request('search') }}">
        
        <select name="product_id" class="kb-form-input" id="productFilter">
            <option value="">All Products</option>
            @foreach($products as $product)
                <option value="{{ $product->product_id }}" {{ request('product_id') == $product->product_id ? 'selected' : '' }}>
                    {{ $product->product_name }}
                </option>
            @endforeach
        </select>
        
        <select name="status" class="kb-form-input">
            <option value="">All Status</option>
            <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
            <option value="notified" {{ request('status')==='notified' ? 'selected' : '' }}>Notified</option>
        </select>
        
        <button type="submit" class="kb-admin-btn">Filter</button>
        
        @if(request()->hasAny(['search', 'status', 'product_id']))
            <a href="{{ route('admin.stock-notifications.index') }}" class="kb-admin-btn-outline">Clear</a>
        @endif
    </form>
</div>

{{-- Bulk actions --}}
<div class="kb-admin-bulk-actions" style="margin-bottom: 0.75rem; display: flex; gap: 0.5rem; align-items: center;">
    <div style="display: flex; align-items: center; gap: 0.25rem;">
        <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
        <label for="selectAll" style="font-size: var(--kb-font-size-sm);">Select All</label>
    </div>
    
    <button type="button" class="kb-admin-btn-sm-outline" onclick="bulkDelete()" style="color: var(--kb-red-500);">
        <i class="bi bi-trash"></i> Delete Selected
    </button>
    
    <span id="selectedCount" class="kb-admin-pill kb-pill-grey" style="display: none;">0 selected</span>
</div>

<div class="kb-admin-card">
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr>
                    <th style="width: 30px;"></th>
                    <th>Email / User</th>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>SKU</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Notified At</th>
                    <th style="width: 100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notifications as $n)
                <tr>
                    <td>
                        <input type="checkbox" class="row-checkbox" value="{{ $n->notification_id }}" onchange="updateSelectedCount()">
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $n->email }}</div>
                        @if($n->user)
                            <div style="font-size: 11px; color: var(--kb-secondary-font);">
                                <i class="bi bi-person"></i> {{ $n->user->first_name }} {{ $n->user->last_name }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($n->variant && $n->variant->product)
                            <a href="{{ route('admin.products.edit', $n->variant->product_id) }}" class="kb-admin-link" target="_blank">
                                {{ $n->variant->product->product_name }}
                            </a>
                        @else
                            <span class="kb-admin-muted">Product deleted</span>
                        @endif
                    </td>
                    <td>
                        @if($n->variant)
                            {{ $n->variant->title ?: '—' }}
                        @else
                            <span class="kb-admin-muted">Variant deleted</span>
                        @endif
                    </td>
                    <td>
                        <span class="kb-admin-mono">{{ $n->variant->sku ?? '—' }}</span>
                    </td>
                    <td>
                        @if($n->notified)
                            <span class="kb-admin-pill kb-pill-green">Notified</span>
                        @else
                            <span class="kb-admin-pill kb-pill-amber">Pending</span>
                        @endif
                    </td>
                    <td>
                        {{ $n->created_at ? \Carbon\Carbon::parse($n->created_at)->format('d M Y H:i') : '—' }}
                    </td>
                    <td>
                        {{ $n->notified_at ? \Carbon\Carbon::parse($n->notified_at)->format('d M Y H:i') : '—' }}
                    </td>
                    <td>
                        <div class="kb-admin-actions">
                            @if(!$n->notified)
                                <form method="POST" action="{{ route('admin.stock-notifications.mark-notified', $n->notification_id) }}" style="display: inline;" onsubmit="return confirm('Mark as notified without sending email?')">
                                    @csrf
                                    <button type="submit" class="kb-admin-btn-sm" title="Mark as notified">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                            @endif
                            
                            <a href="{{ route('admin.stock-notifications.show', $n->notification_id) }}" class="kb-admin-btn-sm-outline" title="View details">
                                <i class="bi bi-eye"></i>
                            </a>
                            
                            <form method="POST" action="{{ route('admin.stock-notifications.destroy', $n->notification_id) }}" style="display: inline;" onsubmit="return confirm('Delete this notification?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="kb-admin-btn-sm-outline" style="color: var(--kb-red-500);" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="kb-admin-empty-row">No stock notifications found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="kb-admin-pagination">
        {{ $notifications->links() }}
    </div>
</div>

{{-- Trigger Emails Modal --}}
<div id="triggerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: var(--kb-primary-bg); border-radius: 12px; padding: 1.5rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <h3 style="margin-top: 0; font-size: var(--kb-font-size-lg);">Trigger Back-in-Stock Emails</h3>
        <p class="kb-admin-muted">Select a product variant to send pending notifications.</p>
        
        <form method="POST" action="{{ route('admin.stock-notifications.trigger-emails') }}" id="triggerForm">
            @csrf
            
            <div class="kb-form-group">
                <label class="kb-form-label">Select Product</label>
                <select class="kb-form-input" id="modalProductSelect" required>
                    <option value="">— Choose Product —</option>
                    @foreach($products as $product)
                        <option value="{{ $product->product_id }}">{{ $product->product_name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="kb-form-group">
                <label class="kb-form-label">Select Variant</label>
                <select name="variant_id" class="kb-form-input" id="modalVariantSelect" required disabled>
                    <option value="">— First select a product —</option>
                </select>
                <div id="pendingCountInfo" class="kb-admin-muted" style="margin-top: 0.25rem;"></div>
            </div>
            
            <div class="kb-admin-form-actions">
                <button type="button" class="kb-admin-btn-outline" onclick="closeTriggerModal()">Cancel</button>
                <button type="submit" class="kb-admin-btn" id="triggerSubmitBtn" disabled>Send Emails</button>
            </div>
        </form>
    </div>
</div>


<style>
/* Additional styles for stock notifications */
.kb-admin-bulk-actions {
    background: var(--kb-primary-bg);
    border: 1px solid var(--kb-button-border);
    border-radius: 8px;
    padding: 0.5rem 1rem;
}

.kb-admin-bulk-actions input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: var(--kb-accent);
    margin-right: 0.25rem;
}

.row-checkbox {
    width: 16px;
    height: 16px;
    accent-color: var(--kb-accent);
}

.kb-admin-mono {
    font-family: 'Courier New', monospace;
    font-size: 12px;
}
</style>
@endsection