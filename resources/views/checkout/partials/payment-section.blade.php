{{-- checkout/partials/payment-section.blade.php --}}
<div class="kb-checkout-section">
    <div class="kb-checkout-section-header">
        <span class="kb-checkout-step-num">3</span>
        <h2 class="kb-checkout-section-title">Payment</h2>
        <a href="{{ route('test.cards') }}" target="_blank" class="kb-pay-test-link" title="View test card numbers">
            <i class="bi bi-info-circle"></i>
        </a>
    </div>

    {{-- Payment method tabs --}}
    <div class="kb-pay-tabs">
        <label class="kb-pay-tab">
            <input type="radio" name="payment_method" value="card" checked onchange="handlePaymentMethodChange()">
            <div class="kb-pay-tab-inner">
                <div class="kb-pay-tab-icons">
                    <i class="bi bi-credit-card-2-front"></i>
                </div>
                <span class="kb-pay-tab-label">Card</span>
            </div>
        </label>
        <label class="kb-pay-tab">
            <input type="radio" name="payment_method" value="paypal" onchange="handlePaymentMethodChange()">
            <div class="kb-pay-tab-inner">
                <div class="kb-pay-tab-icons">
                    <x-icon name="paypal" />
                </div>
                <span class="kb-pay-tab-label">PayPal</span>
            </div>
        </label>
    </div>
    @error('payment_method') <div class="kb-form-error">{{ $message }}</div> @enderror

    {{-- Card payment area --}}
    <div id="card-payment-area" class="kb-pay-card-area">

        {{-- Saved cards --}}
        @if(isset($savedCards) && $savedCards->count() > 0)
            <div class="kb-pay-saved">
                <p class="kb-pay-saved-title">
                    <i class="bi bi-wallet2"></i> Saved Cards
                </p>
                <div class="kb-pay-saved-list">
                    @foreach($savedCards as $sc)
                        <label class="kb-pay-saved-card {{ $sc->is_default ? 'kb-pay-saved-card-default' : '' }}">
                            <input type="radio" name="saved_card_id" value="{{ $sc->card_id }}"
                                {{ $sc->is_default ? 'checked' : '' }}
                                onchange="handleCardChoice(true)">
                            <div class="kb-pay-saved-card-body">
                                <div class="kb-pay-saved-card-top">
                                    <span class="kb-pay-saved-card-brand">
                                        @if($sc->card_brand === 'visa')
                                            <i class="bi bi-credit-card-2-front"></i>
                                        @elseif($sc->card_brand === 'mastercard')
                                            <i class="bi bi-credit-card"></i>
                                        @elseif($sc->card_brand === 'amex')
                                            <i class="bi bi-credit-card-fill"></i>
                                        @else
                                            <i class="bi bi-credit-card"></i>
                                        @endif
                                        {{ ucfirst($sc->card_brand) }}
                                    </span>
                                    @if($sc->is_default)
                                        <span class="kb-pay-saved-card-badge">Default</span>
                                    @endif
                                </div>
                                <span class="kb-pay-saved-card-num">•••• •••• •••• {{ $sc->last_four }}</span>
                                <span class="kb-pay-saved-card-exp">Expires {{ $sc->expiry_display }}</span>
                            </div>
                            <div class="kb-pay-saved-card-check">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                        </label>
                    @endforeach

                    <label class="kb-pay-saved-card kb-pay-saved-card-new">
                        <input type="radio" name="saved_card_id" value="" onchange="handleCardChoice(false)">
                        <div class="kb-pay-saved-card-body">
                            <i class="bi bi-plus-circle"></i>
                            <span>Use a new card</span>
                        </div>
                    </label>
                </div>
            </div>
        @endif

        {{-- New card form --}}
        <div id="new-card-fields" class="kb-pay-new-card">
            <div class="kb-pay-card-form">
                <div class="kb-form-group">
                    <label class="kb-form-label required">Name on Card</label>
                    <input type="text" name="card_name" class="kb-form-input kb-card-input"
                        value="{{ old('card_name', $user->first_name . ' ' . $user->last_name) }}"
                        placeholder="John Smith">
                    @error('card_name') <div class="kb-form-error">{{ $message }}</div> @enderror
                </div>

                <div class="kb-form-group">
                    <label class="kb-form-label required">Card Number</label>
                    <div class="kb-pay-card-number-wrap">
                        <input id="card_number" type="text" name="card_number"
                            class="kb-form-input kb-card-input"
                            value="{{ old('card_number') }}"
                            placeholder="1234 5678 9012 3456"
                            maxlength="19" autocomplete="cc-number">
                        <x-icon name="unknown" id="card_brand_logo" class="kb-pay-brand-icon" aria-hidden="true" />
                    </div>
                    <div id="card_number_help" class="kb-form-hint" style="display:none;"></div>
                    @error('card_number') <div class="kb-form-error">{{ $message }}</div> @enderror
                </div>

                <div class="kb-pay-card-row">
                    <div class="kb-form-group">
                        <label class="kb-form-label required">Expiry</label>
                        <input id="card_expiry" type="text" name="card_expiry"
                            class="kb-form-input kb-card-input"
                            value="{{ old('card_expiry') }}" placeholder="MM/YY"
                            maxlength="5" autocomplete="cc-exp">
                        @error('card_expiry') <div class="kb-form-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="kb-form-group">
                        <label class="kb-form-label required">CVV</label>
                        <div class="kb-pay-cvv-wrap">
                            <input id="card_cvv" type="text" name="card_cvv"
                                class="kb-form-input kb-card-input"
                                value="{{ old('card_cvv') }}" placeholder="123"
                                maxlength="4" autocomplete="cc-csc">
                            <i class="bi bi-lock-fill kb-pay-cvv-icon"></i>
                        </div>
                        @error('card_cvv') <div class="kb-form-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            <label class="kb-pay-save-check">
                <input type="checkbox" name="save_card" value="1">
                <span>Save this card for future purchases</span>
            </label>
        </div>

        {{-- Security badge --}}
        <div class="kb-pay-secure-badge">
            <div class="kb-pay-secure-left">
                <i class="bi bi-shield-lock-fill"></i>
                <div>
                    <strong>Secure Payment</strong>
                    <span>Simulated — no real charges</span>
                </div>
            </div>
            <div class="kb-pay-secure-icons">
                <i class="bi bi-credit-card-2-front" title="Visa"></i>
                <i class="bi bi-credit-card" title="Mastercard"></i>
                <i class="bi bi-credit-card-fill" title="Amex"></i>
            </div>
        </div>
    </div>

    {{-- PayPal notice --}}
    <div id="paypal-notice" style="display:none;" class="kb-pay-paypal-notice">
        <div class="kb-pay-paypal-icon">
            <x-icon name="paypal" />
        </div>
        <div>
            <strong>PayPal Checkout</strong>
            <p>You'll be redirected to PayPal to complete your payment securely. (Simulated — no real transaction.)</p>
        </div>
    </div>
</div>

<style>
/* ═══════════════════════════════════════
   Payment Section — Redesigned
   ═══════════════════════════════════════ */

/* ── Method Tabs ── */
.kb-pay-tabs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
}
.kb-pay-tab {
    cursor: pointer;
    position: relative;
}
.kb-pay-tab input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.kb-pay-tab-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 1rem;
    border: 2px solid var(--kb-button-border, #e5e7eb);
    border-radius: 12px;
    transition: all 0.2s;
    background: var(--kb-primary-bg, #fff);
}
.kb-pay-tab input:checked + .kb-pay-tab-inner {
    border-color: var(--kb-accent, #EB7347);
    background: rgba(235, 115, 71, 0.04);
    box-shadow: 0 0 0 3px rgba(235, 115, 71, 0.1);
}
.kb-pay-tab-inner:hover {
    border-color: var(--kb-accent, #EB7347);
}
.kb-pay-tab-icons {
    font-size: 1.5rem;
    color: var(--kb-secondary-font, #6b7280);
    height: 28px;
    display: flex;
    align-items: center;
}
.kb-pay-tab input:checked + .kb-pay-tab-inner .kb-pay-tab-icons {
    color: var(--kb-accent, #EB7347);
}
.kb-pay-tab-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--kb-primary-font, #111);
}

/* ── Card Area ── */
.kb-pay-card-area {
    animation: kbPayFadeIn 0.2s ease;
}
@keyframes kbPayFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Saved Cards ── */
.kb-pay-saved {
    margin-bottom: 1.25rem;
}
.kb-pay-saved-title {
    font-size: 13px;
    font-weight: 700;
    color: var(--kb-primary-font, #111);
    margin: 0 0 0.6rem;
    display: flex;
    align-items: center;
    gap: 6px;
}
.kb-pay-saved-title i {
    color: var(--kb-accent, #EB7347);
}
.kb-pay-saved-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.kb-pay-saved-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    border: 2px solid var(--kb-button-border, #e5e7eb);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.15s;
    background: var(--kb-primary-bg, #fff);
}
.kb-pay-saved-card:hover {
    border-color: var(--kb-button-beige-border, #f59e0b);
}
.kb-pay-saved-card input[type="radio"] {
    display: none;
}
.kb-pay-saved-card input:checked ~ .kb-pay-saved-card-body {
    color: var(--kb-primary-font);
}
.kb-pay-saved-card:has(input:checked) {
    border-color: var(--kb-accent, #EB7347);
    background: rgba(235, 115, 71, 0.03);
}
.kb-pay-saved-card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.kb-pay-saved-card-top {
    display: flex;
    align-items: center;
    gap: 8px;
}
.kb-pay-saved-card-brand {
    font-weight: 700;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--kb-primary-font, #111);
}
.kb-pay-saved-card-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 20px;
    background: var(--kb-accent, #EB7347);
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.kb-pay-saved-card-num {
    font-size: 14px;
    font-family: 'Courier New', monospace;
    letter-spacing: 1.5px;
    color: var(--kb-primary-font, #111);
}
.kb-pay-saved-card-exp {
    font-size: 11px;
    color: var(--kb-secondary-font, #6b7280);
}
.kb-pay-saved-card-check {
    color: var(--kb-button-border, #e5e7eb);
    font-size: 1.25rem;
    transition: color 0.15s;
}
.kb-pay-saved-card:has(input:checked) .kb-pay-saved-card-check {
    color: var(--kb-accent, #EB7347);
}

/* New card option */
.kb-pay-saved-card-new .kb-pay-saved-card-body {
    flex-direction: row;
    align-items: center;
    gap: 8px;
    color: var(--kb-secondary-font, #6b7280);
    font-size: 13px;
    font-weight: 500;
}
.kb-pay-saved-card-new .kb-pay-saved-card-body i {
    font-size: 1.2rem;
    color: var(--kb-accent, #EB7347);
}
.kb-pay-saved-card-new:has(input:checked) .kb-pay-saved-card-body {
    color: var(--kb-accent, #EB7347);
}

/* ── New Card Form ── */
.kb-pay-new-card {
    animation: kbPayFadeIn 0.2s ease;
}
.kb-pay-card-form {
    padding: 1.25rem;
    background: var(--kb-grey-50, #f9fafb);
    border: 1px solid var(--kb-button-border, #e5e7eb);
    border-radius: 12px;
    margin-bottom: 0.75rem;
}
[data-theme="dark"] .kb-pay-card-form {
    background: var(--kb-secondary-bg);
}
.kb-pay-card-number-wrap {
    position: relative;
}
.kb-pay-brand-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: auto;
    opacity: 0.6;
}
.kb-pay-card-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.kb-pay-cvv-wrap {
    position: relative;
}
.kb-pay-cvv-icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--kb-secondary-font, #6b7280);
    font-size: 14px;
}

/* Save card checkbox */
.kb-pay-save-check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--kb-primary-font, #111);
    cursor: pointer;
    margin-bottom: 1rem;
    padding-left: 2px;
}
.kb-pay-save-check input[type="checkbox"] {
    accent-color: var(--kb-accent, #EB7347);
    width: 16px;
    height: 16px;
}

/* ── Secure Badge ── */
.kb-pay-secure-badge {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background: var(--kb-green-50, #f0fdf4);
    border: 1px solid var(--kb-green-200, #bbf7d0);
    border-radius: 10px;
}
[data-theme="dark"] .kb-pay-secure-badge {
    background: rgba(22, 163, 74, 0.08);
    border-color: rgba(22, 163, 74, 0.2);
}
.kb-pay-secure-left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.kb-pay-secure-left i {
    font-size: 1.25rem;
    color: var(--kb-green-600, #16a34a);
}
.kb-pay-secure-left strong {
    display: block;
    font-size: 13px;
    color: var(--kb-green-700, #166534);
}
.kb-pay-secure-left span {
    display: block;
    font-size: 11px;
    color: var(--kb-green-600, #16a34a);
}
.kb-pay-secure-icons {
    display: flex;
    gap: 8px;
    font-size: 1.25rem;
    color: var(--kb-secondary-font, #6b7280);
    opacity: 0.5;
}

/* ── PayPal Notice ── */
.kb-pay-paypal-notice {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 1.25rem;
    background: #fef8e8;
    border: 1px solid #fde68a;
    border-radius: 12px;
    animation: kbPayFadeIn 0.2s ease;
}
[data-theme="dark"] .kb-pay-paypal-notice {
    background: rgba(251, 191, 36, 0.08);
    border-color: rgba(251, 191, 36, 0.2);
}
.kb-pay-paypal-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.kb-pay-paypal-notice strong {
    display: block;
    font-size: 14px;
    color: var(--kb-primary-font, #111);
    margin-bottom: 2px;
}
.kb-pay-paypal-notice p {
    margin: 0;
    font-size: 13px;
    color: var(--kb-secondary-font, #6b7280);
    line-height: 1.5;
}
.kb-pay-test-link {
    margin-left: auto;
    color: var(--kb-secondary-font, #6b7280);
    font-size: 1.1rem;
    transition: color 0.15s;
}
.kb-pay-test-link:hover {
    color: var(--kb-accent, #EB7347);
}

/* ── Mobile ── */
@media (max-width: 480px) {
    .kb-pay-tabs { gap: 0.5rem; }
    .kb-pay-tab-inner { padding: 0.75rem 0.5rem; }
    .kb-pay-card-form { padding: 1rem; }
    .kb-pay-card-row { grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    .kb-pay-saved-card { padding: 0.75rem; }
    .kb-pay-saved-card-num { font-size: 13px; letter-spacing: 1px; }
    .kb-pay-secure-icons { display: none; }
}
</style>