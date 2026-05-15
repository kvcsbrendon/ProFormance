const kbChat = {
    isOpen: false,
    hasGreeted: false,

    toggle() {
        this.isOpen = !this.isOpen;
        const win = document.getElementById('kb-chat-window');
        const iconOpen = document.getElementById('kb-chat-icon-open');
        const iconClose = document.getElementById('kb-chat-icon-close');

        win.style.display = this.isOpen ? 'flex' : 'none';
        iconOpen.style.display = this.isOpen ? 'none' : 'block';
        iconClose.style.display = this.isOpen ? 'block' : 'none';

        if (this.isOpen && !this.hasGreeted) {
            this.hasGreeted = true;
            this.addBotMessage(
                "Hi! 👋 I'm the ProFormance assistant. I can help you find products, track orders, and answer questions.\n\nWhat can I help you with?",
                {
                    quick_replies: ['Browse products', 'Track my order', 'Shipping info', 'Contact support']
                }
            );
        }

        if (this.isOpen) {
            setTimeout(() => document.getElementById('kb-chat-input').focus(), 100);
        }
    },

    send() {
        const input = document.getElementById('kb-chat-input');
        const msg = input.value.trim();
        if (!msg) return;

        input.value = '';
        this.clearQuickReplies();
        this.addUserMessage(msg);
        this.showTyping();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        fetch('/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: msg }),
        })
        .then(res => res.json())
        .then(data => {
            this.hideTyping();
            this.addBotMessage(data.message, data);
        })
        .catch(err => {
            this.hideTyping();
            this.addBotMessage("Sorry, something went wrong. Please try again.");
            console.error('Chatbot error:', err);
        });
    },

    sendQuickReply(text) {
        document.getElementById('kb-chat-input').value = text;
        this.send();
    },

    addUserMessage(text) {
        const container = document.getElementById('kb-chat-messages');
        const div = document.createElement('div');
        div.className = 'kb-chat-msg kb-chat-msg-user';
        div.innerHTML = `<div class="kb-chat-bubble kb-chat-bubble-user">${this.escHtml(text)}</div>`;
        container.appendChild(div);
        this.scrollToBottom();
    },

    addBotMessage(text, data = {}) {
        const container = document.getElementById('kb-chat-messages');
        const div = document.createElement('div');
        div.className = 'kb-chat-msg kb-chat-msg-bot';

        let html = `<div class="kb-chat-bubble kb-chat-bubble-bot">${this.formatMessage(text)}`;

        // Add links
        if (data.links && data.links.length > 0) {
            html += '<div class="kb-chat-links">';
            data.links.forEach(link => {
                html += `<a href="${this.escHtml(link.url)}" class="kb-chat-link">${this.escHtml(link.label)} <i class="bi bi-arrow-right"></i></a>`;
            });
            html += '</div>';
        }

        html += '</div>';
        div.innerHTML = html;
        container.appendChild(div);
        this.scrollToBottom();

        // Quick replies
        if (data.quick_replies && data.quick_replies.length > 0) {
            this.showQuickReplies(data.quick_replies);
        }
    },

    showQuickReplies(replies) {
        const container = document.getElementById('kb-chat-quick-replies');
        container.innerHTML = '';
        replies.forEach(text => {
            const btn = document.createElement('button');
            btn.className = 'kb-chat-quick-btn';
            btn.textContent = text;
            btn.onclick = () => this.sendQuickReply(text);
            container.appendChild(btn);
        });
        this.scrollToBottom();
    },

    clearQuickReplies() {
        document.getElementById('kb-chat-quick-replies').innerHTML = '';
    },

    showTyping() {
        const container = document.getElementById('kb-chat-messages');
        const div = document.createElement('div');
        div.id = 'kb-chat-typing';
        div.className = 'kb-chat-msg kb-chat-msg-bot';
        div.innerHTML = `<div class="kb-chat-bubble kb-chat-bubble-bot kb-chat-typing">
            <span></span><span></span><span></span>
        </div>`;
        container.appendChild(div);
        this.scrollToBottom();
    },

    hideTyping() {
        const el = document.getElementById('kb-chat-typing');
        if (el) el.remove();
    },

    scrollToBottom() {
        const container = document.getElementById('kb-chat-messages');
        setTimeout(() => { container.scrollTop = container.scrollHeight; }, 50);
    },

    /**
     * Simple markdown-lite formatting:
     * **bold**, [text](url), \n → <br>, • for bullets
     */
    formatMessage(text) {
        let html = this.escHtml(text);
        // Bold
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        // Links [text](url)
        html = html.replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2" class="kb-chat-inline-link">$1</a>');
        // Line breaks
        html = html.replace(/\n/g, '<br>');
        return html;
    },

    escHtml(s) {
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }
};
