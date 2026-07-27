<style>
    /* ── Admin Chat Widget ── */
    .chat-widget-overlay {
        position: fixed;
        inset: 0;
        z-index: 999;
        background: rgba(0,0,0,0.3);
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
    .chat-widget-overlay.open {
        opacity: 1;
        pointer-events: auto;
    }

    .chat-widget-panel {
        position: fixed;
        top: 0;
        right: 0;
        width: 480px;
        max-width: 100vw;
        height: 100vh;
        z-index: 1000;
        background: #fff;
        box-shadow: -8px 0 30px rgba(0,0,0,0.12);
        transform: translateX(100%);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    .chat-widget-panel.open {
        transform: translateX(0);
    }

    .chat-widget-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .chat-widget-header h2 {
        font-size: 16px;
        font-weight: 600;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .chat-widget-header .close-btn {
        width: 32px;
        height: 32px;
        border-radius: 6px;
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.1s;
    }
    .chat-widget-header .close-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    /* Two-panel layout */
    .chat-widget-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }
    .chat-conversations {
        width: 200px;
        border-right: 1px solid #e2e8f0;
        overflow-y: auto;
        background: #fafbfc;
        flex-shrink: 0;
    }
    .chat-conversation-item {
        display: block;
        width: 100%;
        padding: 12px 14px;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        background: transparent;
        text-align: left;
        cursor: pointer;
        transition: background 0.1s;
    }
    .chat-conversation-item:hover {
        background: #f1f5f9;
    }
    .chat-conversation-item.active {
        background: #e8f0fe;
        border-left: 3px solid #1b6fc8;
    }
    .chat-conversation-item .name {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .chat-conversation-item .preview {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 2px;
    }
    .chat-conversation-item .unread-badge {
        display: inline-block;
        background: #ef4444;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 8px;
        margin-top: 4px;
    }

    .chat-window {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .chat-messages-area {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .chat-message {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .chat-message.admin {
        align-self: flex-end;
        background: #1b6fc8;
        color: #fff;
        border-bottom-right-radius: 4px;
    }
    .chat-message.customer {
        align-self: flex-start;
        background: #f1f5f9;
        color: #0f172a;
        border-bottom-left-radius: 4px;
    }
    .chat-message .time {
        font-size: 10px;
        opacity: 0.6;
        margin-top: 4px;
        display: block;
    }
    .chat-message.admin .time { color: rgba(255,255,255,0.7); }
    .chat-message.customer .time { color: #94a3b8; }

    .chat-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #94a3b8;
        text-align: center;
        padding: 20px;
    }
    .chat-empty-state i {
        font-size: 40px;
        margin-bottom: 12px;
        opacity: 0.4;
    }
    .chat-empty-state p {
        font-size: 13px;
        margin: 0;
    }

    .chat-input-area {
        display: flex;
        gap: 8px;
        padding: 12px 16px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .chat-input-area input {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 10px 16px;
        font-size: 13px;
        outline: none;
        transition: border-color 0.15s;
    }
    .chat-input-area input:focus {
        border-color: #1b6fc8;
    }
    .chat-input-area .send-btn {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: 0;
        background: #1b6fc8;
        color: #fff;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
        flex-shrink: 0;
    }
    .chat-input-area .send-btn:hover {
        background: #1b3a6b;
    }
    .chat-input-area .send-btn:disabled {
        opacity: 0.5;
        cursor: default;
    }

    /* Chat bell icon in top bar */
    .chat-toggle-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        border: 0;
        background: transparent;
        color: rgba(255,255,255,0.6);
        font-size: 18px;
        cursor: pointer;
        transition: all 0.15s;
        text-decoration: none;
    }
    .chat-toggle-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }
    .chat-toggle-btn .chat-unread-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 8px;
        background: #ef4444;
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid var(--c-header-bg, #132B52);
        line-height: 1;
    }
    .chat-toggle-btn .chat-unread-badge.hidden {
        display: none;
    }
</style>

<!-- Chat Toggle Button (placed in the admin top bar) -->
<button type="button" class="chat-toggle-btn" id="chat-toggle-btn" title="Live Chat" onclick="toggleChatWidget()">
    <i class="ph ph-chats"></i>
    <span class="chat-unread-badge hidden" id="chat-unread-badge">0</span>
</button>

<!-- Backdrop -->
<div class="chat-widget-overlay" id="chat-widget-overlay" onclick="toggleChatWidget()"></div>

<!-- Panel -->
<div class="chat-widget-panel" id="chat-widget-panel">
    <div class="chat-widget-header">
        <h2><i class="ph ph-chats" style="color: #1b6fc8;"></i> Live Chat</h2>
        <button type="button" class="close-btn" onclick="toggleChatWidget()"><i class="ph ph-x"></i></button>
    </div>
    <div class="chat-widget-body">
        <!-- Conversations List -->
        <div class="chat-conversations" id="chat-conversations-list">
            <div class="chat-empty-state" style="padding: 20px 14px;">
                <p>Loading...</p>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="chat-window">
            <div class="chat-messages-area" id="chat-messages-area">
                <div class="chat-empty-state" id="chat-no-selection">
                    <i class="ph ph-chats"></i>
                    <p>Select a customer to start chatting</p>
                </div>
            </div>
            <div class="chat-input-area" id="chat-input-area" style="display: none;">
                <input type="text" id="chat-message-input" placeholder="Type a message..." maxlength="5000" onkeydown="if(event.key==='Enter')sendChatMessage()">
                <button class="send-btn" id="chat-send-btn" onclick="sendChatMessage()">
                    <i class="ph ph-paper-plane-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    let selectedUserId = null;
    let lastPollTimestamp = new Date().toISOString();
    let pollTimer = null;
    let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '';

    // ── Toggle widget ──
    window.toggleChatWidget = function() {
        const panel = document.getElementById('chat-widget-panel');
        const overlay = document.getElementById('chat-widget-overlay');
        const isOpen = panel.classList.contains('open');
        panel.classList.toggle('open', !isOpen);
        overlay.classList.toggle('open', !isOpen);
        if (!isOpen) {
            fetchConversations();
            pollUnreadCount();
        }
    };

    // ── Fetch conversations list ──
    function fetchConversations() {
        const list = document.getElementById('chat-conversations-list');
        list.innerHTML = '<div class="chat-empty-state" style="padding: 20px 14px;"><p>Loading...</p></div>';

        var convUrl = window.location.origin + '/ecommerce-admin/crm/api/chat/conversations';
        fetch(convUrl, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (!resp.success || !resp.data || resp.data.length === 0) {
                list.innerHTML = '<div class="chat-empty-state" style="padding: 20px 14px;"><p>No conversations yet</p></div>';
                return;
            }
            let html = '';
            resp.data.forEach(function(conv) {
                const isActive = conv.user_id === selectedUserId;
                const preview = conv.last_message ? conv.last_message.substring(0, 40) + (conv.last_message.length > 40 ? '...' : '') : 'No messages';
                const badge = conv.unread_count > 0 ? '<span class="unread-badge">' + conv.unread_count + '</span>' : '';
                html += '<button class="chat-conversation-item' + (isActive ? ' active' : '') + '" onclick="selectConversation(' + conv.user_id + ')" data-user="' + conv.user_id + '">';
                html += '   <div class="name">' + escapeHtml(conv.customer_name) + '</div>';
                html += '   <div class="preview">' + escapeHtml(preview) + '</div>';
                html += '   ' + badge;
                html += '</button>';
            });
            list.innerHTML = html;
        })
        .catch(function() {
            list.innerHTML = '<div class="chat-empty-state" style="padding: 20px 14px;"><p>Could not load conversations</p></div>';
        });
    }

    // ── Select a conversation ──
    window.selectConversation = function(userId) {
        selectedUserId = userId;
        document.querySelectorAll('.chat-conversation-item').forEach(function(el) {
            el.classList.toggle('active', parseInt(el.getAttribute('data-user')) === userId);
        });

        var area = document.getElementById('chat-messages-area');
        if (!area) {
            console.error('[ChatWidget] Missing element: chat-messages-area');
            return;
        }
        area.innerHTML = '<div class="chat-empty-state"><i class="ph ph-spinner animate-spin"></i><p>Loading messages...</p></div>';

        // Show input area with null guard
        var inputArea = document.getElementById('chat-input-area');
        var noSelection = document.getElementById('chat-no-selection');
        if (inputArea) {
            inputArea.style.display = 'flex';
        } else {
            console.warn('[ChatWidget] Missing element: chat-input-area — widget may be in a partial-render context');
        }
        if (noSelection) {
            noSelection.style.display = 'none';
        }

        // Build the API URL dynamically to avoid route() parameter issues
        var baseUrl = window.location.origin;
        var messagesUrl = baseUrl + '/ecommerce-admin/crm/api/chat/' + userId;

        fetch(messagesUrl, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status + ': ' + r.statusText);
            }
            return r.json();
        })
        .then(function(resp) {
            if (!resp.success) {
                area.innerHTML = '<div class="chat-empty-state"><i class="ph ph-warning-circle"></i><p>Could not load messages</p></div>';
                console.warn('[ChatWidget] API returned success=false:', resp);
                return;
            }
            renderMessages(resp.data.data || resp.data, area);
            updateUnreadBadge();
            // Start polling AFTER initial messages load
            startPolling(userId);
        })
        .catch(function(err) {
            area.innerHTML = '<div class="chat-empty-state"><i class="ph ph-warning-circle"></i><p>Error loading messages</p></div>';
            console.error('[ChatWidget] Fetch error:', err);
        });
    };

    // ── Render messages ──
    function renderMessages(messages, area) {
        if (!messages || messages.length === 0) {
            area.innerHTML = '<div class="chat-empty-state"><i class="ph ph-chat-text"></i><p>No messages yet. Send the first message!</p></div>';
            return;
        }

        let html = '';
        messages.slice().reverse().forEach(function(msg) {
            const senderClass = msg.sender_type === 'admin' ? 'admin' : 'customer';
            const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            html += '<div class="chat-message ' + senderClass + '">';
            html += '   ' + escapeHtml(msg.message);
            html += '   <span class="time">' + time + '</span>';
            html += '</div>';
        });
        area.innerHTML = html;
        area.scrollTop = area.scrollHeight;
    }

    // ── Send message ──
    window.sendChatMessage = function() {
        if (!selectedUserId) return;
        const input = document.getElementById('chat-message-input');
        const btn = document.getElementById('chat-send-btn');
        const message = input.value.trim();
        if (!message) return;

        input.disabled = true;
        btn.disabled = true;

        var sendUrl = window.location.origin + '/ecommerce-admin/crm/api/chat/' + selectedUserId;

        fetch(sendUrl, {
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
                // Refresh messages
                selectConversation(selectedUserId);
            }
        })
        .catch(function(err) {
            console.error('[ChatWidget] Send error:', err);
        })
        .finally(function() {
            input.disabled = false;
            btn.disabled = false;
            input.focus();
        });
    };

    // ── Polling ──
    function startPolling(userId) {
        if (pollTimer) clearInterval(pollTimer);
        var pollBaseUrl = window.location.origin + '/ecommerce-admin/crm/api/chat/' + userId + '/poll';
        pollTimer = setInterval(function() {
            if (!selectedUserId) return;
            fetch(pollBaseUrl + '?after=' + encodeURIComponent(lastPollTimestamp), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(resp) {
                if (resp.success && resp.data && resp.data.length > 0) {
                    lastPollTimestamp = new Date().toISOString();
                    // Append new messages
                    const area = document.getElementById('chat-messages-area');
                    const emptyState = area.querySelector('.chat-empty-state');
                    if (emptyState) {
                        // No messages yet, reload all
                        selectConversation(selectedUserId);
                        return;
                    }
                    resp.data.forEach(function(msg) {
                        const senderClass = msg.sender_type === 'admin' ? 'admin' : 'customer';
                        const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        const el = document.createElement('div');
                        el.className = 'chat-message ' + senderClass;
                        el.innerHTML = escapeHtml(msg.message) + '<span class="time">' + time + '</span>';
                        area.appendChild(el);
                    });
                    area.scrollTop = area.scrollHeight;
                    updateUnreadBadge();
                    fetchConversations(); // refresh sidebar unread counts
                }
            })
            .catch(function(err) {
                console.error('[ChatWidget] Poll error:', err);
            });
        }, 5000);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    // ── Poll unread count for the toggle badge ──
    function pollUnreadCount() {
        var convUrl = window.location.origin + '/ecommerce-admin/crm/api/chat/conversations';
        fetch(convUrl, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (resp.success && resp.data) {
                let total = 0;
                resp.data.forEach(function(c) { total += c.unread_count || 0; });
                updateBadge(total);
            }
        })
        .catch(function(err) {
            console.error('[ChatWidget] Unread poll error:', err);
        });
    }

    function updateBadge(count) {
        const badge = document.getElementById('chat-unread-badge');
        if (!badge) return;
        badge.textContent = count;
        badge.classList.toggle('hidden', count === 0);
    }

    function updateUnreadBadge() {
        pollUnreadCount();
    }

    // ── Helpers ──
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ── Init ──
    pollUnreadCount();
    // Poll unread count every 15 seconds when widget is closed
    setInterval(function() {
        const panel = document.getElementById('chat-widget-panel');
        if (!panel.classList.contains('open')) {
            pollUnreadCount();
        }
    }, 15000);

})();
</script>
