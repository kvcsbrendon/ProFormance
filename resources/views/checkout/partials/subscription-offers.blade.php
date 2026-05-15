{{-- ============================================================
     checkout/partials/subscription-offers.blade.php
     Based on the WORKING old version — modals stay inline.
     Added: AJAX subscribe, IDs for JS updates, scripts always render.
     ============================================================ --}}
{{-- Hidden placeholders — always in DOM so JS can show them after AJAX --}}
@if(!isset($activeSubscription) || !$activeSubscription)
    <div id="checkout-sub-applied" class="kb-checkout-sub-applied" style="display:none;">
        <div class="kb-checkout-sub-badge">
            <i class="bi bi-star-fill"></i> <span id="sub-badge-name">{{ $plan->name ?? 'Subscriber' }}</span> Benefits
        </div>
        <div class="kb-checkout-total-row kb-checkout-savings-row" id="sub-free-shipping-row" style="display:none;">
            <span><i class="bi bi-truck"></i> Free standard shipping</span>
            <span class="kb-checkout-saving" id="sub-free-shipping-saving">-{{ $symbol }}0.00</span>
        </div>
        <div class="kb-checkout-total-row kb-checkout-savings-row" id="sub-discount-row" style="display:none;">
            <span><i class="bi bi-piggy-bank"></i> <span id="sub-discount-pct">{{ $plan->order_discount_percent ?? 0 }}</span>% member discount</span>
            <span class="kb-checkout-saving" id="sub-discount-amount">-{{ $symbol }}0.00</span>
        </div>
    </div>
@endif

@if(!isset($ssDiscountPenny) || $ssDiscountPenny <= 0)
    <div class="kb-checkout-total-row kb-checkout-savings-row" id="ss-discount-row" style="display:none;">
        <span><i class="bi bi-arrow-repeat"></i> Subscribe &amp; Save (<span id="ss-pct">{{ $ssDiscountPercent ?? 0 }}</span>%)</span>
        <span class="kb-checkout-saving" id="ss-discount-amount">-{{ $symbol }}0.00</span>
    </div>
@endif
{{-- SUBSCRIBER DISCOUNTS APPLIED --}}
@if(isset($activeSubscription) && $activeSubscription)
    @if($subFreeShipping || $subDiscountPenny > 0)
        <div id="checkout-sub-applied" class="kb-checkout-sub-applied">
            <div class="kb-checkout-sub-badge">
                <i class="bi bi-star-fill"></i> <span id="sub-badge-name">{{ $activeSubscription->plan->name ?? 'Subscriber' }}</span> Benefits
            </div>
            @if($subFreeShipping)
                <div class="kb-checkout-total-row kb-checkout-savings-row" id="sub-free-shipping-row">
                    <span><i class="bi bi-truck"></i> Free standard shipping</span>
                    <span class="kb-checkout-saving" id="sub-free-shipping-saving">-{{ $symbol }}{{ number_format(($originalShippingNetPenny ?? $shippingNetPenny ?? 0) / 100, 2) }}</span>
                </div>
            @endif
            @if($subDiscountPenny > 0)
                <div class="kb-checkout-total-row kb-checkout-savings-row" id="sub-discount-row">
                    <span><i class="bi bi-piggy-bank"></i> <span id="sub-discount-pct">{{ $activeSubscription->plan->order_discount_percent }}</span>% member discount</span>
                    <span class="kb-checkout-saving" id="sub-discount-amount">-{{ $symbol }}{{ number_format($subDiscountPenny / 100, 2) }}</span>
                </div>
            @endif
        </div>
    @endif
@endif

{{-- S&S DISCOUNTS APPLIED --}}
@if(isset($ssDiscountPenny) && $ssDiscountPenny > 0)
    <div class="kb-checkout-total-row kb-checkout-savings-row" id="ss-discount-row">
        <span><i class="bi bi-arrow-repeat"></i> Subscribe &amp; Save (<span id="ss-pct">{{ $ssDiscountPercent }}</span>%)</span>
        <span class="kb-checkout-saving" id="ss-discount-amount">-{{ $symbol }}{{ number_format($ssDiscountPenny / 100, 2) }}</span>
    </div>
@endif

{{-- NON-SUBSCRIBER SAVINGS PROMPT --}}
@if(!isset($activeSubscription) || !$activeSubscription)
    @if(isset($plan) && $plan && $plan->is_active && isset($potentialSavings) && $potentialSavings > 0)
        <div id="checkout-sub-promo" class="kb-checkout-sub-promo">
            <div class="kb-checkout-sub-promo-inner">
                <div class="kb-checkout-sub-promo-text">
                    <i class="bi bi-star"></i>
                    <span class="kb-checkout-promo-title">
                        Save <strong id="potential-savings-amount">{{ $symbol }}{{ number_format($potentialSavings / 100, 2) }}</strong> on this order with
                        <strong>{{ $plan->name }}</strong>
                    </span>
                </div>
                <p class="kb-checkout-sub-promo-details">
                    @if($plan->free_shipping)Free standard shipping @endif
                    @if($plan->free_shipping && $plan->order_discount_percent > 0) + @endif
                    @if($plan->order_discount_percent > 0){{ $plan->order_discount_percent }}% off every order @endif
                    for just {{ $symbol }}{{ number_format($plan->monthly_price_penny / 100, 2) }}/mo
                </p>
                <button type="button" class="kb-account-btn kb-account-btn-primary kb-checkout-sub-btn" onclick="document.getElementById('subscriptionModal').style.display='flex'">
                    <i class="bi bi-star"></i> Subscribe &amp; Save
                </button>
            </div>
        </div>
    @endif
@endif

{{-- S&S SETUP PROMPT (for items not yet subscribed) --}}
@if(isset($ssDiscountPercent) && $ssDiscountPercent > 0)
    @php
        $ssVariantIds = isset($ssActiveItems) ? $ssActiveItems->pluck('variant_id')->toArray() : [];
        $unsubscribedLines = collect($lines ?? [])->filter(function($line) use ($ssVariantIds) {
            return !in_array($line['variant_id'] ?? 0, $ssVariantIds);
        });
    @endphp

    @if($unsubscribedLines->isNotEmpty())
        <div id="checkout-ss-prompt" class="kb-checkout-ss-prompt">
            <p><i class="bi bi-arrow-repeat"></i> <strong>Subscribe &amp; Save {{ $ssDiscountPercent }}%</strong> — Set up regular delivery for your items and save on every order.</p>
            <button type="button" class="kb-account-btn kb-account-btn-small kb-account-btn-outline" onclick="document.getElementById('ssModal').style.display='flex'">
                Set Up Subscribe &amp; Save
            </button>
        </div>
    @endif
@endif

{{-- SUBSCRIPTION MODAL — now uses AJAX instead of form POST --}}
@if(!isset($activeSubscription) || !$activeSubscription)
    @if(isset($plan) && $plan && $plan->is_active)
        <div class="kb-modal-overlay" id="subscriptionModal" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
            <div class="kb-modal">
                <button class="kb-modal-close" onclick="this.closest('.kb-modal-overlay').style.display='none'">&times;</button>
                <div class="kb-modal-header">
                    <div class="kb-modal-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="bi bi-star-fill"></i></div>
                    <h3>{{ $plan->name }}</h3>
                    <p class="kb-modal-price">{{ $symbol }}{{ number_format($plan->monthly_price_penny / 100, 2) }}<span>/month</span></p>
                </div>
                <div class="kb-modal-body">
                    <div class="kb-modal-benefits">
                        @if($plan->free_shipping)
                            <div class="kb-modal-benefit"><i class="bi bi-check-circle-fill"></i> Free standard shipping on every order</div>
                        @endif
                        @if($plan->order_discount_percent > 0)
                            <div class="kb-modal-benefit"><i class="bi bi-check-circle-fill"></i> {{ $plan->order_discount_percent }}% off every order</div>
                        @endif
                        <div class="kb-modal-benefit"><i class="bi bi-check-circle-fill"></i> Cancel anytime</div>
                    </div>
                    @if($potentialSavings > 0)
                        <div class="kb-modal-savings-box">
                            You'd save <strong>{{ $symbol }}{{ number_format($potentialSavings / 100, 2) }}</strong> on this order alone!
                        </div>
                    @endif
                </div>
                <div class="kb-modal-footer">
            {{-- Card selection --}}
            @if(isset($savedCards) && $savedCards->count() > 0)
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Pay with</label>
                    @foreach($savedCards as $sc)
                        <label style="display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid var(--kb-button-border, #e5e7eb); border-radius: 8px; margin-bottom: 4px; cursor: pointer; font-size: 13px;">
                            <input type="radio" name="sub_card_id" value="{{ $sc->card_id }}"
                                {{ $sc->is_default ? 'checked' : '' }}
                                style="accent-color: #f59e0b;">
                            <i class="bi bi-credit-card"></i>
                            {{ ucfirst($sc->card_brand) }} •••• {{ $sc->last_four }}
                            <span style="color: var(--kb-secondary-font); font-size: 11px;">
                                Exp {{ $sc->expiry_display }}
                            </span>
                        </label>
                    @endforeach
                </div>
            @else
                <div style="margin-bottom: 12px; padding: 10px; background: #fef3c7; border-radius: 8px; font-size: 13px; color: #92400e;">
                    <i class="bi bi-exclamation-triangle"></i>
                    You need a saved card to subscribe.
                    <a href="{{ route('account.cards') }}" style="color: #d97706; text-decoration: underline;">Add a card</a>
                </div>
            @endif

            <button type="button" id="subscribe-btn" class="kb-account-btn kb-account-btn-primary"
                    style="width: 100%; padding: 12px;"
                    {{ (isset($savedCards) && $savedCards->count() > 0) ? '' : 'disabled' }}
                    onclick="subscribeAjax(this)">
                <i class="bi bi-star"></i> Subscribe for {{ $symbol }}{{ number_format($plan->monthly_price_penny / 100, 2) }}/mo
            </button>
            <div id="subscribe-error" style="color: var(--kb-red-500, red); font-size: 12px; margin-top: 6px; display: none;"></div>
            <button class="kb-account-btn kb-account-btn-outline" style="width: 100%; margin-top: 8px;"
                    onclick="this.closest('.kb-modal-overlay').style.display='none'">
                Not now
            </button>
        </div>
            </div>
        </div>
    @endif
@endif

{{-- SUBSCRIBE & SAVE MODAL --}}
@if(isset($ssDiscountPercent) && $ssDiscountPercent > 0 && isset($unsubscribedLines) && $unsubscribedLines->isNotEmpty())
    <div class="kb-modal-overlay" id="ssModal" style="display: none;" onclick="if(event.target===this)this.style.display='none'">
        <div class="kb-modal">
            <button class="kb-modal-close" onclick="this.closest('.kb-modal-overlay').style.display='none'">&times;</button>
            <div class="kb-modal-header">
                <div class="kb-modal-icon" style="background: linear-gradient(135deg, #16a34a, #15803d);"><i class="bi bi-arrow-repeat"></i></div>
                <h3>Subscribe &amp; Save {{ $ssDiscountPercent }}%</h3>
                <p style="font-size: 13px; color: var(--kb-secondary-font);">Get this quantity delivered regularly and save on every delivery.</p>
            </div>
            <div class="kb-modal-body">
                @foreach($unsubscribedLines as $line)
                    <div class="kb-ss-modal-item" id="ss-item-{{ $line['variant_id'] }}">
                        <div class="kb-ss-modal-item-check">
                            <input type="checkbox" class="ss-modal-check" data-variant="{{ $line['variant_id'] }}" id="ss-check-{{ $line['variant_id'] }}">
                        </div>
                        <div class="kb-ss-modal-item-info">
                            <label for="ss-check-{{ $line['variant_id'] }}" style="cursor:pointer;">
                                <strong>{{ $line['name'] ?? 'Product' }}</strong>
                            </label>
                            <span class="kb-ss-modal-item-price">{{ $symbol }}{{ number_format(($line['price'] ?? 0), 2) }} each</span>
                        </div>
                        <div class="kb-ss-modal-item-controls">
                            <select class="kb-form-input kb-ss-modal-qty" data-variant="{{ $line['variant_id'] }}" style="width: 60px;">
                                @for($i = 1; $i <= 20; $i++)
                                    <option value="{{ $i }}" {{ ($line['quantity'] ?? 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <select class="kb-form-input kb-ss-modal-freq" data-variant="{{ $line['variant_id'] }}" style="width: 120px;">
                                <option value="2">2 weeks</option>
                                <option value="4" selected>Monthly</option>
                                <option value="8">2 months</option>
                                <option value="12">3 months</option>
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="kb-modal-footer">
                <button type="button" class="kb-account-btn kb-account-btn-primary" style="width: 100%; padding: 12px;" onclick="submitSsItems(this)">
                    <i class="bi bi-arrow-repeat"></i> Set Up Subscribe &amp; Save
                </button>
                <button class="kb-account-btn kb-account-btn-outline" style="width: 100%; margin-top: 8px;" onclick="this.closest('.kb-modal-overlay').style.display='none'">
                    Not now
                </button>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════
     SCRIPTS — OUTSIDE all conditionals so they always render.
     This was the bug in the previous version.
     ═══════════════════════════════════════ --}}
<script>
function subscribeAjax(btn) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const errorEl = document.getElementById('subscribe-error');

    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Subscribing…';
    if (errorEl) errorEl.style.display = 'none';

    // Get selected card
    var selectedCard = document.querySelector('input[name="sub_card_id"]:checked');
    var bodyData = {};
    if (selectedCard) bodyData.saved_card_id = parseInt(selectedCard.value);

    fetch('{{ route("account.subscription.subscribe") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify(bodyData),
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            document.getElementById('subscriptionModal').style.display = 'none';
            const subApplied = document.getElementById('checkout-sub-applied');
            const subPromo   = document.getElementById('checkout-sub-promo');
            if (subApplied) subApplied.style.display = '';
            if (subPromo)   subPromo.style.display = 'none';
            if (typeof updateCheckoutTotals === 'function') {
                updateCheckoutTotals();
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-star"></i> Try Again';
            if (errorEl) {
                errorEl.textContent = data.message || 'Could not subscribe. Please try again.';
                errorEl.style.display = '';
            }
        }
    })
    .catch(err => {
        console.error('Subscribe error:', err);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-star"></i> Try Again';
        if (errorEl) {
            errorEl.textContent = 'Something went wrong. Please try again.';
            errorEl.style.display = '';
        }
    });
}

function submitSsItems(btn) {
    const checks = document.querySelectorAll('.ss-modal-check:checked');
    if (checks.length === 0) {
        alert('Please select at least one item.');
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let completed = 0;

    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving…'; }

    checks.forEach(cb => {
        const vid = cb.dataset.variant;
        const qty = document.querySelector('.kb-ss-modal-qty[data-variant="' + vid + '"]').value;
        const freq = document.querySelector('.kb-ss-modal-freq[data-variant="' + vid + '"]').value;

        fetch('{{ route("account.subscription.ss.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                variant_id: parseInt(vid),
                quantity: parseInt(qty),
                frequency_weeks: parseInt(freq),
            })
        })
        .then(r => r.json())
        .then(() => {
            completed++;
            if (completed === checks.length) {
                document.getElementById('ssModal').style.display = 'none';
                const ssPrompt = document.getElementById('checkout-ss-prompt');
                if (ssPrompt) ssPrompt.style.display = 'none';
                if (typeof updateCheckoutTotals === 'function') {
                    updateCheckoutTotals();
                }
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Set Up Subscribe & Save'; }
            }
        })
        .catch(err => {
            console.error('S&S save error:', err);
            completed++;
            if (completed === checks.length) {
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Set Up Subscribe & Save'; }
            }
        });
    });
}
</script>