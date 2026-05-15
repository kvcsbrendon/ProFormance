document.addEventListener('DOMContentLoaded', function () {
    const container = document.querySelector('.kb-pdp-main-image');
    if (!container) return;

    const img = container.querySelector('img');
    if (!img) return;

    const isMobile = window.matchMedia('(max-width: 768px)').matches;

    if (!isMobile) {
        // ── DESKTOP: hover zoom ──
        container.addEventListener('mouseenter', function () {
            container.classList.add('zooming');
        });

        container.addEventListener('mouseleave', function () {
            container.classList.remove('zooming');
            img.style.transformOrigin = 'center center';
        });

        container.addEventListener('mousemove', function (e) {
            if (!container.classList.contains('zooming')) return;
            const rect = container.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            img.style.transformOrigin = x + '% ' + y + '%';
        });
    } else {
        // ── MOBILE: tap to open lightbox ──
        const overlay = document.getElementById('zoomOverlay');
        const zoomImg = document.getElementById('zoomImage');
        const closeBtn = document.getElementById('zoomClose');

        container.addEventListener('click', function () {
            zoomImg.src = img.src;
            overlay.classList.add('active');
        });

        closeBtn.addEventListener('click', function () {
            overlay.classList.remove('active');
        });

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) overlay.classList.remove('active');
        });
    }
});
