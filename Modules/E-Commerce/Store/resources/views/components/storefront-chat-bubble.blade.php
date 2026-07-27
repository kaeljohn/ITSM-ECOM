<style>
    /* ── Storefront Chat Bubble ── */
    .chat-bubble-btn {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 999;
        width: 56px;
        height: 56px;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--theme-primary, #ff6b00), var(--theme-accent, #f59e0b));
        border: none;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 20px rgba(var(--theme-primary-rgb, 255, 107, 0), 0.35);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .chat-bubble-btn:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 6px 28px rgba(var(--theme-primary-rgb, 255, 107, 0), 0.5);
    }
    .chat-bubble-btn .chat-unread-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 11px;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #050505;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    .chat-bubble-btn .chat-unread-badge.hidden {
        display: none;
    }

    /* Chat panel */
    .chat-bubble-panel {
        position: fixed;
        bottom: 92px;
        right: 24px;
        z-index: 998;
        width: 380px;
        max-width: calc(100vw - 48px);
        height: 520px;
        max-height: calc(100vh - 140px);
        background: #1a1a1a;
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 16px 48px rgba(0,0,0,0.5);
        transform: translateY(16px) scale(0.96);
        opacity: 0;
        pointer-events: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: bottom right;
        overflow: hidden;
    }
    .chat-bubble-panel.open {
        transform: translateY(0) scale(1);
        opacity: 1;
        pointer-events: auto;
    }

    .chat-panel-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, var(--theme-primary, #ff6b00), var(--theme-accent, #f59e0b));
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chat-panel-header h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chat-panel-header .close-bubble {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 0;
        background: rgba(255,255,255,0.15);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.1s;
    }
    .chat-panel-header .close-bubble:hover {
        background: rgba(255,255,255,0.25);
    }

    .chat-panel-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .chat-panel-messages .chat-msg {
        max-width: 88%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .chat-panel-messages .chat-msg.admin {
        align-self: flex-start;
        background: rgba(255,255,255,0.06);
        color: #e2e8f0;
        border-bottom-left-radius: 4px;
        border: 1px solid rgba(255,255,255,0.06);
    }
    .chat-panel-messages .chat-msg.customer {
        align-self: flex-end;
        background: linear-gradient(135deg, var(--theme-primary, #ff6b00), var(--theme-accent, #f59e0b));
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .chat-panel-messages .chat-msg .time {
        font-size: 10px;
        opacity: 0.5;
        margin-top: 4px;
        display: block;
    }
    .chat-panel-messages .chat-msg .sender-label {
        font-size: 10px;
        font-weight: 600;
        margin-bottom: 2px;
        display: block;
    }
    .chat-panel-messages .chat-msg.admin .sender-label {
        color: var(--theme-primary, #ff6b00);
    }

    .chat-empty-msg {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: rgba(255,255,255,0.3);
        text-align: center;
        padding: 24px;
    }
    .chat-empty-msg i {
        font-size: 36px;
        margin-bottom: 12px;
        opacity: 0.3;
    }
    .chat-empty-msg p {
        font-size: 13px;
        margin: 0;
    }

    .chat-panel-input {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid rgba(255,255,255,0.06);
        background: #121212;
    }
    .chat-panel-input input {
        flex: 1;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 20px;
        padding: 10px 16px;
        font-size: 13px;
        color: #fff;
        outline: none;
        transition: border-color 0.15s;
    }
    .chat-panel-input input:focus {
        border-color: var(--theme-primary, #ff6b00);
    }
    .chat-panel-input input::placeholder {
        color: rgba(255,255,255,0.25);
    }
    .chat-panel-input .send-bubble-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 0;
        background: linear-gradient(135deg, var(--theme-primary, #ff6b00), var(--theme-accent, #f59e0b));
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: opacity 0.15s;
        flex-shrink: 0;
    }
    .chat-panel-input .send-bubble-btn:disabled {
        opacity: 0.5;
        cursor: default;
    }
</style>

@auth('ecommerce')
<div class="storefront-chat" style="display: contents;">
    <!-- Chat Bubble Button -->
    <button class="chat-bubble-btn" id="chat-bubble-btn" onclick="toggleChatBubble()">
        <i class="ph ph-chats" id="bubble-icon"></i>
        <span class="chat-unread-badge hidden" id="bubble-unread-badge">0</span>
    </button>

    <!-- Chat Panel -->
    <div class="chat-bubble-panel" id="chat-bubble-panel">
        <div class="chat-panel-header">
            <h3><i class="ph ph-chats"></i> Chat with Us</h3>
            <button class="close-bubble" onclick="toggleChatBubble()"><i class="ph ph-x"></i></button>
        </div>
        <div class="chat-panel-messages" id="chat-panel-messages">
            <div class="chat-empty-msg">
                <i class="ph ph-chat-text"></i>
                <p>Ask us anything about your orders, products, or general inquiries!</p>
            </div>
        </div>
        <div class="chat-panel-input">
            <input type="text" id="bubble-message-input" placeholder="Type a message..." maxlength="5000" onkeydown="if(event.key==='Enter')sendBubbleMessage()">
            <button class="send-bubble-btn" id="bubble-send-btn" onclick="sendBubbleMessage()">
                <i class="ph ph-paper-plane-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    var isOpen = false;
    var lastPollTs = new Date().toISOString();
    var bubblePollTimer = null;
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '';

    // ── Dynamic positioning: move chat up when scroll-to-top appears ──
    function updateChatPosition() {
        var btn = document.getElementById('chat-bubble-btn');
        var panel = document.getElementById('chat-bubble-panel');
        var scrollY = window.scrollY || document.documentElement.scrollTop;
        var isAtTop = scrollY <= 300;
        if (btn) {
            btn.style.bottom = isAtTop ? '24px' : '90px';
        }
        if (panel) {
            panel.style.bottom = isAtTop ? '92px' : '158px';
        }
    }
    window.addEventListener('scroll', updateChatPosition, { passive: true });
    updateChatPosition();

    window.toggleChatBubble = function() {
        isOpen = !isOpen;
        var panel = document.getElementById('chat-bubble-panel');
        var icon = document.getElementById('bubble-icon');
        panel.classList.toggle('open', isOpen);
        icon.className = isOpen ? 'ph ph-x' : 'ph ph-chats';
        if (isOpen) {
            loadMessages();
            startBubblePolling();
        } else {
            stopBubblePolling();
        }
    };

    function loadMessages() {
        var area = document.getElementById('chat-panel-messages');
        fetch('{{ route("ecommerce.api.chat.messages", ["store" => $store]) }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (!resp.success || !resp.data || !resp.data.data || resp.data.data.length === 0) {
                area.innerHTML = '<div class="chat-empty-msg"><i class="ph ph-chat-text"></i><p>Ask us anything about your orders, products, or general inquiries!</p></div>';
                return;
            }
            renderBubbleMessages(resp.data.data, area);
            lastPollTs = new Date().toISOString();
        })
        .catch(function() {
            area.innerHTML = '<div class="chat-empty-msg"><i class="ph ph-warning-circle"></i><p>Could not load messages.</p></div>';
        });
    }

    function renderBubbleMessages(messages, area) {
        if (!messages || messages.length === 0) {
            area.innerHTML = '<div class="chat-empty-msg"><i class="ph ph-chat-text"></i><p>Ask us anything!</p></div>';
            return;
        }
        var html = '';
        messages.slice().reverse().forEach(function(msg) {
            var senderClass = msg.sender_type === 'admin' ? 'admin' : 'customer';
            var time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            html += '<div class="chat-msg ' + senderClass + '">';
            if (msg.sender_type === 'admin') {
                html += '<span class="sender-label">Support</span>';
            }
            html += escapeHtml(msg.message);
            html += '<span class="time">' + time + '</span></div>';
        });
        area.innerHTML = html;
        area.scrollTop = area.scrollHeight;
    }

    window.sendBubbleMessage = function() {
        var input = document.getElementById('bubble-message-input');
        var btn = document.getElementById('bubble-send-btn');
        var message = input.value.trim();
        if (!message) return;

        input.disabled = true;
        btn.disabled = true;

        fetch('{{ route("ecommerce.api.chat.send", ["store" => $store]) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (resp.success) {
                input.value = '';
                loadMessages();
            }
        })
        .catch(function() {})
        .finally(function() {
            input.disabled = false;
            btn.disabled = false;
            input.focus();
        });
    };

    function startBubblePolling() {
        stopBubblePolling();
        bubblePollTimer = setInterval(function() {
            if (!isOpen) return;
            fetch('{{ route("ecommerce.api.chat.poll", ["store" => $store]) }}?after=' + encodeURIComponent(lastPollTs), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success && resp.data && resp.data.length > 0) {
                    lastPollTs = new Date().toISOString();
                    var area = document.getElementById('chat-panel-messages');
                    var emptyState = area.querySelector('.chat-empty-msg');
                    if (emptyState) {
                        loadMessages();
                        return;
                    }
                    resp.data.forEach(function(msg) {
                        var senderClass = msg.sender_type === 'admin' ? 'admin' : 'customer';
                        var time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        var el = document.createElement('div');
                        el.className = 'chat-msg ' + senderClass;
                        if (msg.sender_type === 'admin') {
                            el.innerHTML = '<span class="sender-label">Support</span>' + escapeHtml(msg.message) + '<span class="time">' + time + '</span>';
                        } else {
                            el.innerHTML = escapeHtml(msg.message) + '<span class="time">' + time + '</span>';
                        }
                        area.appendChild(el);
                    });
                    area.scrollTop = area.scrollHeight;
                }
            })
            .catch(function() {});
        }, 5000);
    }

    function stopBubblePolling() {
        if (bubblePollTimer) { clearInterval(bubblePollTimer); bubblePollTimer = null; }
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Poll unread count for badge when closed
    setInterval(function() {
        if (isOpen) return;
        fetch('{{ route("ecommerce.api.chat.messages", ["store" => $store]) }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (resp.success && resp.data && resp.data.data) {
                var unread = resp.data.data.filter(function(m) {
                    return m.sender_type === 'admin' && !m.is_read;
                }).length;
                var badge = document.getElementById('bubble-unread-badge');
                if (badge) {
                    badge.textContent = unread;
                    badge.classList.toggle('hidden', unread === 0);
                }
            }
        })
        .catch(function() {});
    }, 15000);
})();
</script>
@endauth