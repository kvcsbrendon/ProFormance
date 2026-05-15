{{-- resources/views/admin/refunds/show.blade.php --}}
@extends('admin.layout')
@section('admin-content')
<div class="kb-admin-section">
    <a href="{{ route('admin.refunds.index') }}" class="kb-admin-back-link">
        <i class="bi bi-arrow-left"></i> Back to Refunds
    </a>
    <h1 class="kb-admin-title">
        Refund #{{ $refund->refund_id }}
        @if($refund->refund_status === 'Pending')
            <span class="kb-admin-pill kb-pill-amber" style="font-size: 14px; vertical-align: middle;">Pending</span>
        @elseif($refund->refund_status === 'Succeeded')
            <span class="kb-admin-pill kb-pill-green" style="font-size: 14px; vertical-align: middle;">Approved</span>
        @else
            <span class="kb-admin-pill kb-pill-red" style="font-size: 14px; vertical-align: middle;">Rejected</span>
        @endif
    </h1>
    <p class="kb-admin-subtitle">Submitted {{ $refund->created_at->format('d M Y \a\t H:i') }}</p>
</div>

@if(session('success'))
    <div class="kb-admin-alert kb-admin-alert-success">
        <i class="bi bi-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="kb-admin-alert kb-admin-alert-error">
        @foreach($errors->all() as $error)
            <p><i class="bi bi-exclamation-circle"></i> {{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="kb-admin-row">
    {{-- LEFT: Refund Details --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title">Refund Details</h3>

        <div class="kb-refund-detail-grid">
            <div class="kb-refund-detail-item">
                <span class="kb-refund-detail-label">Requested Amount</span>
                <span class="kb-refund-detail-value">£{{ number_format($refund->amount_penny / 100, 2) }}</span>
            </div>
            <div class="kb-refund-detail-item">
                <span class="kb-refund-detail-label">Order Total</span>
                <span class="kb-refund-detail-value">£{{ number_format(($refund->order->total_penny ?? 0) / 100, 2) }}</span>
            </div>
            <div class="kb-refund-detail-item">
                <span class="kb-refund-detail-label">Status</span>
                <span class="kb-refund-detail-value">{{ $refund->refund_status }}</span>
            </div>
            <div class="kb-refund-detail-item">
                <span class="kb-refund-detail-label">Submitted</span>
                <span class="kb-refund-detail-value">{{ $refund->created_at->format('d M Y H:i') }}</span>
            </div>
            @if($refund->updated_at->ne($refund->created_at))
                <div class="kb-refund-detail-item">
                    <span class="kb-refund-detail-label">Last Updated</span>
                    <span class="kb-refund-detail-value">{{ $refund->updated_at->format('d M Y H:i') }}</span>
                </div>
            @endif
        </div>

        <div class="kb-refund-reason-box">
            <h4>Customer's Reason</h4>
            <p>{{ $refund->reason }}</p>
        </div>
    </div>

    {{-- RIGHT: Order & Customer --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title">Order & Customer</h3>

        @if($refund->order)
            <div class="kb-refund-detail-grid">
                <div class="kb-refund-detail-item">
                    <span class="kb-refund-detail-label">Order</span>
                    <span class="kb-refund-detail-value">
                        <a href="{{ route('admin.orders.show', $refund->order->order_id) }}" class="kb-admin-link">
                            {{ $refund->order->order_number }}
                        </a>
                    </span>
                </div>
                <div class="kb-refund-detail-item">
                    <span class="kb-refund-detail-label">Order Date</span>
                    <span class="kb-refund-detail-value">{{ $refund->order->created_at->format('d M Y') }}</span>
                </div>
                <div class="kb-refund-detail-item">
                    <span class="kb-refund-detail-label">Order Status</span>
                    <span class="kb-refund-detail-value">
                        <span class="kb-admin-pill kb-pill-{{ strtolower($refund->order->order_status) }}">{{ $refund->order->order_status }}</span>
                    </span>
                </div>
            </div>

            @if($customer)
                <div class="kb-refund-customer-box">
                    <div class="kb-refund-detail-item">
                        <span class="kb-refund-detail-label">Customer</span>
                        <span class="kb-refund-detail-value">
                            <a href="{{ route('admin.customers.show', $customer->user_id) }}" class="kb-admin-link">
                                {{ $customer->first_name }} {{ $customer->last_name }}
                            </a>
                        </span>
                    </div>
                    <div class="kb-refund-detail-item">
                        <span class="kb-refund-detail-label">Email</span>
                        <span class="kb-refund-detail-value">{{ $customer->loginDetail->email_address ?? '—' }}</span>
                    </div>
                </div>
            @endif

            {{-- Items Requested for Refund --}}
            @if($refund->items->isNotEmpty())
                <h4 style="margin-top: 16px; margin-bottom: 8px; font-size: 13px; font-weight: 600; color: #dc2626;">
                    <i class="bi bi-arrow-counterclockwise"></i> Items Requested for Refund
                </h4>
                <div class="kb-admin-table-wrapper">
                    <table class="kb-admin-table" style="font-size: 12px;">
                        <thead>
                            <tr><th>Item</th><th>Refund Qty</th><th>Of</th><th>Unit Price</th><th>Line Total</th></tr>
                        </thead>
                        <tbody>
                            @php $calcTotal = 0; @endphp
                            @foreach($refund->items as $ri)
                                @php
                                    $oi = $ri->orderItem;
                                    $unitInclVat = ($oi->unit_price_penny ?? 0) * (1 + ($oi->tax_rate ?? 0));
                                    $lineTotal = $unitInclVat * $ri->quantity;
                                    $calcTotal += $lineTotal;
                                @endphp
                                <tr style="background: #fef2f2;">
                                    <td>
                                        <strong>{{ $oi->title ?? 'Unknown' }}</strong>
                                        @if($oi && $oi->is_gift)<span class="kb-admin-pill kb-pill-amber" style="font-size: 9px; padding: 1px 5px;">Gift</span>@endif
                                    </td>
                                    <td><strong>{{ $ri->quantity }}</strong></td>
                                    <td>{{ $oi->quantity ?? '—' }}</td>
                                    <td>£{{ number_format(($oi->unit_price_penny ?? 0) / 100, 2) }}</td>
                                    <td><strong>£{{ number_format($lineTotal / 100, 2) }}</strong></td>
                                </tr>
                            @endforeach
                            <tr style="background: #fef2f2;">
                                <td colspan="4" style="text-align: right; font-weight: 600;">Calculated item total (incl. VAT)</td>
                                <td><strong>£{{ number_format($calcTotal / 100, 2) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- All Order Items --}}
            <h4 style="margin-top: 16px; margin-bottom: 8px; font-size: 13px; font-weight: 600;">All Items in Order</h4>
            <div class="kb-admin-table-wrapper">
                <table class="kb-admin-table" style="font-size: 12px;">
                    <thead>
                        <tr><th>Item</th><th>Qty</th><th>Price</th></tr>
                    </thead>
                    <tbody>
                        @foreach($refund->order->items as $item)
                            <tr>
                                <td>
                                    {{ $item->title }}
                                    @if($item->is_gift)<span class="kb-admin-pill kb-pill-amber" style="font-size: 9px; padding: 1px 5px;">Gift</span>@endif
                                </td>
                                <td>{{ $item->quantity }}</td>
                                <td>£{{ number_format($item->unit_price_penny / 100, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- ACTION SECTION --}}
@if($refund->refund_status === 'Pending')
<div class="kb-admin-row">
    {{-- Approve with adjustable amount --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title" style="color: #166534;"><i class="bi bi-check-circle"></i> Approve Refund</h3>

        <form method="POST" action="{{ route('admin.refunds.approve', $refund->refund_id) }}"
              onsubmit="return confirmApproval()">
            @csrf

            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-label">Refund Amount</label>
                <div class="kb-refund-type-options">
                    <label class="kb-radio">
                        <input type="radio" name="refund_type" value="full" checked onchange="toggleRefundInput(this)">
                        <span>Full refund — £{{ number_format(($refund->order->total_penny ?? 0) / 100, 2) }}</span>
                    </label>
                    <label class="kb-radio">
                        <input type="radio" name="refund_type" value="percentage" onchange="toggleRefundInput(this)">
                        <span>Percentage of order total</span>
                    </label>
                    <label class="kb-radio">
                        <input type="radio" name="refund_type" value="fixed" onchange="toggleRefundInput(this)">
                        <span>Fixed amount</span>
                    </label>
                </div>
            </div>

            <div class="kb-refund-value-row" id="refund-value-row" style="display: none; margin-bottom: 12px;">
                <div class="kb-form-group">
                    <label class="kb-form-label" id="refund-value-label">Value</label>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span id="refund-prefix" style="font-weight: 600;"></span>
                        <input type="number" name="refund_value" id="refund-value-input"
                               class="kb-form-input" step="0.01" min="0" style="max-width: 160px;">
                        <span id="refund-suffix" style="font-weight: 600;"></span>
                        <span id="refund-calc" class="kb-admin-muted" style="font-size: 12px;"></span>
                    </div>
                </div>
            </div>

            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-label">Note to customer <span class="kb-form-optional">(optional)</span></label>
                <textarea name="admin_note" class="kb-form-input" rows="2"
                          placeholder="This will be included in the notification sent to the customer…"
                          maxlength="1000"></textarea>
            </div>

            <button type="submit" class="kb-admin-btn" style="background: #16a34a; border-color: #16a34a;">
                <i class="bi bi-check-circle"></i> Approve Refund
            </button>
        </form>
    </div>

    {{-- Reject --}}
    <div class="kb-admin-card kb-admin-card-half">
        <h3 class="kb-admin-card-title" style="color: #991b1b;"><i class="bi bi-x-circle"></i> Reject Refund</h3>

        <form method="POST" action="{{ route('admin.refunds.reject', $refund->refund_id) }}"
              onsubmit="return confirm('Reject this refund request? The customer will be notified.')">
            @csrf

            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-label">Reason for rejection <span class="kb-form-optional">(optional — sent to customer)</span></label>
                <textarea name="admin_note" class="kb-form-input" rows="3"
                          placeholder="Explain why the refund was not approved…"
                          maxlength="1000"></textarea>
            </div>

            <button type="submit" class="kb-admin-btn" style="background: #dc2626; border-color: #dc2626;">
                <i class="bi bi-x-circle"></i> Reject Refund
            </button>
        </form>
    </div>
</div>
@endif

{{-- REPLY / MESSAGE SECTION --}}
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title"><i class="bi bi-chat-dots"></i> Send Message to Customer</h3>
    <p class="kb-admin-muted" style="margin-bottom: 12px;">This sends a message to the customer's Message Centre regarding this refund.</p>

    <form method="POST" action="{{ route('admin.refunds.reply', $refund->refund_id) }}">
        @csrf
        <div class="kb-form-group" style="margin-bottom: 12px;">
            <textarea name="message" class="kb-form-input" rows="4" required maxlength="2000"
                      placeholder="Type your message to the customer…"></textarea>
        </div>
        <button type="submit" class="kb-admin-btn">
            <i class="bi bi-send"></i> Send Message
        </button>
    </form>
</div>

{{-- Previous refunds for same order --}}
@if($otherRefunds->isNotEmpty())
<div class="kb-admin-card">
    <h3 class="kb-admin-card-title">Previous Refund Requests for This Order</h3>
    <div class="kb-admin-table-wrapper">
        <table class="kb-admin-table">
            <thead>
                <tr><th>ID</th><th>Amount</th><th>Status</th><th>Reason</th><th>Date</th></tr>
            </thead>
            <tbody>
                @foreach($otherRefunds as $prev)
                    <tr>
                        <td><a href="{{ route('admin.refunds.show', $prev->refund_id) }}" class="kb-admin-link">#{{ $prev->refund_id }}</a></td>
                        <td>£{{ number_format($prev->amount_penny / 100, 2) }}</td>
                        <td>
                            @if($prev->refund_status === 'Pending')
                                <span class="kb-admin-pill kb-pill-amber">Pending</span>
                            @elseif($prev->refund_status === 'Succeeded')
                                <span class="kb-admin-pill kb-pill-green">Approved</span>
                            @else
                                <span class="kb-admin-pill kb-pill-red">Rejected</span>
                            @endif
                        </td>
                        <td>{{ Str::limit($prev->reason, 60) }}</td>
                        <td>{{ $prev->created_at->format('d M Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<script>
const orderTotal = {{ $refund->order->total_penny ?? 0 }};

function toggleRefundInput(radio) {
    const row = document.getElementById('refund-value-row');
    const input = document.getElementById('refund-value-input');
    const prefix = document.getElementById('refund-prefix');
    const suffix = document.getElementById('refund-suffix');
    const label = document.getElementById('refund-value-label');
    const calc = document.getElementById('refund-calc');

    if (radio.value === 'full') {
        row.style.display = 'none';
        input.removeAttribute('required');
    } else {
        row.style.display = 'block';
        input.setAttribute('required', 'required');
        input.value = '';
        calc.textContent = '';

        if (radio.value === 'percentage') {
            prefix.textContent = '';
            suffix.textContent = '%';
            label.textContent = 'Percentage';
            input.max = 100;
            input.step = '1';
            input.placeholder = 'e.g. 50';
        } else {
            prefix.textContent = '£';
            suffix.textContent = '';
            label.textContent = 'Amount';
            input.max = (orderTotal / 100).toFixed(2);
            input.step = '0.01';
            input.placeholder = 'e.g. ' + (orderTotal / 200).toFixed(2);
        }
    }
}

document.getElementById('refund-value-input')?.addEventListener('input', function() {
    const type = document.querySelector('input[name="refund_type"]:checked')?.value;
    const calc = document.getElementById('refund-calc');
    const val = parseFloat(this.value) || 0;

    if (type === 'percentage') {
        const amount = (orderTotal * (Math.min(100, val) / 100)) / 100;
        calc.textContent = '= £' + amount.toFixed(2);
    } else if (type === 'fixed') {
        const capped = Math.min(val, orderTotal / 100);
        const pct = ((capped / (orderTotal / 100)) * 100).toFixed(0);
        calc.textContent = '(' + pct + '% of order)';
    }
});

function confirmApproval() {
    const type = document.querySelector('input[name="refund_type"]:checked')?.value;
    const input = document.getElementById('refund-value-input');
    let amount;

    if (type === 'full') {
        amount = '£' + (orderTotal / 100).toFixed(2) + ' (full refund)';
    } else if (type === 'percentage') {
        const pct = parseFloat(input.value) || 0;
        const val = (orderTotal * (pct / 100)) / 100;
        amount = '£' + val.toFixed(2) + ' (' + pct + '%)';
    } else {
        amount = '£' + (parseFloat(input.value) || 0).toFixed(2);
    }

    return confirm('Approve refund of ' + amount + '?\n\nThe customer will be notified and the order marked as Refunded.');
}
</script>

<style>
    .kb-admin-back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--kb-secondary-font, #6b7280); text-decoration: none; font-size: 13px; margin-bottom: 8px; }
    .kb-admin-back-link:hover { color: var(--kb-accent); }
    .kb-admin-row { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
    .kb-admin-card-half { flex: 1; min-width: 300px; }
    .kb-refund-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; margin-bottom: 16px; }
    .kb-refund-detail-item { display: flex; flex-direction: column; gap: 2px; }
    .kb-refund-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--kb-secondary-font, #6b7280); font-weight: 600; }
    .kb-refund-detail-value { font-size: 14px; color: var(--kb-primary-font, #111827); }
    .kb-refund-reason-box { background: var(--kb-grey-50, #f9fafb); border: 1px solid var(--kb-button-border, #e5e7eb); border-radius: 8px; padding: 14px; }
    .kb-refund-reason-box h4 { font-size: 12px; font-weight: 600; color: var(--kb-secondary-font, #6b7280); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
    .kb-refund-reason-box p { font-size: 14px; line-height: 1.6; color: var(--kb-primary-font, #111827); white-space: pre-wrap; }
    .kb-refund-customer-box { margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--kb-button-border, #e5e7eb); display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
    .kb-refund-type-options { display: flex; flex-direction: column; gap: 6px; }
    .kb-admin-alert { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
    .kb-admin-alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .kb-admin-alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .kb-admin-link { color: var(--kb-accent); text-decoration: none; font-weight: 600; }
    .kb-admin-link:hover { text-decoration: underline; }
    .kb-pill-red { background: #fee2e2; color: #991b1b; }
</style>
@endsection
