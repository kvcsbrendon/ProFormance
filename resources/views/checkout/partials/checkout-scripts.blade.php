{{-- checkout/partials/checkout-scripts.blade.php --}}
<script>
/* ═══════════════════════════════════════════════════════
   Checkout Scripts — All fixes baked in
   ═══════════════════════════════════════════════════════ */

const CHECKOUT_SYMBOL = '{{ $symbol ?? "£" }}';

function fmt(penny) {
    return CHECKOUT_SYMBOL + (penny / 100).toFixed(2);
}

function setText(id, text) {
    var el = document.getElementById(id);
    if (el) el.textContent = text;
}

function showEl(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = '';
}

function hideEl(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

/* ─── Payment method: Card vs PayPal ─── */
function handlePaymentMethodChange() {
    var isPaypal = document.querySelector('input[name="payment_method"][value="paypal"]')?.checked;
    var cardArea = document.getElementById('card-payment-area');
    var paypalNotice = document.getElementById('paypal-notice');

    if (isPaypal) {
        cardArea.style.display = 'none';
        paypalNotice.style.display = 'block';
        cardArea.querySelectorAll('input').forEach(function(i) { i.disabled = true; });
    } else {
        cardArea.style.display = 'block';
        paypalNotice.style.display = 'none';
        cardArea.querySelectorAll('input[name="saved_card_id"]').forEach(function(i) { i.disabled = false; });
        var savedChecked = document.querySelector('input[name="saved_card_id"]:checked');
        if (savedChecked && savedChecked.value) {
            handleCardChoice(true);
        } else {
            handleCardChoice(false);
        }
    }
}
function togglePayPal() { handlePaymentMethodChange(); }

/* ─── Saved card vs New card ─── */
function handleCardChoice(useSaved) {
    var newFields = document.getElementById('new-card-fields');
    if (!newFields) return;

    if (useSaved) {
        newFields.style.display = 'none';
        newFields.querySelectorAll('.kb-card-input').forEach(function(i) {
            i.disabled = true;
            i.removeAttribute('required');
            i.value = '';
        });
    } else {
        newFields.style.display = 'block';
        newFields.querySelectorAll('.kb-card-input').forEach(function(i) {
            i.disabled = false;
        });
        ['card_name', 'card_number', 'card_expiry', 'card_cvv'].forEach(function(name) {
            var el = newFields.querySelector('[name="' + name + '"]');
            if (el) el.setAttribute('required', '');
        });
    }
}
function toggleCardFields(useSaved) { handleCardChoice(useSaved); }

/* ─── On page load ─── */
document.addEventListener('DOMContentLoaded', function() {
    var savedChecked = document.querySelector('input[name="saved_card_id"]:checked');
    if (savedChecked && savedChecked.value) {
        handleCardChoice(true);
    }
    var paypalChecked = document.querySelector('input[name="payment_method"][value="paypal"]')?.checked;
    if (paypalChecked) {
        handlePaymentMethodChange();
    }

    // Recalculate when saved address selection changes
    document.querySelectorAll('input[name="address_id"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateCheckoutTotals();
        });
    });
});

/* ═══════════════════════════════════════════════════════
   REBUILD SHIPPING METHOD RADIOS
   Called when country changes — replaces the radio buttons
   with methods available for the new country.
   ═══════════════════════════════════════════════════════ */
function rebuildShippingMethods(methods, symbol) {
    var containers = document.querySelectorAll('input[name="shipping_method"]');
    if (containers.length === 0) return;

    var parent = containers[0].closest('.kb-form-group');
    if (!parent) return;

    // Remember current selection
    var currentMethod = document.querySelector('input[name="shipping_method"]:checked');
    var currentValue = currentMethod ? currentMethod.value : 'standard';

    // Keep the label
    var label = parent.querySelector('.kb-form-label');

    // Clear and rebuild
    parent.innerHTML = '';
    if (label) parent.appendChild(label);

    var hasCurrentSelection = false;
    methods.forEach(function(sm) {
        var radioLabel = document.createElement('label');
        radioLabel.className = 'kb-radio';

        var input = document.createElement('input');
        input.type = 'radio';
        input.name = 'shipping_method';
        input.value = sm.method_key;
        input.onchange = function() { updateCheckoutTotals(); };

        if (sm.method_key === currentValue) {
            input.checked = true;
            hasCurrentSelection = true;
        }

        var span = document.createElement('span');
        var price = (sm.price_penny / 100).toFixed(2);
        span.textContent = sm.method_label + ' — ' + symbol + price;

        radioLabel.appendChild(input);
        radioLabel.appendChild(span);
        parent.appendChild(radioLabel);
    });

    // If previous selection not available, select first and recalc
    if (!hasCurrentSelection && methods.length > 0) {
        var firstRadio = parent.querySelector('input[name="shipping_method"]');
        if (firstRadio) {
            firstRadio.checked = true;
            updateCheckoutTotals();
        }
    }
}

/* ═══════════════════════════════════════════════════════
   CENTRAL TOTALS REFRESH
   ═══════════════════════════════════════════════════════ */
function updateCheckoutTotals() {
    var method = document.querySelector('input[name="shipping_method"]:checked')?.value || 'standard';
    var giftMethod = document.querySelector('input[name="gift_shipping_method"]:checked')?.value;
    var csrfToken = document.querySelector('input[name="_token"]')?.value
        || document.querySelector('meta[name="csrf-token"]')?.content || '';

    var body = { shipping_method: method };
    if (giftMethod) body.gift_shipping_method = giftMethod;

    // Determine the shipping country
    var selectedAddress = document.querySelector('input[name="address_id"]:checked');
    if (selectedAddress && selectedAddress.value) {
        // Saved address — send both ID and its country
        body.address_id = selectedAddress.value;
        body.ship_country_code = selectedAddress.dataset.country || '';
    } else {
        // Manual address — read from the dropdown
        var countrySelect = document.querySelector('[name="ship_country_code"]');
        if (countrySelect) {
            body.ship_country_code = countrySelect.value || 'GB';
        }
    }

    return fetch('{{ route("checkout.totals") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify(body),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (!data.ok) return;

        // ── Core totals ──
        setText('subtotalAmount', fmt(data.subtotalPenny));
        setText('shippingAmount', data.subFreeShipping ? 'FREE' : fmt(data.shippingNetPenny));
        setText('vatAmount', fmt(data.combinedVatPenny));
        setText('totalAmount', fmt(data.totalPenny));

        // ── VAT label ──
        var vatLabel = document.getElementById('vatLabel');
        if (vatLabel) {
            if (data.vatRate > 0) {
                vatLabel.textContent = 'VAT (' + Math.round(data.vatRate * 100) + '%)';
            } else {
                vatLabel.textContent = 'VAT (0% — Export)';
            }
        }

        // ── Discount row + badge ──
        if (data.discountPenny > 0) {
            showEl('discount-total-row');
            setText('discountAmount', '-' + fmt(data.discountPenny));
            setText('discount-amount-display', '-' + fmt(data.discountPenny));
        } else {
            hideEl('discount-total-row');
        }

        // ── Subscriber benefits badge ──
        if (data.hasActiveSubscription && (data.subFreeShipping || data.subDiscountPenny > 0)) {
            showEl('checkout-sub-applied');
        } else {
            hideEl('checkout-sub-applied');
        }

        // Free shipping row inside benefits
        if (data.subFreeShipping) {
            showEl('sub-free-shipping-row');
            setText('sub-free-shipping-saving', '-' + fmt(data.originalShippingPenny || 0));
        } else {
            hideEl('sub-free-shipping-row');
        }

        // Member discount row inside benefits
        if (data.subDiscountPenny > 0) {
            showEl('sub-discount-row');
            setText('sub-discount-amount', '-' + fmt(data.subDiscountPenny));
        } else {
            hideEl('sub-discount-row');
        }

        // ── S&S discount row ──
        if (data.ssDiscountPenny > 0) {
            showEl('ss-discount-row');
            setText('ss-discount-amount', '-' + fmt(data.ssDiscountPenny));
        } else {
            hideEl('ss-discount-row');
        }

        // ── Non-subscriber promo ──
        if (!data.hasActiveSubscription && data.potentialSavings > 0) {
            showEl('checkout-sub-promo');
            setText('potential-savings-amount', fmt(data.potentialSavings));
        } else {
            hideEl('checkout-sub-promo');
        }

        // ── Rebuild shipping methods if country changed ──
        if (data.shippingMethods && data.shippingMethods.length > 0) {
            rebuildShippingMethods(data.shippingMethods, CHECKOUT_SYMBOL);
        }

        // ── Place order buttons ──
        document.querySelectorAll('.kb-checkout-place-order-btn').forEach(function(btn) {
            var icon = btn.querySelector('i');
            var isSubmitOnly = !btn.textContent.includes('—');
            if (isSubmitOnly) return;
            btn.textContent = '';
            if (icon) btn.appendChild(icon);
            btn.append(' Place Order — ' + fmt(data.totalPenny));
        });
    })
    .catch(function(err) { console.error('Totals update error:', err); });
}

// Alias for shipping radio onchange
function updateShippingTotals() { updateCheckoutTotals(); }

/* ═══════════════════════════════════════════════════════
   DISCOUNT — Apply (AJAX, no reload)
   ═══════════════════════════════════════════════════════ */
function applyDiscountAjax() {
    var input = document.getElementById('discount-code-input');
    var code = input ? input.value.trim() : '';
    if (!code) return false;

    var btn = document.getElementById('discount-apply-btn');
    var msgEl = document.getElementById('discount-message');
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    if (btn) { btn.disabled = true; btn.textContent = 'Applying…'; }

    fetch('{{ route("checkout.discount.apply") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ discount_code: code }),
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (btn) { btn.disabled = false; btn.textContent = 'Apply'; }

        if (data.success) {
            setText('discount-code-display', code.toUpperCase());
            showEl('discount-applied');
            hideEl('discount-form-wrap');
            if (msgEl) msgEl.textContent = '';
            updateCheckoutTotals();
        } else {
            if (msgEl) {
                msgEl.textContent = data.message || 'Invalid discount code.';
                msgEl.style.color = 'var(--kb-red-500, red)';
            }
        }
    })
    .catch(function(err) {
        if (btn) { btn.disabled = false; btn.textContent = 'Apply'; }
        if (msgEl) {
            msgEl.textContent = 'Something went wrong. Try again.';
            msgEl.style.color = 'var(--kb-red-500, red)';
        }
        console.error('Discount error:', err);
    });

    return false;
}

/* ═══════════════════════════════════════════════════════
   DISCOUNT — Remove (AJAX, no reload)
   ═══════════════════════════════════════════════════════ */
function removeDiscountAjax() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch('{{ route("checkout.discount.remove") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(function() {
        hideEl('discount-applied');
        showEl('discount-form-wrap');
        var input = document.getElementById('discount-code-input');
        if (input) input.value = '';
        updateCheckoutTotals();
    })
    .catch(function(err) {
        console.error('Remove discount error:', err);
    });

    return false;
}

function removeDiscount() { return removeDiscountAjax(); }

/* ─── Enter key on discount input ─── */
document.addEventListener('DOMContentLoaded', function() {
    var di = document.getElementById('discount-code-input');
    if (di) {
        di.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyDiscountAjax();
            }
        });
    }

    var discountForm = document.querySelector('.kb-checkout-discount-form');
    if (discountForm) {
        discountForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyDiscountAjax();
        });
    }
});
</script>
