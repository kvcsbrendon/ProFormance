
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.kb-admin-table').forEach(table => {
        const headers = table.querySelectorAll('thead th');
        headers.forEach((th, colIdx) => {
            // Skip empty headers (action columns)
            if (!th.textContent.trim()) return;

            th.style.cursor = 'pointer';
            th.style.userSelect = 'none';
            th.title = 'Click to sort';

            // Add sort indicator
            const indicator = document.createElement('span');
            indicator.className = 'kb-sort-indicator';
            indicator.style.marginLeft = '4px';
            indicator.style.fontSize = '0.7em';
            indicator.style.opacity = '0.4';
            indicator.textContent = '↕';
            th.appendChild(indicator);

            let ascending = true;

            th.addEventListener('click', function () {
                const tbody = table.querySelector('tbody');
                if (!tbody) return;

                const rows = Array.from(tbody.querySelectorAll('tr'));

                rows.sort((a, b) => {
                    const cellA = a.children[colIdx]?.textContent.trim() || '';
                    const cellB = b.children[colIdx]?.textContent.trim() || '';

                    // Try numeric sort first
                    const numA = parseFloat(cellA.replace(/[£$€,%]/g, ''));
                    const numB = parseFloat(cellB.replace(/[£$€,%]/g, ''));

                    if (!isNaN(numA) && !isNaN(numB)) {
                        return ascending ? numA - numB : numB - numA;
                    }

                    // Date sort (dd MMM yyyy)
                    const dateA = Date.parse(cellA);
                    const dateB = Date.parse(cellB);
                    if (!isNaN(dateA) && !isNaN(dateB)) {
                        return ascending ? dateA - dateB : dateB - dateA;
                    }

                    // String sort
                    return ascending
                        ? cellA.localeCompare(cellB, 'en', { numeric: true })
                        : cellB.localeCompare(cellA, 'en', { numeric: true });
                });

                // Clear all indicators
                headers.forEach(h => {
                    const ind = h.querySelector('.kb-sort-indicator');
                    if (ind) { ind.textContent = '↕'; ind.style.opacity = '0.4'; }
                });

                // Update this indicator
                indicator.textContent = ascending ? '↑' : '↓';
                indicator.style.opacity = '1';

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
                ascending = !ascending;
            });
        });
    });
});
