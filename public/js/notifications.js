const Toast = {
    show(message, type = 'info') {
        const existing = document.querySelector('.kb-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `kb-toast kb-toast-${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('kb-exit');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

const Notification = {
    show(message, type = 'success') {
        const existing = document.querySelector('.kb-cart-notification');
        if (existing) existing.remove();

        const notification = document.createElement('div');
        notification.className = `kb-cart-notification ${type}`;

        const icon = type === 'success' ? '✓' : '!';
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${icon}</span>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);
        setTimeout(() => {
            notification.classList.add('kb-exit');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
};
