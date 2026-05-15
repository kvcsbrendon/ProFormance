document.addEventListener("DOMContentLoaded", function() {
    var btn = document.getElementById("backToTopBtn");
    if (!btn) return;

    window.addEventListener("scroll", function() {
        btn.classList.toggle("show", window.pageYOffset > 300);
    });

    btn.addEventListener("click", function(e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
});
