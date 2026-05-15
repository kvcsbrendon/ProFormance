document.addEventListener('DOMContentLoaded', () => {
    const elNumber = document.getElementById('card_number');
    const elExpiry = document.getElementById('card_expiry');
    const elCvv    = document.getElementById('card_cvv');
    const elHelp   = document.getElementById('card_number_help');

    if (!elNumber) return;

    const supported = new Set(['visa', 'mastercard', 'amex']);

    function digitsOnly(s) { return (s || '').replace(/\D+/g, ''); }

    function setInvalid(el, bad) {
        if (!el) return;
        el.classList.toggle('is-invalid', !!bad);
        el.setAttribute('aria-invalid', bad ? 'true' : 'false');
    }

    function showHelp(msg) {
        if (!elHelp) return;
        if (!msg) { elHelp.style.display = 'none'; elHelp.textContent = ''; return; }
        elHelp.style.display = 'block';
        elHelp.textContent = msg;
    }

    function detectBrand(pan) {
        if (!pan) return null;
        if (/^(34|37)/.test(pan))    return 'amex';
        if (/^4/.test(pan))          return 'visa';
        if (/^(5[1-5])/.test(pan))   return 'mastercard';
        if (pan.length >= 4) {
            const first4 = parseInt(pan.slice(0, 4), 10);
            if (first4 >= 2221 && first4 <= 2720) return 'mastercard';
        }
        if (/^(6011|65|64[4-9])/.test(pan)) return 'discover';
        if (/^35/.test(pan))         return 'jcb';
        if (/^(300|301|302|303|304|305|36|38)/.test(pan)) return 'diners';
        return null;
    }

    function setBrandIcon(name) {
        const icon    = name || 'unknown';
        const exists  = document.getElementById(`icon-${icon}`);
        const finalId = exists ? icon : 'unknown';

        const useEl = document.querySelector('#card_brand_logo use');
        if (!useEl) return;

        const ref = `#icon-${finalId}`;
        useEl.setAttribute('href', ref);
        useEl.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', ref);
    }

    function expectedPanLength(brand) {
        if (brand === 'amex')   return 15;
        if (brand === 'diners') return 14;
        return 16; // visa, mastercard, discover, jcb, default
    }

    function expectedCvvLength(brand) {
        return brand === 'amex' ? 4 : 3;
    }

    function formatPan(pan, brand) {
        if (brand === 'amex') {
            return [pan.slice(0, 4), pan.slice(4, 10), pan.slice(10, 15)].filter(Boolean).join(' ');
        }
        return pan.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    }

    function luhnValid(pan) {
        let sum = 0, alt = false;
        for (let i = pan.length - 1; i >= 0; i--) {
            let n = pan.charCodeAt(i) - 48;
            if (alt) { n *= 2; if (n > 9) n -= 9; }
            sum += n;
            alt = !alt;
        }
        return sum % 10 === 0;
    }

    function formatExpiry(v) {
        const d = digitsOnly(v).slice(0, 4);
        return d.length <= 2 ? d : d.slice(0, 2) + '/' + d.slice(2);
    }

    function expiryValid(mmYY) {
        const m = (mmYY || '').match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
        if (!m) return false;
        const exp = new Date(2000 + parseInt(m[2], 10), parseInt(m[1], 10), 0, 23, 59, 59, 999);
        return exp >= new Date();
    }

    function updateNumber() {
        const pan   = digitsOnly(elNumber.value);
        const brand = detectBrand(pan);

        setBrandIcon(brand || 'unknown');

        const formatted = formatPan(pan, brand);
        if (elNumber.value !== formatted) elNumber.value = formatted;

        if (brand && !supported.has(brand)) {
            showHelp("We don't accept this card type. Please use Visa, MasterCard, or American Express.");
            setInvalid(elNumber, true);
            return;
        }

        showHelp('');

        const expLen = expectedPanLength(brand);
        if (expLen && pan.length >= expLen) {
            const okLen  = pan.length === expLen;
            const okLuhn = okLen ? luhnValid(pan) : false;
            setInvalid(elNumber, !(okLen && okLuhn));
            if (okLen && !okLuhn) showHelp('Card number is not valid.');
        } else {
            setInvalid(elNumber, false);
        }

        if (elCvv) {
            const want = expectedCvvLength(brand);
            elCvv.maxLength   = want;
            elCvv.placeholder = want === 4 ? '1234' : '123';
        }
    }

    function updateExpiry() {
        if (!elExpiry) return;
        const f = formatExpiry(elExpiry.value);
        if (elExpiry.value !== f) elExpiry.value = f;
        setInvalid(elExpiry, f.length === 5 && !expiryValid(f));
    }

    function updateCvv() {
        if (!elCvv) return;
        const brand = detectBrand(digitsOnly(elNumber.value));
        const want  = expectedCvvLength(brand);
        const cvv   = digitsOnly(elCvv.value).slice(0, want);
        elCvv.value = cvv;
        setInvalid(elCvv, cvv.length > 0 && cvv.length !== want);
    }

    elNumber.addEventListener('input', () => { updateNumber(); updateCvv(); });
    elExpiry?.addEventListener('input', updateExpiry);
    elCvv?.addEventListener('input', updateCvv);

    updateNumber();
    updateExpiry();
    updateCvv();
});
