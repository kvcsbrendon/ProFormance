let _wlPickerVariantId = null;
let _wlPickerBtn       = null;
const csrfMeta         = () => document.querySelector('meta[name="csrf-token"]')?.content
    || document.querySelector('input[name="_token"]')?.value
    || '';

// ── Open the picker ──
function openWishlistPicker(btn) {
    _wlPickerVariantId = btn.dataset.variantId;
    _wlPickerBtn       = btn;

    const overlay = document.getElementById('wl-picker-overlay');
    const picker  = document.getElementById('wl-picker');

    overlay.style.display = 'block';
    picker.style.display  = 'flex';

    // Reset states
    document.getElementById('wl-picker-loading').style.display = 'block';
    document.getElementById('wl-picker-list').style.display    = 'none';
    document.getElementById('wl-picker-empty').style.display   = 'none';
    document.getElementById('wl-picker-success').style.display = 'none';
    toggleCreateRow(false);

    // Fetch wishlists — pass variant_id so backend marks which contain this item
    fetch('/wishlist/picker?variant_id=' + encodeURIComponent(_wlPickerVariantId), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => {
        if (res.status === 401) { window.location.href = '/login'; return; }
        return res.json();
    })
    .then(data => {
        if (!data) return;

        document.getElementById('wl-picker-loading').style.display = 'none';

        if (data.wishlists && data.wishlists.length > 0) {
            renderWishlists(data.wishlists);
        } else {
            document.getElementById('wl-picker-empty').style.display = 'flex';
        }
    })
    .catch(err => {
        console.error('Wishlist picker error:', err);
        document.getElementById('wl-picker-loading').innerHTML = 'Failed to load. Please try again.';
    });
}

function closeWishlistPicker() {
    document.getElementById('wl-picker-overlay').style.display = 'none';
    document.getElementById('wl-picker').style.display         = 'none';
    _wlPickerVariantId = null;
}

// ── Render the wishlist list with checkmarks ──
function renderWishlists(wishlists) {
    const list = document.getElementById('wl-picker-list');
    list.innerHTML = '';
    list.style.display = 'block';
    document.getElementById('wl-picker-empty').style.display = 'none';

    wishlists.forEach(wl => {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'kb-wl-picker-item' + (wl.contains_item ? ' kb-wl-picker-item-active' : '');
        row.dataset.wishlistId = wl.id;
        row.innerHTML = `
            <div class="kb-wl-picker-item-info">
                <i class="bi ${wl.contains_item ? 'bi-heart-fill' : 'bi-heart'}"></i>
                <span class="kb-wl-picker-item-name">${escHtml(wl.name)}</span>
            </div>
            <div class="kb-wl-picker-item-right">
                <span class="kb-wl-picker-item-count">${wl.items_count} item${wl.items_count !== 1 ? 's' : ''}</span>
                ${wl.contains_item ? '<i class="bi bi-check-circle-fill kb-wl-picker-check"></i>' : ''}
            </div>
        `;
        row.addEventListener('click', () => toggleInWishlist(wl.id, wl.name, row));
        list.appendChild(row);
    });
}

// ── Toggle variant in a specific wishlist ──
function toggleInWishlist(wishlistId, wishlistName, rowEl) {
    fetch('/wishlist/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfMeta(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            variant_id:  _wlPickerVariantId,
            wishlist_id: wishlistId,
        }),
    })
    .then(res => res.json())
    .then(data => {
        if (!data) return;

        if (data.in_wishlist) {
            // Added — show checkmark
            rowEl.classList.add('kb-wl-picker-item-active');
            rowEl.querySelector('.kb-wl-picker-item-info i').className = 'bi bi-heart-fill';

            const rightEl = rowEl.querySelector('.kb-wl-picker-item-right');
            if (!rightEl.querySelector('.kb-wl-picker-check')) {
                const check = document.createElement('i');
                check.className = 'bi bi-check-circle-fill kb-wl-picker-check';
                rightEl.appendChild(check);
            }

            const countEl = rightEl.querySelector('.kb-wl-picker-item-count');
            if (countEl) countEl.textContent = data.wishlist_count + ' item' + (data.wishlist_count !== 1 ? 's' : '');
        } else {
            // Removed — remove checkmark
            rowEl.classList.remove('kb-wl-picker-item-active');
            rowEl.querySelector('.kb-wl-picker-item-info i').className = 'bi bi-heart';

            const check = rowEl.querySelector('.kb-wl-picker-check');
            if (check) check.remove();

            const countEl = rowEl.querySelector('.kb-wl-picker-item-count');
            if (countEl) countEl.textContent = data.wishlist_count + ' item' + (data.wishlist_count !== 1 ? 's' : '');
        }

        // Update the original heart button
        updateHeartButton();
    })
    .catch(err => console.error('Toggle wishlist error:', err));
}

// ── Update heart button based on whether ANY wishlist contains the item ──
function updateHeartButton() {
    if (!_wlPickerBtn) return;

    const activeItems = document.querySelectorAll('.kb-wl-picker-item-active');
    const isInAny = activeItems.length > 0;

    if (isInAny) {
        _wlPickerBtn.classList.add('kb-wishlisted');
        const icon = _wlPickerBtn.querySelector('i');
        if (icon) icon.className = 'bi bi-heart-fill';
        const span = _wlPickerBtn.querySelector('span');
        if (span) span.textContent = 'In Wishlist';
    } else {
        _wlPickerBtn.classList.remove('kb-wishlisted');
        const icon = _wlPickerBtn.querySelector('i');
        if (icon) icon.className = 'bi bi-heart';
        const span = _wlPickerBtn.querySelector('span');
        if (span) span.textContent = 'Add to Wishlist';
    }
}

// ── Create new wishlist ──
function toggleCreateRow(show) {
    document.getElementById('wl-create-row').style.display = show ? 'flex' : 'none';
    document.getElementById('wl-create-btn').style.display = show ? 'none' : 'flex';
    if (show) document.getElementById('wl-create-input').focus();
}

function createWishlist() {
    const input = document.getElementById('wl-create-input');
    const name  = input.value.trim();

    if (!name) { input.focus(); return; }

    fetch('/wishlist/quick-create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfMeta(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({ wishlist_name: name }),
    })
    .then(res => res.json())
    .then(data => {
        if (!data?.success) return;

        input.value = '';
        toggleCreateRow(false);

        // Add new wishlist row to the list
        const list = document.getElementById('wl-picker-list');
        list.style.display = 'block';
        document.getElementById('wl-picker-empty').style.display = 'none';

        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'kb-wl-picker-item';
        row.dataset.wishlistId = data.wishlist.id;
        row.innerHTML = `
            <div class="kb-wl-picker-item-info">
                <i class="bi bi-heart"></i>
                <span class="kb-wl-picker-item-name">${escHtml(data.wishlist.name)}</span>
            </div>
            <div class="kb-wl-picker-item-right">
                <span class="kb-wl-picker-item-count">0 items</span>
            </div>
        `;
        row.addEventListener('click', () => toggleInWishlist(data.wishlist.id, data.wishlist.name, row));
        list.prepend(row);

        // Immediately add the item to the new wishlist
        toggleInWishlist(data.wishlist.id, data.wishlist.name, row);
    })
    .catch(err => console.error('Create wishlist error:', err));
}

// ── HTML escape helper ──
function escHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML;
}

// ── Always open picker when heart is clicked ──
function toggleWishlistCard(btn) {
    openWishlistPicker(btn);
}