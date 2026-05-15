
// ── Address toggle ──

function toggleManualAddress(radio) {
    const fields = document.getElementById('manual-address-fields');
    if (fields) fields.style.display = radio.value === '' ? 'block' : 'none';
}

// ── Billing toggle ──

function toggleBilling() {
    const checkbox = document.getElementById('billing-same-checkbox');
    const fields   = document.getElementById('billing-fields');
    if (fields) fields.style.display = checkbox?.checked ? 'none' : 'block';
}

function toggleManualBilling(radio) {
    const manualFields = document.getElementById('manual-billing-fields');
    if (manualFields) {
        manualFields.style.display = radio.value === '' ? 'block' : 'none';
    }
}

// ── Payment method: Card vs PayPal ──

function handlePaymentMethodChange() {
    const isPaypal = document.querySelector('input[name="payment_method"][value="paypal"]')?.checked;
    const cardArea = document.getElementById('card-payment-area');
    const paypalNotice = document.getElementById('paypal-notice');

    if (isPaypal) {
        if (cardArea) {
            cardArea.style.display = 'none';
            // Disable ALL card inputs so they don't submit
            cardArea.querySelectorAll('input').forEach(i => { i.disabled = true; });
        }
        if (paypalNotice) paypalNotice.style.display = 'flex';
    } else {
        if (cardArea) {
            cardArea.style.display = 'block';
            // Re-enable saved card radios
            cardArea.querySelectorAll('input[name="saved_card_id"]').forEach(i => { i.disabled = false; });
        }
        if (paypalNotice) paypalNotice.style.display = 'none';

        // Let saved card logic sort out which inputs are enabled
        const savedChecked = document.querySelector('input[name="saved_card_id"]:checked');
        if (savedChecked && savedChecked.value) {
            handleCardChoice(true);
        } else {
            handleCardChoice(false);
        }
    }
}

function togglePayPal() { handlePaymentMethodChange(); }

// ── Saved card vs New card ──

function handleCardChoice(useSaved) {
    const newFields = document.getElementById('new-card-fields');
    if (!newFields) return;

    const cardInputs = newFields.querySelectorAll('.kb-card-input');

    if (useSaved) {
        newFields.style.display = 'none';
        // DISABLE inputs — disabled fields are NOT submitted with the form
        cardInputs.forEach(i => {
            i.disabled = true;
            i.removeAttribute('required');
        });
    } else {
        newFields.style.display = 'block';
        // ENABLE inputs
        cardInputs.forEach(i => {
            i.disabled = false;
        });
        // Set required on the key fields
        ['card_name', 'card_number', 'card_expiry', 'card_cvv'].forEach(name => {
            const el = newFields.querySelector('[name="' + name + '"]');
            if (el) el.setAttribute('required', '');
        });
    }
}

// Alias
function toggleCardFields(useSaved) { handleCardChoice(useSaved); }

// ── Card formatting ──

document.addEventListener('DOMContentLoaded', () => {

    // Card number: add spaces every 4 digits
    const cardNumEl = document.querySelector('input[name="card_number"]');
    if (cardNumEl) {
        cardNumEl.addEventListener('input', function () {
            let val = this.value.replace(/\s+/g, '').replace(/[^0-9]/g, '');
            this.value = val.match(/.{1,4}/g)?.join(' ') || val;
        });
    }

    // Expiry: auto-insert slash after MM
    const cardExpEl = document.querySelector('input[name="card_expiry"]');
    if (cardExpEl) {
        cardExpEl.addEventListener('input', function () {
            let val = this.value.replace(/\//g, '').replace(/[^0-9]/g, '');
            if (val.length >= 2) val = val.substring(0, 2) + '/' + val.substring(2);
            this.value = val;
        });
    }

    // ── Initial state on page load ──

    // If a saved card is pre-selected (default), hide and disable new card fields
    const savedChecked = document.querySelector('input[name="saved_card_id"]:checked');
    if (savedChecked && savedChecked.value) {
        handleCardChoice(true);
    }

    // If PayPal was somehow selected on load
    const paypalChecked = document.querySelector('input[name="payment_method"][value="paypal"]')?.checked;
    if (paypalChecked) {
        handlePaymentMethodChange();
    }

    // ── Shipping method change → update next day visibility ──

    const countryInput = document.querySelector('[name="ship_country_code"]');

    function selectedCountry() {
        const checked = document.querySelector('input[name="address_id"]:checked');
        const radioCC = (checked?.dataset?.country || '').trim().toUpperCase();
        return radioCC.length === 2 ? radioCC : (countryInput?.value || '').trim().toUpperCase();
    }

    function updateShippingOptions() {
        const cc = selectedCountry();
        const nextDayContainer = document.getElementById('nextDayOption');
        if (!nextDayContainer) return;

        if (cc === 'GB' || cc === '') {
            nextDayContainer.style.display = '';
        } else {
            nextDayContainer.style.display = 'none';
            const nextDayRadio = nextDayContainer.querySelector('input');
            if (nextDayRadio?.checked) {
                const standard = document.querySelector('input[name="shipping_method"][value="standard"]');
                if (standard) standard.checked = true;
            }
        }
    }

    // Bind shipping option updates
    document.querySelectorAll('input[name="address_id"]').forEach(r => r.addEventListener('change', updateShippingOptions));
    if (countryInput) {
        countryInput.addEventListener('input', updateShippingOptions);
        countryInput.addEventListener('change', updateShippingOptions);
    }
    updateShippingOptions();
});