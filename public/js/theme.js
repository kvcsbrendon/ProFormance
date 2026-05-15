// ── Inline flash prevention (runs immediately) ──
(function () {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.setAttribute('data-theme', saved);
    }
})();

// ── Toggle button (runs after DOM ready) ──
document.addEventListener('DOMContentLoaded', () => {
    const html      = document.documentElement;
    const toggleBtn = document.getElementById('theme-toggle');
    const icon      = document.getElementById('theme-icon');

    if (!toggleBtn || !icon) return;

    function updateIcon(theme) {
        icon.classList.toggle('bi-sun-fill',  theme === 'dark');
        icon.classList.toggle('bi-moon-fill', theme !== 'dark');
    }

    function setTheme(theme) {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        updateIcon(theme);
    }

    // Apply saved or system preference
    const saved = localStorage.getItem('theme');
    if (saved) {
        setTheme(saved);
    } else {
        setTheme(window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    }

    toggleBtn.addEventListener('click', () => {
        setTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
});
