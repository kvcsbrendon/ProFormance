document.addEventListener("DOMContentLoaded", function() {
    
    // --- Accordion Logic ---
    const accordionButtons = document.querySelectorAll(".info-accordion-button");
    
    accordionButtons.forEach(button => {
        button.addEventListener("click", function() {
            const content = this.nextElementSibling; 
            const icon = this.querySelector(".info-accordion-icon");
            const isExpanded = this.getAttribute("aria-expanded") === "true";

            if (isExpanded) {
                this.setAttribute("aria-expanded", "false");
                content.style.display = "none";
                if(icon) icon.textContent = "+";
            } else {
                this.setAttribute("aria-expanded", "true");
                content.style.display = "block";
                if(icon) icon.textContent = "-";
            }
        });
    });

    // --- Live Search Logic ---
    const searchInput = document.getElementById("infoSearch");
    const noResultsMsg = document.getElementById("noResultsMsg");
    const accordionItems = document.querySelectorAll(".info-accordion-item");

    if (searchInput) {
        searchInput.addEventListener("input", function() {
            const filter = this.value.toLowerCase();
            let hasVisibleItems = false;

            accordionItems.forEach(item => {
                const title = item.querySelector(".info-accordion-title").textContent.toLowerCase();
                const content = item.querySelector(".info-accordion-content").textContent.toLowerCase();

                if (title.includes(filter) || content.includes(filter)) {
                    item.style.display = "block";
                    hasVisibleItems = true;
                } else {
                    item.style.display = "none";
                }
            });

            if (noResultsMsg) {
                noResultsMsg.style.display = hasVisibleItems ? "none" : "block";
            }
        });
    }
});