<style>
    /* ── Notification Bell ── */
    .notif-bell-btn {
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
        text-decoration: none;
        transition: all 0.15s;
    }
    .notif-bell-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }
    .notif-bell-btn .badge-count {
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
    .notif-bell-btn .badge-count.hidden-badge {
        display: none;
    }

    /* Dropdown */
    .notif-dropdown {
        visibility: hidden;
        position: absolute;
        z-index: 100;
        top: 100%;
        right: 0;
        margin-top: 6px;
        width: 360px;
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 12px 32px rgba(0,0,0,0.15);
        opacity: 0;
        transform: translateY(-6px) scale(0.98);
        transition: all 0.16s ease;
        border: 1px solid var(--c-border);
        overflow: hidden;
        transform-origin: top right;
    }
    .notif-dropdown.open {
        visibility: visible;
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .notif-dd-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 16px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .notif-dd-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        margin: 0;
    }
    .notif-dd-header .mark-all-btn {
        font-size: 11px;
        font-weight: 600;
        color: var(--c-primary);
        background: none;
        border: 0;
        cursor: pointer;
        padding: 0;
        text-decoration: none;
    }
    .notif-dd-header .mark-all-btn:hover {
        text-decoration: underline;
    }
    .notif-dd-header .mark-all-btn:disabled {
        opacity: 0.4;
        cursor: default;
        text-decoration: none;
    }

    .notif-dd-body {
        max-height: 380px;
        overflow-y: auto;
    }
    .notif-dd-body .empty-state {
        padding: 40px 24px;
        text-align: center;
    }
    .notif-dd-body .empty-state i {
        font-size: 32px;
        color: #d1d5db;
        margin-bottom: 8px;
        display: block;
    }
    .notif-dd-body .empty-state p {
        font-size: 13px;
        color: var(--c-text-muted);
        margin: 0;
    }

    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        text-decoration: none;
        transition: background 0.1s;
        cursor: pointer;
    }
    .notif-item:hover {
        background: #f9fafb;
    }
    .notif-item.unread {
        background: #f0f5ff;
    }
    .notif-item.unread:hover {
        background: #e8f0fe;
    }
    .notif-item:last-child {
        border-bottom: none;
    }

    .notif-item .notif-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .notif-item .notif-icon.blue { background: #eff6ff; color: #2563eb; }
    .notif-item .notif-icon.green { background: #f0fdf4; color: #16a34a; }
    .notif-item .notif-icon.amber { background: #fffbeb; color: #d97706; }
    .notif-item .notif-icon.red { background: #fef2f2; color: #dc2626; }
    .notif-item .notif-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .notif-item .notif-icon.teal { background: #f0fdfa; color: #14b8a6; }
    .notif-item .notif-icon.gray { background: #f3f4f6; color: #6b7280; }

    .notif-item .notif-content {
        flex: 1;
        min-width: 0;
    }
    .notif-item .notif-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
        line-height: 1.3;
        margin-bottom: 2px;
    }
    .notif-item .notif-body {
        font-size: 12px;
        color: var(--c-text-muted);
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .notif-item .notif-time {
        font-size: 10px;
        color: var(--c-text-muted);
        margin-top: 4px;
    }
    .notif-item .unread-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #3b82f6;
        flex-shrink: 0;
        margin-top: 4px;
    }

    .notif-dd-footer {
        padding: 10px 16px;
        border-top: 1px solid var(--c-border);
        background: #fafbfc;
        text-align: center;
    }
    .notif-dd-footer a {
        font-size: 12px;
        font-weight: 600;
        color: var(--c-primary);
        text-decoration: none;
    }
    .notif-dd-footer a:hover {
        text-decoration: underline;
    }

    /* Backdrop overlay */
    .notif-backdrop {
        position: fixed;
        inset: 0;
        z-index: 99;
        background: transparent;
        display: none;
    }
    .notif-backdrop.visible {
        display: block;
    }

    /* Pulse animation for new notifications */
    @keyframes notif-pulse-admin {
        0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5), 0 0 0 0 rgba(37, 99, 235, 0.2); }
        40% { box-shadow: 0 0 0 8px rgba(37, 99, 235, 0), 0 0 0 16px rgba(37, 99, 235, 0); }
        100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0), 0 0 0 0 rgba(37, 99, 235, 0); }
    }
    .admin-notif-pulse {
        animation: notif-pulse-admin 1.4s ease-out 2;
        background: rgba(37, 99, 235, 0.06) !important;
        color: #2563eb !important;
    }
</style>

<div class="notif-bell-wrap" style="position: relative;">
    <button type="button" class="notif-bell-btn" id="notif-bell-btn" aria-label="Notifications" title="Notifications">
        <i class="ph ph-bell"></i>
        <span class="badge-count hidden-badge" id="notif-badge-count">0</span>
    </button>

    <!-- Backdrop (for closing) -->
    <div class="notif-backdrop" id="notif-backdrop"></div>

    <!-- Dropdown -->
    <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-dd-header">
            <h3><i class="ph ph-bell" style="font-size: 15px; margin-right: 4px;"></i> Notifications</h3>
            <button type="button" class="mark-all-btn" id="notif-mark-all" disabled>Mark all as read</button>
        </div>
        <div class="notif-dd-body" id="notif-dd-body">
            <div class="empty-state">
                <i class="ph ph-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        </div>
        <div class="notif-dd-footer">
            <a href="{{ route('ecommerce.admin.crm.notifications') }}">View all notifications <i class="ph ph-arrow-right" style="font-size: 11px;"></i></a>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const btn = document.getElementById('notif-bell-btn');
    const dropdown = document.getElementById('notif-dropdown');
    const backdrop = document.getElementById('notif-backdrop');
    const badge = document.getElementById('notif-badge-count');
    const body = document.getElementById('notif-dd-body');
    const markAllBtn = document.getElementById('notif-mark-all');

    let isOpen = false;
    let previousCount = -1;

    function triggerPulse() {
        var bellBtn = document.getElementById('notif-bell-btn');
        if (!bellBtn) return;
        bellBtn.classList.add('admin-notif-pulse');
        bellBtn.addEventListener('animationend', function() {
            bellBtn.classList.remove('admin-notif-pulse');
        }, { once: true });
    }

    // ── Toggle ──
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        dropdown.classList.toggle('open', isOpen);
        backdrop.classList.toggle('visible', isOpen);
        if (isOpen) {
            // Dismiss pulse when user acknowledges by opening dropdown
            btn.classList.remove('admin-notif-pulse');
            fetchNotifications();
        }
    });

    backdrop.addEventListener('click', function() {
        closeDropdown();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isOpen) closeDropdown();
    });

    function closeDropdown() {
        isOpen = false;
        dropdown.classList.remove('open');
        backdrop.classList.remove('visible');
    }

    // ── Mark all as read ──
    markAllBtn.addEventListener('click', function() {
        if (markAllBtn.disabled) return;
        markAllBtn.disabled = true;
        fetch('{{ route("ecommerce.admin.crm.api.notifications.mark-all-read") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(function(resp) {
            if (resp.success) {
                fetchUnreadCount();
                // Visually mark all as read
                document.querySelectorAll('.notif-item.unread').forEach(function(el) {
                    el.classList.remove('unread');
                    var dot = el.querySelector('.unread-dot');
                    if (dot) dot.remove();
                });
            }
        })
        .catch(function() { markAllBtn.disabled = false; });
    });

    // ── Fetch notifications list ──
    function fetchNotifications() {
        fetch('{{ route("ecommerce.admin.crm.api.notifications.unread") }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(resp) {
            if (!resp || !resp.success) { body.innerHTML = '<div class="empty-state"><i class="ph ph-bell-slash"></i><p>No notifications yet</p></div>'; return; }
            var data = resp.data;
            if (!data.notifications || data.notifications.length === 0) {
                body.innerHTML = '<div class="empty-state"><i class="ph ph-bell-slash"></i><p>All caught up!</p></div>';
                markAllBtn.disabled = true;
                return;
            }
            var html = '';
            data.notifications.forEach(function(n) {
                var iconColor = n.icon_color || 'blue';
                var isUnread = !n.is_read;
                html += '<a class="notif-item' + (isUnread ? ' unread' : '') + '" href="' + (n.link || '#') + '" data-id="' + n.id + '">';
                html += '   <div class="notif-icon ' + iconColor + '"><i class="ph ' + (n.icon || 'ph-bell') + '"></i></div>';
                html += '   <div class="notif-content">';
                html += '       <div class="notif-title">' + escapeHtml(n.title) + '</div>';
                if (n.body) html += '       <div class="notif-body">' + escapeHtml(n.body) + '</div>';
                html += '       <div class="notif-time">' + timeAgo(n.created_at) + '</div>';
                html += '   </div>';
                if (isUnread) html += '   <div class="unread-dot"></div>';
                html += '</a>';
            });
            body.innerHTML = html;
            markAllBtn.disabled = false;

            // Click handler for individual notifications
            body.querySelectorAll('.notif-item').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    var id = el.getAttribute('data-id');
                    if (id) {
                        fetch('{{ route("ecommerce.admin.crm.api.notifications.mark-read", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', id), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '', 'Accept': 'application/json' }
                        }).catch(function() {});
                    }
                    // Let the link navigation proceed naturally
                });
            });
        })
        .catch(function() {
            body.innerHTML = '<div class="empty-state"><i class="ph ph-warning-circle"></i><p>Could not load notifications.</p></div>';
        });
    }

    // ── Visibility-aware polling (lightweight, no open connections) ──
    let pollTimer = null;
    let isPageVisible = true;

    function startPolling() {
        stopPolling();
        fetchUnreadCount();
        pollTimer = setInterval(fetchUnreadCount, 30000);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    // Pause/resume when tab visibility changes (saves server resources)
    document.addEventListener('visibilitychange', function() {
        isPageVisible = !document.hidden;
        if (isPageVisible) {
            startPolling(); // Refresh immediately when tab is visible again
        } else {
            stopPolling();
        }
    });

    // ── Fetch unread count ──
    function fetchUnreadCount() {
        fetch('{{ route("ecommerce.admin.crm.api.notifications.unread") }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.ok ? r.json() : null; })
        .then(function(resp) {
            if (!resp || !resp.success) return;
            var count = resp.data.count || 0;

            // Detect new notification and trigger pulse
            if (previousCount >= 0 && count > previousCount) {
                triggerPulse();
            }
            previousCount = count;

            badge.textContent = count;
            badge.classList.toggle('hidden-badge', count === 0);
            // If dropdown is open, refresh notifications
            if (isOpen && resp.data.notifications) {
                renderNotifications(resp.data.notifications);
            }
        })
        .catch(function() {});
    }

    // ── Render notifications in dropdown ──
    function renderNotifications(notifications) {
        if (!notifications || notifications.length === 0) {
            body.innerHTML = '<div class="empty-state"><i class="ph ph-bell-slash"></i><p>All caught up!</p></div>';
            markAllBtn.disabled = true;
            return;
        }
        var html = '';
        notifications.forEach(function(n) {
            var iconColor = n.icon_color || 'blue';
            var isUnread = !n.is_read;
            html += '<a class="notif-item' + (isUnread ? ' unread' : '') + '" href="' + (n.link || '#') + '" data-id="' + n.id + '">';
            html += '   <div class="notif-icon ' + iconColor + '"><i class="ph ' + (n.icon || 'ph-bell') + '"></i></div>';
            html += '   <div class="notif-content">';
            html += '       <div class="notif-title">' + escapeHtml(n.title) + '</div>';
            if (n.body) html += '       <div class="notif-body">' + escapeHtml(n.body) + '</div>';
            html += '       <div class="notif-time">' + timeAgo(n.created_at) + '</div>';
            html += '   </div>';
            if (isUnread) html += '   <div class="unread-dot"></div>';
            html += '</a>';
        });
        body.innerHTML = html;
        markAllBtn.disabled = false;

        // Click handler for individual notifications
        body.querySelectorAll('.notif-item').forEach(function(el) {
            el.addEventListener('click', function(e) {
                var id = el.getAttribute('data-id');
                if (id) {
                    fetch('{{ route("ecommerce.admin.crm.api.notifications.mark-read", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '', 'Accept': 'application/json' }
                    }).catch(function() {});
                }
            });
        });
    }

    // ── Helpers ──
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '';
        var now = new Date();
        var date = new Date(dateStr);
        var diffMs = now - date;
        var diffSec = Math.floor(diffMs / 1000);
        if (diffSec < 60) return 'just now';
        var diffMin = Math.floor(diffSec / 60);
        if (diffMin < 60) return diffMin + 'm ago';
        var diffHr = Math.floor(diffMin / 60);
        if (diffHr < 24) return diffHr + 'h ago';
        var diffDay = Math.floor(diffHr / 24);
        if (diffDay < 7) return diffDay + 'd ago';
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    // ── Init ──
    startPolling();
})();
</script>
