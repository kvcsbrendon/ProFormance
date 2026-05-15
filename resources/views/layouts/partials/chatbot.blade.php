{{-- resources/views/layouts/partials/chatbot.blade.php --}}

<div class="kb-chat-widget" id="kb-chatbot">
    {{-- Chat toggle button --}}
    <button class="kb-chat-toggle" id="kb-chat-toggle" onclick="kbChat.toggle()" aria-label="Open chat">
        <i class="bi bi-chat-dots-fill" id="kb-chat-icon-open"></i>
        <i class="bi bi-x-lg" id="kb-chat-icon-close" style="display:none;"></i>
    </button>

    {{-- Chat window --}}
    <div class="kb-chat-window" id="kb-chat-window" style="display:none;">
        <div class="kb-chat-header">
            <div class="kb-chat-header-info">
                <div class="kb-chat-avatar">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <span class="kb-chat-header-name">ProFormance Assistant</span>
                    <span class="kb-chat-header-status"><span class="kb-chat-status-dot"></span> Online</span>
                </div>
            </div>
            <button class="kb-chat-header-close" onclick="kbChat.toggle()" aria-label="Close chat">
                <i class="bi bi-dash-lg"></i>
            </button>
        </div>

        <div class="kb-chat-messages" id="kb-chat-messages">
            {{-- Messages inserted here by JS --}}
        </div>

        <div class="kb-chat-quick-replies" id="kb-chat-quick-replies"></div>

        <div class="kb-chat-input-area">
            <input type="text" class="kb-chat-input" id="kb-chat-input"
                   placeholder="Type a message…" maxlength="500"
                   onkeydown="if(event.key==='Enter')kbChat.send()">
            <button class="kb-chat-send" onclick="kbChat.send()" aria-label="Send">
                <i class="bi bi-send-fill"></i>
            </button>
        </div>
    </div>
</div>
