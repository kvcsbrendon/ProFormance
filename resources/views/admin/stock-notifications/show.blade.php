@extends('admin.layout')

@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.stock-notifications.index') }}" class="kb-admin-back">
        <i class="bi bi-arrow-left"></i> Back to Notifications
    </a>
    
    <div class="kb-admin-page-header">
        <h1 class="kb-admin-title">Stock Notification Details</h1>
        
        <div class="kb-admin-actions">
            @if(!$notification->notified)
                <form method="POST" action="{{ route('admin.stock-notifications.mark-notified', $notification->notification_id) }}" style="display: inline;" onsubmit="return confirm('Mark as notified?')">
                    @csrf
                    <button type="submit" class="kb-admin-btn">
                        <i class="bi bi-check-circle"></i> Mark as Notified
                    </button>
                </form>
            @endif
            
            <form method="POST" action="{{ route('admin.stock-notifications.destroy', $notification->notification_id) }}" style="display: inline;" onsubmit="return confirm('Delete this notification?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="kb-admin-btn-outline" style="color: var(--kb-red-500);">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="kb-admin-row">
    {{-- Left Column: Customer Info --}}
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title"><i class="bi bi-person"></i> Customer Information</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0.5rem 0; color: var(--kb-secondary-font); width: 120px;">Email:</td>
                <td style="padding: 0.5rem 0; font-weight: 600;">{{ $notification->email }}</td>
            </tr>
            @if($notification->user)
            <tr>
                <td style="padding: 0.5rem 0;">User:</td>
                <td style="padding: 0.5rem 0;">
                    {{-- Check if users.edit route exists --}}
                    @if(Route::has('admin.users.edit'))
                        <a href="{{ route('admin.users.edit', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @elseif(Route::has('admin.customers.edit'))
                        <a href="{{ route('admin.customers.edit', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @elseif(Route::has('admin.user.show'))
                        <a href="{{ route('admin.user.show', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @else
                        <span style="font-weight: 600;">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </span>
                    @endif
                    <span class="kb-admin-muted" style="display: block;">ID: {{ $notification->user_id }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>
    
    {{-- Right Column: Notification Status --}}
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title"><i class="bi bi-bell"></i> Notification Status</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0.5rem 0; width: 120px;">Status:</td>
                <td style="padding: 0.5rem 0;">
                    @if($notification->notified)
                        <span class="kb-admin-pill kb-pill-green">Notified</span>
                    @else
                        <span class="kb-admin-pill kb-pill-amber">Pending</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 0;">Requested:</td>
                <td style="padding: 0.5rem 0;">
                    {{ $notification->created_at ? \Carbon\Carbon::parse($notification->created_at)->format('d M Y H:i:s') : '—' }}
                </td>
            </tr>
            @if($notification->notified_at)
            <tr>
                <td style="padding: 0.5rem 0;">Notified At:</td>
                <td style="padding: 0.5rem 0;">
                    {{ \Carbon\Carbon::parse($notification->notified_at)->format('d M Y H:i:s') }}
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

{{-- Product Information --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-box"></i> Product Information</h3>
    
    @if($notification->variant && $notification->variant->product)
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div>
                <div class="kb-admin-muted">Product</div>
                <div style="font-weight: 600;">
                    {{-- Check if product edit route exists --}}
                    @if(Route::has('admin.products.edit'))
                        <a href="{{ route('admin.products.edit', $notification->variant->product_id) }}" class="kb-admin-link">
                            {{ $notification->variant->product->product_name }}
                        </a>
                    @else
                        {{ $notification->variant->product->product_name }}
                    @endif
                </div>
            </div>
            
            <div>
                <div class="kb-admin-muted">Variant</div>
                <div style="font-weight: 600;">{{ $notification->variant->title ?: '—' }}</div>
            </div>
            
            <div>
                <div class="kb-admin-muted">SKU</div>
                <div><span class="kb-admin-mono">{{ $notification->variant->sku }}</span></div>
            </div>
            
            <div>
                <div class="kb-admin-muted">Current Stock</div>
                <div>
                    <span class="kb-admin-pill {{ $notification->variant->stock_quantity > 0 ? 'kb-pill-green' : 'kb-pill-red' }}">
                        {{ $notification->variant->stock_quantity }} units
                    </span>
                </div>
            </div>

        </div>
        
        @if($notification->variant->image)
            <div style="margin-top: 1rem;">
                <img src="{{ asset('storage/' . $notification->variant->image) }}" 
                     alt="{{ $notification->variant->title }}"
                     style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid var(--kb-button-border);">
            </div>
        @endif
    @else
        <p class="kb-admin-muted">Product or variant no longer exists.</p>
    @endif
</div>

{{-- Other subscribers for same product --}}
@if($notification->variant)
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title">
        <i class="bi bi-people"></i> Other Subscribers for Same Variant
    </h3>
    
    @php
        $others = \App\Models\StockNotification::where('variant_id', $notification->variant_id)
            ->where('notification_id', '!=', $notification->notification_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    @endphp
    
    @if($others->count() > 0)
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($others as $other)
                    <tr>
                        <td>{{ $other->email }}</td>
                        <td>
                            @if($other->notified)
                                <span class="kb-admin-pill kb-pill-green">Notified</span>
                            @else
                                <span class="kb-admin-pill kb-pill-amber">Pending</span>
                            @endif
                        </td>
                        <td>{{ $other->created_at ? \Carbon\Carbon::parse($other->created_at)->format('d M Y H:i') : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.stock-notifications.show', $other->notification_id) }}" class="kb-admin-btn-sm-outline">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="kb-admin-muted">No other subscribers for this variant.</p>
    @endif
</div>
@endif
@endsection@extends('admin.layout')

@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.stock-notifications.index') }}" class="kb-admin-back">
        <i class="bi bi-arrow-left"></i> Back to Notifications
    </a>
    
    <div class="kb-admin-page-header">
        <h1 class="kb-admin-title">Stock Notification Details</h1>
        
        <div class="kb-admin-actions">
            @if(!$notification->notified)
                <form method="POST" action="{{ route('admin.stock-notifications.mark-notified', $notification->notification_id) }}" style="display: inline;" onsubmit="return confirm('Mark as notified?')">
                    @csrf
                    <button type="submit" class="kb-admin-btn">
                        <i class="bi bi-check-circle"></i> Mark as Notified
                    </button>
                </form>
            @endif
            
            <form method="POST" action="{{ route('admin.stock-notifications.destroy', $notification->notification_id) }}" style="display: inline;" onsubmit="return confirm('Delete this notification?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="kb-admin-btn-outline" style="color: var(--kb-red-500);">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="kb-admin-row">
    {{-- Left Column: Customer Info --}}
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title"><i class="bi bi-person"></i> Customer Information</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0.5rem 0; color: var(--kb-secondary-font); width: 120px;">Email:</td>
                <td style="padding: 0.5rem 0; font-weight: 600;">{{ $notification->email }}</td>
            </tr>
            @if($notification->user)
            <tr>
                <td style="padding: 0.5rem 0;">User:</td>
                <td style="padding: 0.5rem 0;">
                    {{-- Check if users.edit route exists --}}
                    @if(Route::has('admin.users.edit'))
                        <a href="{{ route('admin.users.edit', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @elseif(Route::has('admin.customers.edit'))
                        <a href="{{ route('admin.customers.edit', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @elseif(Route::has('admin.user.show'))
                        <a href="{{ route('admin.user.show', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @else
                        <span style="font-weight: 600;">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </span>
                    @endif
                    <span class="kb-admin-muted" style="display: block;">ID: {{ $notification->user_id }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>
    
    {{-- Right Column: Notification Status --}}
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title"><i class="bi bi-bell"></i> Notification Status</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0.5rem 0; width: 120px;">Status:</td>
                <td style="padding: 0.5rem 0;">
                    @if($notification->notified)
                        <span class="kb-admin-pill kb-pill-green">Notified</span>
                    @else
                        <span class="kb-admin-pill kb-pill-amber">Pending</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 0;">Requested:</td>
                <td style="padding: 0.5rem 0;">
                    {{ $notification->created_at ? \Carbon\Carbon::parse($notification->created_at)->format('d M Y H:i:s') : '—' }}
                </td>
            </tr>
            @if($notification->notified_at)
            <tr>
                <td style="padding: 0.5rem 0;">Notified At:</td>
                <td style="padding: 0.5rem 0;">
                    {{ \Carbon\Carbon::parse($notification->notified_at)->format('d M Y H:i:s') }}
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

{{-- Product Information --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-box"></i> Product Information</h3>
    
    @if($notification->variant && $notification->variant->product)
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div>
                <div class="kb-admin-muted">Product</div>
                <div style="font-weight: 600;">
                    {{-- Check if product edit route exists --}}
                    @if(Route::has('admin.products.edit'))
                        <a href="{{ route('admin.products.edit', $notification->variant->product_id) }}" class="kb-admin-link">
                            {{ $notification->variant->product->product_name }}
                        </a>
                    @else
                        {{ $notification->variant->product->product_name }}
                    @endif
                </div>
            </div>
            
            <div>
                <div class="kb-admin-muted">Variant</div>
                <div style="font-weight: 600;">{{ $notification->variant->title ?: '—' }}</div>
            </div>
            
            <div>
                <div class="kb-admin-muted">SKU</div>
                <div><span class="kb-admin-mono">{{ $notification->variant->sku }}</span></div>
            </div>
            
            <div>
                <div class="kb-admin-muted">Current Stock</div>
                <div>
                    <span class="kb-admin-pill {{ $notification->variant->stock_quantity > 0 ? 'kb-pill-green' : 'kb-pill-red' }}">
                        {{ $notification->variant->stock_quantity }} units
                    </span>
                </div>
            </div>

        </div>
        
        @if($notification->variant->image)
            <div style="margin-top: 1rem;">
                <img src="{{ asset('storage/' . $notification->variant->image) }}" 
                     alt="{{ $notification->variant->title }}"
                     style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid var(--kb-button-border);">
            </div>
        @endif
    @else
        <p class="kb-admin-muted">Product or variant no longer exists.</p>
    @endif
</div>

{{-- Other subscribers for same product --}}
@if($notification->variant)
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title">
        <i class="bi bi-people"></i> Other Subscribers for Same Variant
    </h3>
    
    @php
        $others = \App\Models\StockNotification::where('variant_id', $notification->variant_id)
            ->where('notification_id', '!=', $notification->notification_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    @endphp
    
    @if($others->count() > 0)
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($others as $other)
                    <tr>
                        <td>{{ $other->email }}</td>
                        <td>
                            @if($other->notified)
                                <span class="kb-admin-pill kb-pill-green">Notified</span>
                            @else
                                <span class="kb-admin-pill kb-pill-amber">Pending</span>
                            @endif
                        </td>
                        <td>{{ $other->created_at ? \Carbon\Carbon::parse($other->created_at)->format('d M Y H:i') : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.stock-notifications.show', $other->notification_id) }}" class="kb-admin-btn-sm-outline">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="kb-admin-muted">No other subscribers for this variant.</p>
    @endif
</div>
@endif
@endsection@extends('admin.layout')

@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.stock-notifications.index') }}" class="kb-admin-back">
        <i class="bi bi-arrow-left"></i> Back to Notifications
    </a>
    
    <div class="kb-admin-page-header">
        <h1 class="kb-admin-title">Stock Notification Details</h1>
        
        <div class="kb-admin-actions">
            @if(!$notification->notified)
                <form method="POST" action="{{ route('admin.stock-notifications.mark-notified', $notification->notification_id) }}" style="display: inline;" onsubmit="return confirm('Mark as notified?')">
                    @csrf
                    <button type="submit" class="kb-admin-btn">
                        <i class="bi bi-check-circle"></i> Mark as Notified
                    </button>
                </form>
            @endif
            
            <form method="POST" action="{{ route('admin.stock-notifications.destroy', $notification->notification_id) }}" style="display: inline;" onsubmit="return confirm('Delete this notification?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="kb-admin-btn-outline" style="color: var(--kb-red-500);">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<div class="kb-admin-row">
    {{-- Left Column: Customer Info --}}
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title"><i class="bi bi-person"></i> Customer Information</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0.5rem 0; color: var(--kb-secondary-font); width: 120px;">Email:</td>
                <td style="padding: 0.5rem 0; font-weight: 600;">{{ $notification->email }}</td>
            </tr>
            @if($notification->user)
            <tr>
                <td style="padding: 0.5rem 0;">User:</td>
                <td style="padding: 0.5rem 0;">
                    {{-- Check if users.edit route exists --}}
                    @if(Route::has('admin.users.edit'))
                        <a href="{{ route('admin.users.edit', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @elseif(Route::has('admin.customers.edit'))
                        <a href="{{ route('admin.customers.edit', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @elseif(Route::has('admin.user.show'))
                        <a href="{{ route('admin.user.show', $notification->user_id) }}" class="kb-admin-link">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </a>
                    @else
                        <span style="font-weight: 600;">
                            {{ $notification->user->first_name }} {{ $notification->user->last_name }}
                        </span>
                    @endif
                    <span class="kb-admin-muted" style="display: block;">ID: {{ $notification->user_id }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>
    
    {{-- Right Column: Notification Status --}}
    <div class="kb-admin-card">
        <h3 class="kb-admin-card-title"><i class="bi bi-bell"></i> Notification Status</h3>
        
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 0.5rem 0; width: 120px;">Status:</td>
                <td style="padding: 0.5rem 0;">
                    @if($notification->notified)
                        <span class="kb-admin-pill kb-pill-green">Notified</span>
                    @else
                        <span class="kb-admin-pill kb-pill-amber">Pending</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding: 0.5rem 0;">Requested:</td>
                <td style="padding: 0.5rem 0;">
                    {{ $notification->created_at ? \Carbon\Carbon::parse($notification->created_at)->format('d M Y H:i:s') : '—' }}
                </td>
            </tr>
            @if($notification->notified_at)
            <tr>
                <td style="padding: 0.5rem 0;">Notified At:</td>
                <td style="padding: 0.5rem 0;">
                    {{ \Carbon\Carbon::parse($notification->notified_at)->format('d M Y H:i:s') }}
                </td>
            </tr>
            @endif
        </table>
    </div>
</div>

{{-- Product Information --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-box"></i> Product Information</h3>
    
    @if($notification->variant && $notification->variant->product)
        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
            <div>
                <div class="kb-admin-muted">Product</div>
                <div style="font-weight: 600;">
                    {{-- Check if product edit route exists --}}
                    @if(Route::has('admin.products.edit'))
                        <a href="{{ route('admin.products.edit', $notification->variant->product_id) }}" class="kb-admin-link">
                            {{ $notification->variant->product->product_name }}
                        </a>
                    @else
                        {{ $notification->variant->product->product_name }}
                    @endif
                </div>
            </div>
            
            <div>
                <div class="kb-admin-muted">Variant</div>
                <div style="font-weight: 600;">{{ $notification->variant->title ?: '—' }}</div>
            </div>
            
            <div>
                <div class="kb-admin-muted">SKU</div>
                <div><span class="kb-admin-mono">{{ $notification->variant->sku }}</span></div>
            </div>
            
            <div>
                <div class="kb-admin-muted">Current Stock</div>
                <div>
                    <span class="kb-admin-pill {{ $notification->variant->stock_quantity > 0 ? 'kb-pill-green' : 'kb-pill-red' }}">
                        {{ $notification->variant->stock_quantity }} units
                    </span>
                </div>
            </div>

        </div>
        
        @if($notification->variant->image)
            <div style="margin-top: 1rem;">
                <img src="{{ asset('storage/' . $notification->variant->image) }}" 
                     alt="{{ $notification->variant->title }}"
                     style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid var(--kb-button-border);">
            </div>
        @endif
    @else
        <p class="kb-admin-muted">Product or variant no longer exists.</p>
    @endif
</div>

{{-- Other subscribers for same product --}}
@if($notification->variant)
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title">
        <i class="bi bi-people"></i> Other Subscribers for Same Variant
    </h3>
    
    @php
        $others = \App\Models\StockNotification::where('variant_id', $notification->variant_id)
            ->where('notification_id', '!=', $notification->notification_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    @endphp
    
    @if($others->count() > 0)
        <div class="kb-admin-table-wrapper">
            <table class="kb-admin-table">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($others as $other)
                    <tr>
                        <td>{{ $other->email }}</td>
                        <td>
                            @if($other->notified)
                                <span class="kb-admin-pill kb-pill-green">Notified</span>
                            @else
                                <span class="kb-admin-pill kb-pill-amber">Pending</span>
                            @endif
                        </td>
                        <td>{{ $other->created_at ? \Carbon\Carbon::parse($other->created_at)->format('d M Y H:i') : '—' }}</td>
                        <td>
                            <a href="{{ route('admin.stock-notifications.show', $other->notification_id) }}" class="kb-admin-btn-sm-outline">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="kb-admin-muted">No other subscribers for this variant.</p>
    @endif
</div>
@endif
@endsection