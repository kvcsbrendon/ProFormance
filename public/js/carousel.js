document.addEventListener('DOMContentLoaded', () => {

    // ══════════════════════════════════════
    //  Hero Slider
    // ══════════════════════════════════════
    const heroSlider = document.querySelector('.kb-hero-slider');

    if (heroSlider) {
        const slides  = Array.from(heroSlider.querySelectorAll('.kb-hero-slide'));
        const dots    = Array.from(document.querySelectorAll('.kb-hero-dot'));
        const prevBtn = document.querySelector('.kb-hero-nav--prev');
        const nextBtn = document.querySelector('.kb-hero-nav--next');

        let current = 0;
        let timer   = null;
        const INTERVAL = 7000;

        function setActive(index) {
            current = index;
            slides.forEach((s, i) => s.classList.toggle('is-active', i === current));
            dots.forEach((d, i)   => d.classList.toggle('is-active', i === current));
        }

        function next() { setActive((current + 1) % slides.length); }
        function prev() { setActive((current - 1 + slides.length) % slides.length); }

        function startAuto() { stopAuto(); timer = setInterval(next, INTERVAL); }
        function stopAuto()  { if (timer) clearInterval(timer); }

        prevBtn?.addEventListener('click', () => { prev(); startAuto(); });
        nextBtn?.addEventListener('click', () => { next(); startAuto(); });

        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => { setActive(i); startAuto(); });
        });

        heroSlider.addEventListener('mouseenter', stopAuto);
        heroSlider.addEventListener('mouseleave', startAuto);

        setActive(0);
        startAuto();
    }

    // ══════════════════════════════════════
    //  New Products Carousel
    // ══════════════════════════════════════
    const carouselContainer = document.querySelector('.kb-new-carousel-container');

    if (carouselContainer) {
        const track  = carouselContainer.querySelector('.kb-new-carousel-track');
        const cards  = track.querySelectorAll('.kb-new-card');
        const parent = carouselContainer.closest('.kb-new-products-inner');
        const prevBtn = parent?.querySelector('.kb-carousel-btn--prev');
        const nextBtn = parent?.querySelector('.kb-carousel-btn--next');
        const dotsContainer = carouselContainer.querySelector('.kb-carousel-dots');

        if (cards.length === 0) return;

        const cardWidth     = cards[0].offsetWidth + 24;
        const containerWidth = carouselContainer.offsetWidth;
        const cardsPerView  = Math.max(1, Math.floor(containerWidth / cardWidth));
        const totalSlides   = Math.ceil(cards.length / cardsPerView);
        let currentSlide    = 0;

        // Build dots
        if (dotsContainer && totalSlides > 1) {
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('button');
                dot.className = 'kb-carousel-dot';
                dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                if (i === 0) dot.classList.add('active');
                dot.addEventListener('click', () => goToSlide(i));
                dotsContainer.appendChild(dot);
            }
        }

        const dots = dotsContainer ? dotsContainer.querySelectorAll('.kb-carousel-dot') : [];

        function updateButtons() {
            if (prevBtn) prevBtn.disabled = currentSlide === 0;
            if (nextBtn) nextBtn.disabled = currentSlide === totalSlides - 1;
        }

        function updateDots() {
            dots.forEach((d, i) => d.classList.toggle('active', i === currentSlide));
        }

        function goToSlide(i) {
            currentSlide = Math.max(0, Math.min(i, totalSlides - 1));
            track.style.transform = `translateX(${-currentSlide * cardsPerView * cardWidth}px)`;
            updateButtons();
            updateDots();
        }

        prevBtn?.addEventListener('click', () => { if (currentSlide > 0) goToSlide(currentSlide - 1); });
        nextBtn?.addEventListener('click', () => { if (currentSlide < totalSlides - 1) goToSlide(currentSlide + 1); });

        // Touch / swipe
        let touchStartX = 0;
        track.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; }, { passive: true });
        track.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                diff > 0 ? goToSlide(currentSlide + 1) : goToSlide(currentSlide - 1);
            }
        }, { passive: true });

        updateButtons();
    }
});
