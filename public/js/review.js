document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.btn-helpful').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const fetchUrl = this.dataset.url; 
            
            const countSpan = this.querySelector('.helpful-count');
            const icon = this.querySelector('i');

            fetch(fetchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 401) {
    				window.location.href = window.loginUrl;
    				return null;
				}
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    // Update the count number
                    countSpan.textContent = data.count;
                    
                    // Update sibling text ("X people found this helpful")
                    const textNode = this.nextElementSibling;
                    if (textNode) {
                        textNode.textContent = `${data.count} people found this helpful.`;
                    }

                    // Toggle filled/outline icon
                    if (data.action === 'added') {
                        icon.classList.replace('bi-hand-thumbs-up', 'bi-hand-thumbs-up-fill');
                    } else {
                        icon.classList.replace('bi-hand-thumbs-up-fill', 'bi-hand-thumbs-up');
                    }
                }
            })
            .catch(error => console.error('Error submitting helpful vote:', error));
        });
    });
});