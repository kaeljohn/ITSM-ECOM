@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Notifications — ' . $companyName)

@section('head')
<style>
    .notif-page {
        max-width: 860px;
        margin: 0 auto;
    }

    .notif-page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
        gap: 16px;
        flex-wrap: wrap;
    }

    .notif-page-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--c-text);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .notif-page-header h1 i {
        color: var(--c-primary);
    }

    .notif-page-header .notif-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .notif-page-header .btn-mark-all {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 8px;
        border: 1px solid var(--c-border);
        background: #fff;
        color: var(--c-text);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }

    .notif-page-header .btn-mark-all:hover {
        background: #f5f5f5;
        border-color: #d0d5dd;
    }

    .notif-page-header .btn-mark-all:disabled {
        opacity: 0.4;
        cursor: default;
    }

    .notif-page-header .unread-count {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
    }

    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .notif-list .notif-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 16px 20px;
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.15s;
    }

    .notif-list .notif-card:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        border-color: #d0d5dd;
    }

    .notif-list .notif-card.unread {
        background: #f0f5ff;
        border-color: #bfdbfe;
    }

    .notif-list .notif-card.unread:hover {
        background: #e8f0fe;
    }

    .notif-list .notif-card .n-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }

    .notif-list .notif-card .n-icon.blue { background: #eff6ff; color: #2563eb; }
    .notif-list .notif-card .n-icon.green { background: #f0fdf4; color: #16a34a; }
    .notif-list .notif-card .n-icon.amber { background: #fffbeb; color: #d97706; }
    .notif-list .notif-card .n-icon.red { background: #fef2f2; color: #dc2626; }
    .notif-list .notif-card .n-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .notif-list .notif-card .n-icon.teal { background: #f0fdfa; color: #14b8a6; }
    .notif-list .notif-card .n-icon.gray { background: #f3f4f6; color: #6b7280; }

    .notif-list .notif-card .n-content {
        flex: 1;
        min-width: 0;
    }

    .notif-list .notif-card .n-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 2px;
    }

    .notif-list .notif-card .n-body {
        font-size: 13px;
        color: var(--c-text-muted);
        line-height: 1.5;
        margin-bottom: 6px;
    }

    .notif-list .notif-card .n-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 11px;
        color: var(--c-text-muted);
    }

    .notif-list .notif-card .n-meta .type-tag {
        padding: 1px 8px;
        border-radius: 4px;
        background: #f3f4f6;
        color: #6b7280;
        font-weight: 600;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .notif-list .notif-card .n-read-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #3b82f6;
        flex-shrink: 0;
        margin-top: 4px;
    }

    .notif-list .notif-card.read .n-read-indicator {
        display: none;
    }

    .notif-list .empty-state {
        text-align: center;
        padding: 60px 24px;
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
    }

    .notif-list .empty-state i {
        font-size: 40px;
        color: #d1d5db;
        margin-bottom: 12px;
        display: block;
    }

    .notif-list .empty-state h3 {
        font-size: 16px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 6px;
    }

    .notif-list .empty-state p {
        font-size: 13px;
        color: var(--c-text-muted);
    }

    .notif-pagination {
        margin-top: 24px;
        display: flex;
        justify-content: center;
    }

    .notif-pagination .pagination-links {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .notif-pagination a, .notif-pagination span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        color: var(--c-text);
        border: 1px solid var(--c-border);
        background: #fff;
        transition: all 0.1s;
    }

    .notif-pagination a:hover {
        background: #f5f5f5;
        border-color: #d0d5dd;
    }

    .notif-pagination span.current {
        background: var(--c-primary);
        color: #fff;
        border-color: var(--c-primary);
    }

    @media (max-width: 640px) {
        .notif-page-header { flex-direction: column; align-items: flex-start; }
        .notif-list .notif-card { padding: 12px 14px; }
    }
</style>
@endsection

@section('content')
<div class="notif-page">
    <div class="notif-page-header">
        <h1>
            <i class="ph ph-bell"></i> Notifications
            <span class="unread-count" id="page-unread-count">
                <i class="ph ph-check-circle"></i> <span id="unread-num">{{ $unreadCount }}</span> unread
            </span>
        </h1>
        <div class="notif-actions">
            <button type="button" class="btn-mark-all" id="page-mark-all" {{ $unreadCount === 0 ? 'disabled' : '' }}>
                <i class="ph ph-check-circle"></i> Mark all as read
            </button>
        </div>
    </div>

    <div class="notif-list" id="notif-page-list">
        @forelse($notifications as $notification)
            <a href="{{ $notification->link ?: '#' }}" class="notif-card {{ $notification->is_read ? 'read' : 'unread' }}" data-id="{{ $notification->id }}">
                <div class="n-icon {{ $notification->icon_color }}"><i class="ph {{ $notification->icon }}"></i></div>
                <div class="n-content">
                    <div class="n-title">{{ $notification->title }}</div>
                    @if($notification->body)
                        <div class="n-body">{{ $notification->body }}</div>
                    @endif
                    <div class="n-meta">
                        <span class="type-tag">{{ $notification->type }}</span>
                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                        @if(!$notification->is_read)
                            <span style="color: #2563eb; font-weight: 600;">· New</span>
                        @endif
                    </div>
                </div>
                @if(!$notification->is_read)
                    <div class="n-read-indicator"></div>
                @endif
            </a>
        @empty
            <div class="empty-state">
                <i class="ph ph-bell-slash"></i>
                <h3>All caught up!</h3>
                <p>You have no notifications. Notifications will appear here when something needs your attention.</p>
            </div>
        @endforelse
    </div>

    @if($notifications instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="notif-pagination">
            <div class="pagination-links">
                {{ $notifications->links() }}
            </div>
        </div>
    @endif
</div>

<script>
(function() {
    const markAllBtn = document.getElementById('page-mark-all');
    const unreadNum = document.getElementById('unread-num');

    markAllBtn?.addEventListener('click', function() {
        if (markAllBtn.disabled) return;
        markAllBtn.disabled = true;
        markAllBtn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Marking...';

        fetch('{{ route("ecommerce.admin.crm.api.notifications.mark-all-read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(function(resp) {
            if (resp.success) {
                // Update all unread cards to read
                document.querySelectorAll('.notif-card.unread').forEach(function(el) {
                    el.classList.remove('unread');
                    el.classList.add('read');
                    var indicator = el.querySelector('.n-read-indicator');
                    if (indicator) indicator.remove();
                    var meta = el.querySelector('.n-meta');
                    if (meta) {
                        var newTag = meta.querySelector('span:last-child');
                        if (newTag && newTag.textContent.trim() === '· New') newTag.remove();
                    }
                });
                unreadNum.textContent = '0';
                markAllBtn.disabled = true;
                markAllBtn.innerHTML = '<i class="ph ph-check-circle"></i> Mark all as read';
            }
        })
        .catch(function() {
            markAllBtn.disabled = false;
            markAllBtn.innerHTML = '<i class="ph ph-check-circle"></i> Mark all as read';
        });
    });
})();
</script>
@endsection
