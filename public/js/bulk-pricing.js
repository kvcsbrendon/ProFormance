document.addEventListener('DOMContentLoaded', function () {
    const bulkSection = document.getElementById('bulk-pricing-section');
    const bulkTiersEl = document.getElementById('bulk-tiers');
    const qtyInput = document.querySelector('input[name="quantity"], #quantity-input, [data-qty-input]');
    const allTiers = window.__bulkTiers || {};

    if (!bulkSection || !bulkTiersEl) return;

    // Get symbol from page
    const symbolEl = document.querySelector('.kb-product-price, [data-currency-symbol]');
    const symbol = symbolEl?.dataset?.currencySymbol || '£';

    /**
     * Render tiers for a given variant ID.
     */
    window.updateBulkTiers = function (variantId) {
        const tiers = allTiers[variantId] || [];

        if (tiers.length === 0) {
            bulkSection.style.display = 'none';
            return;
        }

        bulkSection.style.display = '';
        bulkTiersEl.innerHTML = '';

        tiers.forEach(tier => {
            const div = document.createElement('div');
            div.className = 'kb-bulk-tier';
            div.dataset.minQty = tier.min_quantity;
            div.innerHTML = `
                <span class="kb-bulk-tier-qty">${tier.min_quantity}+</span>
                <span class="kb-bulk-tier-price">${symbol}${tier.price.toFixed(2)} each</span>
            `;
            bulkTiersEl.appendChild(div);
        });

        // Update active highlight
        highlightActiveTier();
    };

    /**
     * Highlight the tier that matches current quantity.
     */
    function highlightActiveTier() {
        const qty = parseInt(qtyInput?.value || '1', 10);
        const tierEls = bulkTiersEl.querySelectorAll('.kb-bulk-tier');

        let activeEl = null;
        tierEls.forEach(el => {
            el.classList.remove('kb-bulk-tier-active');
            const minQty = parseInt(el.dataset.minQty, 10);
            if (qty >= minQty) {
                activeEl = el;
            }
        });

        if (activeEl) {
            activeEl.classList.add('kb-bulk-tier-active');
        }
    }

    // Listen for quantity changes
    if (qtyInput) {
        qtyInput.addEventListener('input', highlightActiveTier);
        qtyInput.addEventListener('change', highlightActiveTier);
    }

    // Initial highlight
    highlightActiveTier();
});
