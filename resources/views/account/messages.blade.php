@extends('account.layout')

@section('account-content')
<div class="kb-account-section">
    <div class="kb-messages-header">
        <div>
            <h1 class="kb-account-title">Messages</h1>
            <p class="kb-account-subtitle">Notifications, order updates, and security alerts.</p>
        </div>
        @if($totalUnread > 0)
            <form method="POST" action="{{ route('account.messages.readAll') }}">
                @csrf
                @if($category !== 'all')
                    <input type="hidden" name="tab" value="{{ $category }}">
                @endif
                <button type="submit" class="kb-account-btn kb-account-btn-outline kb-account-btn-small">
                    <i class="bi bi-check2-all"></i> Mark all read
                </button>
            </form>
        @endif
    </div>
</div>

{{-- Category Tabs --}}
<div class="kb-messages-tabs">
    <a href="{{ route('account.messages') }}"
       class="kb-messages-tab {{ $category === 'all' ? 'active' : '' }}">
        All
        @if($totalUnread > 0)
            <span class="kb-messages-badge">{{ $totalUnread }}</span>
        @endif
    </a>

    @foreach(\App\Models\UserMessage::CATEGORIES as $key => $label)
        <a href="{{ route('account.messages', ['tab' => $key]) }}"
           class="kb-messages-tab {{ $category === $key ? 'active' : '' }}">
            @switch($key)
                @case('order')
                    <i class="bi bi-bag"></i>
                    @break
                @case('security')
                    <i class="bi bi-shield-lock"></i>
                    @break
                @case('promotional')
                    <i class="bi bi-tag"></i>
                    @break
                @case('system')
                    <i class="bi bi-gear"></i>
                    @break
            @endswitch
            {{ $label }}
            @if(($unreadCounts[$key] ?? 0) > 0)
                <span class="kb-messages-badge">{{ $unreadCounts[$key] }}</span>
            @endif
        </a>
    @endforeach
</div>

{{-- Messages List --}}
@if($messages->isEmpty())
    <div class="kb-account-empty">
        <i class="bi bi-envelope-open"></i>
        <p>No messages{{ $category !== 'all' ? ' in this category' : '' }}.</p>
    </div>
@else
    <div class="kb-messages-list">
        @foreach($messages as $message)
            <div class="kb-message-card {{ !$message->is_read ? 'kb-message-unread' : '' }}" id="msg-{{ $message->message_id }}">
                {{-- Delete X button --}}
                <button class="kb-message-delete" onclick="deleteMessage({{ $message->message_id }}, this)"
                        title="Delete message">
                    <i class="bi bi-x-lg"></i>
                </button>

                <div class="kb-message-icon">
                    @switch($message->category)
                        @case('order')
                            <i class="bi bi-bag-fill"></i>
                            @break
                        @case('security')
                            <i class="bi bi-shield-lock-fill"></i>
                            @break
                        @case('promotional')
                            <i class="bi bi-tag-fill"></i>
                            @break
                        @default
                            <i class="bi bi-bell-fill"></i>
                    @endswitch
                </div>

                <div class="kb-message-content">
                    <div class="kb-message-top">
                        <h3 class="kb-message-title">
                            @if(!$message->is_read)
                                <span class="kb-message-dot"></span>
                            @endif
                            {{ $message->title }}
                        </h3>
                        <time class="kb-message-time">{{ $message->created_at->diffForHumans() }}</time>
                    </div>

                    <p class="kb-message-body">{{ $message->body }}</p>

                    <div class="kb-message-actions">
                        @if($message->link_url)
                            <form method="POST" action="{{ route('account.messages.read', $message->message_id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="kb-account-btn kb-account-btn-small kb-account-btn-primary">
                                    {{ $message->link_label ?? 'View' }}
                                </button>
                            </form>
                        @endif

                        @if(!$message->is_read)
                            <form method="POST" action="{{ route('account.messages.read', $message->message_id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="kb-account-btn kb-account-btn-small kb-account-btn-outline">
                                    Mark as read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="kb-admin-pagination">{{ $messages->links() }}</div>
@endif

<script>
function deleteMessage(messageId, btn) {
    if (!confirm('Delete this message?')) return;

    const card = document.getElementById('msg-' + messageId);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    fetch('/account/messages/' + messageId, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && card) {
            card.style.transition = 'opacity 0.3s, transform 0.3s';
            card.style.opacity = '0';
            card.style.transform = 'translateX(20px)';
            setTimeout(() => card.remove(), 300);
        }
    })
    .catch(() => {
        // Fallback: submit a form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/account/messages/' + messageId;
        form.innerHTML = '<input type="hidden" name="_token" value="' + csrfToken + '"><input type="hidden" name="_method" value="DELETE">';
        document.body.appendChild(form);
        form.submit();
    });
}
</script>

<style>
    .kb-message-card { position: relative; }
    .kb-message-delete {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: none;
        background: transparent;
        color: var(--kb-secondary-font, #9ca3af);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.15s;
        z-index: 1;
    }
    .kb-message-delete:hover {
        background: #fee2e2;
        color: #dc2626;
    }
                   
    .kb-message-time {
    	padding-right: 3rem;
    }
</style>
@endsection
