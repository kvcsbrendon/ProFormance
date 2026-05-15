document.addEventListener('DOMContentLoaded', () => {

    // ── Custom dropdowns (sort, currency, etc.) ──
    document.querySelectorAll('.kb-dropdown').forEach(drop => {
        const toggle      = drop.querySelector('.kb-dropdown-toggle');
        const hiddenInput = drop.querySelector('input[type="hidden"]');
        const selected    = drop.querySelector('.kb-selected-option');

        toggle?.addEventListener('click', e => {
            e.stopPropagation();
            drop.classList.toggle('open');
        });

        drop.querySelectorAll('.kb-dropdown-item').forEach(item => {
            item.addEventListener('click', () => {
                if (hiddenInput) hiddenInput.value = item.getAttribute('data-value');
                if (selected) selected.innerText = item.innerText;
                drop.classList.remove('open');

                const form = drop.closest('form');
                if (form) form.submit();
            });
        });

        document.addEventListener('click', e => {
            if (!drop.contains(e.target)) drop.classList.remove('open');
        });
    });

    // ── Account dropdown ──
    const accountWrapper = document.querySelector('.kb-account-wrapper');
    const accountBtn     = document.querySelector('.kb-account-btn');

    if (accountWrapper && accountBtn) {
        accountBtn.addEventListener('click', e => {
            e.stopPropagation();
            accountWrapper.classList.toggle('open');
        });

        document.addEventListener('click', e => {
            if (!accountWrapper.contains(e.target)) accountWrapper.classList.remove('open');
        });
    }

    // ── Sidebars (categories + cart) ──
    const closeAll   = document.querySelector('.kb-sidebar-close-all');
    const allButtons = document.querySelectorAll('.kb-all-btn, .kb-menu-btn');
    const sidebarAll = document.querySelector('.kb-sidebar-all');
    const overlayAll = document.querySelector('.kb-sidebar-overlay-all');

    const cartBtn     = document.querySelector('.kb-cart-btn');
    const overlayCart = document.querySelector('.kb-sidebar-overlay-cart');
    const closeCart   = document.querySelector('.kb-sidebar-close-cart');

    function openSidebar(sidebar, overlay) {
        if (!sidebar || !overlay) return;
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar(sidebar, overlay) {
        if (!sidebar || !overlay) return;
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    allButtons.forEach(btn => {
        btn.addEventListener('click', () => openSidebar(sidebarAll, overlayAll));
    });

    closeAll?.addEventListener('click',   () => closeSidebar(sidebarAll, overlayAll));
    overlayAll?.addEventListener('click',  () => closeSidebar(sidebarAll, overlayAll));
    cartBtn?.addEventListener('click',     () => openSidebar(sidebarCart, overlayCart));
    closeCart?.addEventListener('click',   () => closeSidebar(sidebarCart, overlayCart));
    overlayCart?.addEventListener('click',  () => closeSidebar(sidebarCart, overlayCart));

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            if (sidebarAll?.classList.contains('active'))  closeSidebar(sidebarAll, overlayAll);
            if (sidebarCart?.classList.contains('active')) closeSidebar(sidebarCart, overlayCart);
        }
    });

    // ── Category submenus (accordion) ──
    document.querySelectorAll('.kb-category-main.has-subcategories').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const group    = this.closest('.kb-category-group');
            const isActive = group.classList.contains('active');

            document.querySelectorAll('.kb-category-group').forEach(g => {
                if (g !== group) g.classList.remove('active');
            });

            group.classList.toggle('active', !isActive);
        });
    });

    // Auto-close sidebar on mobile after category click
    document.querySelectorAll('.kb-category-main, .kb-subcategory, .kb-category-deals, .kb-category-new')
        .forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768 && sidebarAll?.classList.contains('active')) {
                    setTimeout(() => closeSidebar(sidebarAll, overlayAll), 300);
                }
            });
        });
});
