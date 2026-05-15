{{-- resources/views/account/order-detail.blade.php --}}
@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <a href="{{ route('account.orders') }}" class="kb-account-back-link">
        <i class="bi bi-arrow-left"></i> Back to Orders
    </a>
    <h1 class="kb-account-title">Order {{ $order->order_number }}</h1>
    <p class="kb-account-subtitle">
        Placed on {{ $order->created_at->format('d M Y \a\t H:i') }}
        &nbsp;·&nbsp;
        <span class="kb-order-status kb-order-status-{{ strtolower($order->order_status) }}">
            {{ $order->order_status }}
        </span>
        @if($hasGiftItems)
            &nbsp;·&nbsp;
            <span class="kb-order-gift-badge">
                <i class="bi bi-gift"></i>
                {{ $isPureGift ? 'Gift Order' : 'Contains Gift Items' }}
            </span>
        @endif
        @if($order->is_subscribe_save)
            &nbsp;·&nbsp;
            <span class="kb-order-ss-badge">
                <i class="bi bi-arrow-repeat"></i> Subscribe &amp; Save
            </span>
        @endif
    </p>
</div>

{{-- ORDER STATUS TIMELINE --}}
@php
    $trackingStatuses = ['Paid', 'Processing', 'Shipped', 'Delivered'];
    $currentIdx = array_search($order->order_status, $trackingStatuses);
    $isCancelled = in_array($order->order_status, ['Cancelled', 'Refunded']);
@endphp
@if(!$isCancelled && $currentIdx !== false)
<div class="kb-status-timeline">
    @foreach($trackingStatuses as $idx => $step)
        @php
            $isComplete = $idx <= $currentIdx;
            $isCurrent = $idx === $currentIdx;
        @endphp
        <div class="kb-timeline-step {{ $isComplete ? 'kb-timeline-complete' : '' }} {{ $isCurrent ? 'kb-timeline-current' : '' }}">
            <div class="kb-timeline-dot">
                @if($isComplete && !$isCurrent)
                    <i class="bi bi-check"></i>
                @else
                    <span class="kb-tracker-number">{{ $idx + 1 }}</span>
                @endif
            </div>
            <span class="kb-timeline-label">{{ $step }}</span>
        </div>
        @if(!$loop->last)
            <div class="kb-timeline-line {{ $idx < $currentIdx ? 'kb-timeline-line-complete' : '' }}"></div>
        @endif
    @endforeach
</div>
@elseif($isCancelled)
<div class="kb-status-cancelled-banner">
    <i class="bi bi-{{ $order->order_status === 'Cancelled' ? 'x-circle' : 'arrow-counterclockwise' }}"></i>
    This order has been {{ strtolower($order->order_status) }}.
</div>
@endif

{{-- ORDER ITEMS --}}
<div class="kb-account-card">
    <h2 class="kb-account-card-title">Items Ordered</h2>
    <div class="kb-account-table-wrapper">
        <table class="kb-account-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    @if($isMixedOrder)
                        <th>Delivery</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->title }}</td>
                        <td><code>{{ $item->sku }}</code></td>
                        <td>£{{ number_format($item->unit_price_penny / 100, 2) }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>£{{ number_format(($item->unit_price_penny * $item->quantity) / 100, 2) }}</td>
                        @if($isMixedOrder)
                            <td>
                                @if($item->is_gift)
                                    <span class="kb-badge-gift-sm"><i class="bi bi-gift"></i> Gift</span>
                                @else
                                    <span class="kb-badge-standard-sm">Your address</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
        <a href="{{ route('account.orders.invoice', $order->order_id) }}"
           class="kb-account-btn kb-account-btn-outline">
            <i class="bi bi-download"></i> Download Invoice
        </a>
        <form method="POST" action="{{ route('account.orders.reorder', $order->order_id) }}" style="display:inline;">
            @csrf
            <button type="submit" class="kb-account-btn kb-account-btn-outline">
                <i class="bi bi-arrow-repeat"></i> Buy Again
            </button>
        </form>
    </div>
</div>

{{-- ORDER SUMMARY + SHIPPING --}}
<div class="kb-account-grid-2">
    <div class="kb-account-card">
        <h2 class="kb-account-card-title">Order Summary</h2>
        <div class="kb-order-summary">
            <div class="kb-summary-row">
                <span>Subtotal (excl. VAT)</span>
                <span>£{{ number_format($order->subtotal_penny / 100, 2) }}</span>
            </div>
            @if($order->discount_penny > 0)
                <div class="kb-summary-row kb-summary-discount">
                    <span>Discount</span>
                    <span>-£{{ number_format($order->discount_penny / 100, 2) }}</span>
                </div>
            @endif
            <div class="kb-summary-row">
                <span>Shipping</span>
                <span>£{{ number_format($order->shipping_penny / 100, 2) }}</span>
            </div>
            <div class="kb-summary-row">
                <span>VAT</span>
                <span>£{{ number_format($order->tax_penny / 100, 2) }}</span>
            </div>
            <div class="kb-summary-row kb-summary-total">
                <strong>Total</strong>
                <strong>£{{ number_format($order->total_penny / 100, 2) }}</strong>
            </div>
        </div>
    </div>

    <div class="kb-account-card">
        <h2 class="kb-account-card-title">Shipping</h2>

        @if($isPureGift)
            <div class="kb-address-display">
                <p><strong><i class="bi bi-gift"></i> Gift Delivery</strong></p>
                <p>All items delivered to recipient's address</p>
                <p>{{ $order->ship_country_code }}</p>
                <p class="kb-form-hint" style="margin-top: 8px;">
                    Recipient's full address is hidden for privacy.
                </p>
            </div>
        @elseif($isMixedOrder)
            <div class="kb-address-display" style="margin-bottom: 14px;">
                <p class="kb-form-label" style="margin-bottom: 6px;">Your items</p>
                <p>{{ $order->ship_recipient_name }}</p>
                <p>{{ $order->ship_house_number }} {{ $order->ship_address_line_one }}</p>
                @if($order->ship_address_line_two)
                    <p>{{ $order->ship_address_line_two }}</p>
                @endif
                <p>{{ $order->ship_city }}{{ $order->ship_county ? ', ' . $order->ship_county : '' }}</p>
                <p>{{ $order->ship_postcode }}</p>
                @if($order->ship_phone_number)
                    <p><i class="bi bi-telephone"></i> {{ $order->ship_phone_number }}</p>
                @endif
            </div>
            <div class="kb-address-display kb-address-gift-note">
                <p><strong><i class="bi bi-gift"></i> Gift items ({{ $giftItems->count() }})</strong></p>
                <p>Delivered directly to recipient</p>
                <p class="kb-form-hint">Recipient's address is hidden for privacy.</p>
            </div>
        @else
            <div class="kb-address-display">
                <p>{{ $order->ship_recipient_name }}</p>
                <p>{{ $order->ship_house_number }} {{ $order->ship_address_line_one }}</p>
                @if($order->ship_address_line_two)
                    <p>{{ $order->ship_address_line_two }}</p>
                @endif
                <p>{{ $order->ship_city }}{{ $order->ship_county ? ', ' . $order->ship_county : '' }}</p>
                <p>{{ $order->ship_postcode }}</p>
                @if($order->ship_phone_number)
                    <p><i class="bi bi-telephone"></i> {{ $order->ship_phone_number }}</p>
                @endif
            </div>
        @endif
    </div>
</div>

{{-- REFUND SECTION --}}
<div class="kb-account-card">
    <h2 class="kb-account-card-title"><i class="bi bi-arrow-counterclockwise"></i> Refund</h2>

    {{-- Refund History --}}
    @if($allRefunds->isNotEmpty())
        @foreach($allRefunds as $refund)
            <div class="kb-refund-history-entry">
                <div class="kb-refund-status-badge kb-refund-status-{{ strtolower($refund->refund_status) }}">
                    @if($refund->refund_status === 'Pending')
                        <i class="bi bi-clock"></i> Refund Request Pending
                    @elseif($refund->refund_status === 'Succeeded')
                        <i class="bi bi-check-circle"></i> Refund Approved
                    @elseif($refund->refund_status === 'Rejected')
                        <i class="bi bi-x-circle"></i> Refund Rejected
                    @endif
                </div>

                <div class="kb-refund-details">
                    <p><strong>Amount:</strong> £{{ number_format($refund->amount_penny / 100, 2) }}</p>
                    <p><strong>Submitted:</strong> {{ $refund->created_at->format('d M Y \a\t H:i') }}</p>
                    <p><strong>Reason:</strong> {{ $refund->reason }}</p>
                </div>

                @if($refund->items->isNotEmpty())
                    <div class="kb-refund-items-summary">
                        <p class="kb-form-label" style="margin-bottom: 6px;">Items requested</p>
                        @foreach($refund->items as $ri)
                            <div class="kb-refund-item-row">
                                <span>{{ $ri->orderItem->title ?? 'Unknown item' }}</span>
                                <span class="kb-refund-item-qty">x{{ $ri->quantity }}</span>
                                <span class="kb-refund-item-price">
                                    £{{ number_format((($ri->orderItem->unit_price_penny ?? 0) * $ri->quantity * (1 + ($ri->orderItem->tax_rate ?? 0))) / 100, 2) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($refund->refund_status === 'Pending')
                    <p class="kb-form-hint" style="margin-top: 10px;">
                        We're reviewing your request. You'll receive a message once a decision has been made.
                    </p>
                @elseif($refund->refund_status === 'Succeeded')
                    <p class="kb-form-hint" style="margin-top: 10px;">
                        Your refund has been processed. Please allow 5–10 business days for it to appear on your statement.
                    </p>
                @endif
            </div>
        @endforeach
    @endif

    {{-- New Refund Request Form --}}
    @if($canRequestRefund)
        @if($allRefunds->isNotEmpty())
            <hr style="margin: 16px 0; border: none; border-top: 1px solid var(--kb-button-border);">
            <p style="margin-bottom: 12px; font-size: 13px;">
                @if($allRefunds->where('refund_status', 'Rejected')->isNotEmpty() && $allRefunds->where('refund_status', 'Succeeded')->isEmpty())
                    Your previous request was rejected. You can submit a new one if needed.
                @else
                    You have remaining items that can be refunded.
                @endif
            </p>
        @endif

        <form method="POST" action="{{ route('account.orders.refund', $order->order_id) }}" id="refund-form">
            @csrf

            {{-- Item Selection --}}
            <div class="kb-form-group" style="margin-bottom: 16px;">
                <label class="kb-form-label">Which items would you like to refund?</label>
                <div class="kb-refund-item-picker">
                    @foreach($order->items as $item)
                        @php
                            $alreadyRefunded = $refundedQty[$item->order_item_id] ?? 0;
                            $maxRefundable = $item->quantity - $alreadyRefunded;
                            $unitInclVat = $item->unit_price_penny * (1 + ($item->tax_rate ?? 0));
                        @endphp

                        <div class="kb-refund-pick-row {{ $maxRefundable <= 0 ? 'kb-refund-pick-disabled' : '' }}"
                             id="pick-row-{{ $item->order_item_id }}">
                            <label class="kb-refund-pick-check">
                                <input type="checkbox"
                                       class="refund-item-check"
                                       data-item-id="{{ $item->order_item_id }}"
                                       data-unit-price="{{ $unitInclVat }}"
                                       data-max-qty="{{ $maxRefundable }}"
                                       {{ $maxRefundable <= 0 ? 'disabled' : '' }}
                                       onchange="toggleRefundItem(this)">
                            </label>

                            <div class="kb-refund-pick-info">
                                <span class="kb-refund-pick-title">{{ $item->title }}</span>
                                <span class="kb-refund-pick-meta">
                                    £{{ number_format($item->unit_price_penny / 100, 2) }} each
                                    · Ordered {{ $item->quantity }}
                                    @if($alreadyRefunded > 0)
                                        · <span style="color: #16a34a;">{{ $alreadyRefunded }} already refunded</span>
                                    @endif
                                    @if($item->is_gift)
                                        · <i class="bi bi-gift"></i> Gift
                                    @endif
                                </span>
                            </div>

                            <div class="kb-refund-pick-qty">
                                @if($maxRefundable > 0)
                                    <select name=""
                                            class="kb-form-input kb-refund-qty-select"
                                            id="qty-{{ $item->order_item_id }}"
                                            disabled
                                            onchange="updateRefundTotal()">
                                        @for($i = 1; $i <= $maxRefundable; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>
                                    <span class="kb-refund-pick-of">of {{ $maxRefundable }} remaining</span>
                                @else
                                    <span class="kb-refund-pick-refunded">
                                        <i class="bi bi-check-circle"></i> Fully refunded
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Estimated refund total --}}
            <div class="kb-refund-total-box" id="refund-total-box" style="display: none;">
                <div class="kb-refund-total-row">
                    <span>Estimated refund</span>
                    <strong id="refund-total-amount">£0.00</strong>
                </div>
                <p class="kb-form-hint" style="margin: 4px 0 0;">
                    Final amount is determined by our team during review.
                </p>
            </div>

            {{-- Reason --}}
            <div class="kb-form-group" style="margin-bottom: 12px;">
                <label class="kb-form-label">Why would you like a refund?</label>
                <textarea name="reason" class="kb-form-input" rows="4"
                          placeholder="Please describe the reason for your refund request..."
                          required maxlength="1000">{{ old('reason') }}</textarea>
            </div>

            <div class="kb-refund-info-box">
                <i class="bi bi-info-circle"></i>
                <div>
                    <p>Refund requests are typically reviewed within 2–3 business days.</p>
                </div>
            </div>

            <button type="submit" class="kb-account-btn kb-account-btn-outline"
                    id="refund-submit-btn" disabled
                    onclick="return confirm('Submit refund request for the selected items?')">
                <i class="bi bi-arrow-counterclockwise"></i> Submit Refund Request
            </button>
        </form>

    @elseif($hasPendingRefund)
        {{-- Don't show form, pending is already being reviewed --}}

    @elseif(!$hasRefundableItems && $allRefunds->isNotEmpty())
        <p class="kb-form-hint" style="margin-top: 10px;">
            All items in this order have been refunded.
        </p>

    @elseif($allRefunds->isEmpty())
        <p class="kb-form-hint">
            @if(in_array($order->order_status, ['Cancelled']))
                This order has been cancelled.
            @else
                Refund requests are not available for orders with status "{{ $order->order_status }}".
            @endif
        </p>
    @endif
</div>

<script>
function toggleRefundItem(checkbox) {
    const itemId = checkbox.dataset.itemId;
    const qtySelect = document.getElementById('qty-' + itemId);
    const row = document.getElementById('pick-row-' + itemId);

    if (checkbox.checked) {
        qtySelect.disabled = false;
        qtySelect.name = 'refund_items[' + itemId + ']';
        row.classList.add('kb-refund-pick-selected');
    } else {
        qtySelect.disabled = true;
        qtySelect.name = '';
        row.classList.remove('kb-refund-pick-selected');
    }

    updateRefundTotal();
}

function updateRefundTotal() {
    const checkboxes = document.querySelectorAll('.refund-item-check:checked');
    let totalPenny = 0;
    let hasItems = false;

    checkboxes.forEach(cb => {
        const itemId = cb.dataset.itemId;
        const unitPrice = parseFloat(cb.dataset.unitPrice);
        const qtySelect = document.getElementById('qty-' + itemId);
        const qty = parseInt(qtySelect.value) || 1;

        totalPenny += unitPrice * qty;
        hasItems = true;
    });

    const totalBox = document.getElementById('refund-total-box');
    const totalAmount = document.getElementById('refund-total-amount');
    const submitBtn = document.getElementById('refund-submit-btn');

    if (hasItems) {
        totalBox.style.display = 'block';
        totalAmount.textContent = '£' + (totalPenny / 100).toFixed(2);
        submitBtn.disabled = false;
    } else {
        totalBox.style.display = 'none';
        submitBtn.disabled = true;
    }
}
</script>

@endsection
