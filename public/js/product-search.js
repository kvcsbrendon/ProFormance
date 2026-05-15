/**
 * product-search.js
 *
 * Product autocomplete for the contact-us page product selector.
 * Fetches suggestions from /product-autocomplete as the user types.
 */

document.addEventListener('DOMContentLoaded', () => {
    const input       = document.getElementById('product_search');
    const suggestions = document.getElementById('product_suggestions');
    const productIdEl = document.getElementById('product_id');
    const variantIdEl = document.getElementById('variant_id');

    if (!input || !suggestions || !productIdEl || !variantIdEl) return;

    const AUTOCOMPLETE_URL = '/product-autocomplete';
    const MIN_CHARS   = 2;
    const DEBOUNCE_MS = 250;
    const MAX_ITEMS   = 10;

    let timer     = null;
    let lastQuery = '';
    let fetchCtrl = null;

    function clearIds() {
        productIdEl.value = '';
        variantIdEl.value = '';
    }

    function hide() {
        suggestions.style.display = 'none';
        suggestions.innerHTML = '';
    }

    function show(items) {
        suggestions.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) { hide(); return; }

        items.slice(0, MAX_ITEMS).forEach(item => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action';
            btn.textContent = item.label;

            btn.addEventListener('click', () => {
                input.value       = item.label;
                productIdEl.value = item.product_id ?? '';
                variantIdEl.value = item.variant_id ?? '';
                hide();
            });

            suggestions.appendChild(btn);
        });

        suggestions.style.display = 'block';
    }

    async function fetchSuggestions(q) {
        fetchCtrl?.abort();
        fetchCtrl = new AbortController();

        const res = await fetch(`${AUTOCOMPLETE_URL}?q=${encodeURIComponent(q)}`, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: fetchCtrl.signal,
        });

        if (!res.ok) { hide(); return; }
        show(await res.json());
    }

    function scheduleFetch() {
        const q = input.value.trim();
        clearIds();

        if (q.length < MIN_CHARS) { hide(); lastQuery = ''; return; }
        if (q === lastQuery) return;
        lastQuery = q;

        if (timer) clearTimeout(timer);
        timer = setTimeout(() => {
            fetchSuggestions(q).catch(err => {
                if (err?.name !== 'AbortError') hide();
            });
        }, DEBOUNCE_MS);
    }

    input.addEventListener('input', scheduleFetch);
    input.addEventListener('keydown', e => { if (e.key === 'Escape') hide(); });
    input.addEventListener('focus', () => { if (input.value.trim().length >= MIN_CHARS) scheduleFetch(); });

    document.addEventListener('click', e => {
        if (e.target !== input && !suggestions.contains(e.target)) hide();
    });
});
