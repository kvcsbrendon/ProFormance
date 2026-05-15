document.addEventListener('DOMContentLoaded', () => {

    const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const cartCountEl = document.getElementById('cart-count');

    // ══════════════════════════════════════
    //  Shared helpers
    // ══════════════════════════════════════

    function setHeaderCount(n) {
        if (cartCountEl) cartCountEl.textContent = String(n);
    }

    function money(symbol, value) {
        return `${symbol}${Number(value || 0).toFixed(2)}`;
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function debounce(fn, delay = 350) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); };
    }

    // Reload cart page after user stops changing quantities
    let idleTimer = null;
    function scheduleCartPageReloadAfterIdle() {
        if (idleTimer) clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
            if (window.location.pathname === '/cart' || window.location.pathname.startsWith('/cart/')) {
                window.location.reload();
            }
        }, 3500);
    }


    // ══════════════════════════════════════
    //  1. Product listing add-to-basket controls
    // ══════════════════════════════════════

    // Inject helper CSS so hidden works with display:flex containers
    const style = document.createElement('style');
    style.textContent = '.qty-controls[hidden]{display:none !important;}';
    document.head.appendChild(style);

    // Fetch existing cart to init controls with correct quantities
    fetch('/cart/preview', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            const qtyByVariant = {};
            let totalItems = 0;

            if (data?.ok && Array.isArray(data.lines)) {
                data.lines.forEach(line => {
                    if (typeof line.variant_id !== 'undefined') {
                        qtyByVariant[String(line.variant_id)] = Number(line.quantity || 0);
                    }
                    totalItems += Number(line.quantity || 0);
                });
            }

            setHeaderCount(totalItems);
            initAddToBasketControls(qtyByVariant);
        })
        .catch(() => initAddToBasketControls({}));

    function initAddToBasketControls(qtyByVariant) {
        document.querySelectorAll('.cart-control').forEach(control => {
            const addBtn      = control.querySelector('.add-btn');
            const qtyControls = control.querySelector('.qty-controls');
            const plusBtn     = control.querySelector('.plus-btn');
            const minusBtn    = control.querySelector('.minus-btn');
            const qtyDisplay  = control.querySelector('.qty');

            const variantId = control.getAttribute('data-variant');
            const cartUrl   = control.getAttribute('data-cart-url');

            let quantity = qtyByVariant[String(variantId)] || 0;

            function setMinusIcon() {
                minusBtn.textContent = quantity <= 1 ? '🗑' : '−';
            }

            function sendSetQuantity(qty) {
                return fetch(cartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: new URLSearchParams({ variant_id: variantId, quantity: String(qty) }),
                })
                .then(res => res.ok ? res.json().catch(() => null) : null)
                .then(json => {
                    if (json?.totalItems !== undefined) setHeaderCount(json.totalItems);
                })
                .catch(console.error);
            }

            let debounceTimer = null;
            function scheduleSend() {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => sendSetQuantity(quantity), 200);
                scheduleCartPageReloadAfterIdle();
            }

            // Initial state
            if (quantity > 0) {
                addBtn.hidden = true;
                qtyControls.hidden = false;
                qtyDisplay.textContent = String(quantity);
                setMinusIcon();
            } else {
                addBtn.hidden = false;
                qtyControls.hidden = true;
            }

            addBtn.addEventListener('click', () => {
                quantity = quantity > 0 ? quantity + 1 : 1;
                addBtn.hidden = true;
                qtyControls.hidden = false;
                qtyDisplay.textContent = String(quantity);
                setMinusIcon();
                sendSetQuantity(quantity);
                scheduleCartPageReloadAfterIdle();
            });

            plusBtn.addEventListener('click', () => {
                quantity++;
                qtyDisplay.textContent = String(quantity);
                setMinusIcon();
                scheduleSend();
            });

            minusBtn.addEventListener('click', () => {
                quantity--;
                if (quantity <= 0) {
                    quantity = 0;
                    qtyControls.hidden = true;
                    addBtn.hidden = false;
                    sendSetQuantity(0);
                    scheduleCartPageReloadAfterIdle();
                    return;
                }
                qtyDisplay.textContent = String(quantity);
                setMinusIcon();
                scheduleSend();
            });
        });
    }


    // ══════════════════════════════════════
    //  2. Cart page quantity editing
    // ══════════════════════════════════════

    function formatMoney(n) { return Number(n).toFixed(2); }

    function setRowStatus(row, msg) {
        const el = row.querySelector('.js-row-status');
        if (el) el.textContent = msg || '';
    }

    function updateRowSubtotal(row, qty) {
        const price = Number(row.getAttribute('data-price') || 0);
        const subtotalEl = row.querySelector('.js-line-subtotal');
        if (subtotalEl) subtotalEl.textContent = formatMoney(price * qty);
    }

    function updateSummary(totalItems, total) {
        const el1 = document.getElementById('js-total-items');
        const el2 = document.getElementById('js-total-items-2');
        const totalEl = document.getElementById('js-total');
        if (el1) el1.textContent = totalItems;
        if (el2) el2.textContent = totalItems;
        if (totalEl) totalEl.textContent = formatMoney(total);
    }

    async function postQtyUpdate(form, qty) {
        const res = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ quantity: qty }),
        });
        if (!res.ok) throw new Error('Update failed');
        return res.json();
    }

    const sendCartPageUpdate = debounce(async (input) => {
        const row  = input.closest('.kb-cart-row');
        const form = input.closest('form');
        if (!row || !form) return;

        let qty = parseInt(input.value || '0', 10);
        if (Number.isNaN(qty)) qty = 0;
        qty = Math.max(0, qty);
        input.value = qty;

        updateRowSubtotal(row, qty);
        setRowStatus(row, 'Updating…');

        try {
            const data = await postQtyUpdate(form, qty);
            if (qty <= 0) {
                row.remove();
                updateSummary(data.totalItems, data.total);
                if (data.totalItems <= 0) location.reload();
                return;
            }
            updateSummary(data.totalItems, data.total);
            setRowStatus(row, 'Updated');
            setTimeout(() => setRowStatus(row, ''), 700);
        } catch {
            setRowStatus(row, 'Refreshing…');
            form.submit();
        }
    }, 300);

    document.querySelectorAll('.js-qty-input').forEach(input => {
        input.addEventListener('input',  () => sendCartPageUpdate(input));
        input.addEventListener('change', () => sendCartPageUpdate(input));
    });

    document.addEventListener('click', e => {
        const inc = e.target.closest('.js-qty-inc');
        const dec = e.target.closest('.js-qty-dec');
        if (!inc && !dec) return;

        const row   = (inc || dec).closest('.kb-cart-row');
        const input = row?.querySelector('.js-qty-input');
        if (!input) return;

        const cur = parseInt(input.value || '0', 10) || 0;
        input.value = inc ? cur + 1 : Math.max(0, cur - 1);
        sendCartPageUpdate(input);
    });


    // ══════════════════════════════════════
    //  3. Cart sidebar preview (hover)
    // ══════════════════════════════════════

    const previewItems      = document.getElementById('preview-items');
    const previewTotalItems = document.getElementById('preview-total-items');
    const previewTotalPrice = document.getElementById('preview-total-price');
    const container = document.querySelector('.kb-cart-hover');

    if (previewItems && container) {
        const CACHE_MS = 15000;
        let cache    = null;
        let inflight = null;

        function render(data) {
            const lines  = data.lines || [];
            const symbol = data.symbol || '£';

            if (previewTotalItems) {
                const n = data.totalItems || 0;
                previewTotalItems.textContent = `${n} item${n === 1 ? '' : 's'}`;
            }
            if (previewTotalPrice) previewTotalPrice.textContent = money(symbol, data.total || 0);
            if (cartCountEl) cartCountEl.textContent = data.totalItems || 0;

            if (!lines.length) {
                previewItems.innerHTML = '<div style="padding:10px 0;color:#fec89A;font-size:14px;">Your cart is empty.</div>';
                return;
            }

            previewItems.innerHTML = lines.map(line => {
                const lineTotal = (line.price || 0) * (line.quantity || 0);
                const img = line.image
                    ? `<img class="cart-preview-thumb" src="/${line.image}" alt="">`
                    : '<div class="cart-preview-thumb"></div>';

                return `
                    <div class="cart-preview-item">
                      ${img}
                      <div class="cart-preview-meta">
                        <p class="cart-preview-title">${escapeHtml(line.name || '')}</p>
                        <div class="cart-preview-sub">Qty: ${line.quantity || 0} · ${money(symbol, lineTotal)}</div>
                      </div>
                    </div>`;
            }).join('');
        }

        async function fetchPreview() {
            if (cache && (Date.now() - cache.ts) < CACHE_MS) return cache.data;
            if (inflight) return inflight;

            inflight = fetch('/cart/preview', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { cache = { data, ts: Date.now() }; return data; })
                .finally(() => { inflight = null; });

            return inflight;
        }

        async function ensurePreviewFast() {
            if (cache?.data) render(cache.data);
            try {
                const data = await fetchPreview();
                if (data?.ok) render(data);
            } catch { /* keep what was rendered */ }
        }

        // Prefetch on idle
        if ('requestIdleCallback' in window) {
            requestIdleCallback(() => fetchPreview().catch(() => {}));
        } else {
            setTimeout(() => fetchPreview().catch(() => {}), 300);
        }

        container.addEventListener('mouseenter', ensurePreviewFast);

        // Invalidate cache when tab becomes visible again
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) cache = null;
        });
    }
});
