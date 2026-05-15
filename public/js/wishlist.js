function toggleWishlistCard(btn) {
    const variantId = btn.dataset.variantId;
    const icon      = btn.querySelector('i');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch('/wishlist/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ variant_id: variantId }),
    })
    .then(res => {
        if (res.status === 401) { window.location.href = '/login'; return; }
        return res.json();
    })
    .then(data => {
        if (!data) return;
        if (data.in_wishlist) {
            btn.classList.add('kb-wishlisted');
            icon.className = 'bi bi-heart-fill';
        } else {
            btn.classList.remove('kb-wishlisted');
            icon.className = 'bi bi-heart';
        }
    })
    .catch(err => console.error('Wishlist error:', err));
}
