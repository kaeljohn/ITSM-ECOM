@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $type = request()->input('type', '');
    $status = request()->input('status', '');
    $search = request()->input('search', '');
    $from = request()->input('from', '');
    $to = request()->input('to', '');

    $deliveryRate = $totalSent > 0 ? round($totalDelivered / $totalSent * 100) : 0;
    $openRate = $totalDelivered > 0 ? round($totalOpened / $totalDelivered * 100) : 0;
    $clickRate = $totalOpened > 0 ? round($totalClicked / $totalOpened * 100) : 0;
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Campaign Log — CRM — ' . $companyName)

@section('head')
<style>
    .cmp { max-width: 1200px; margin: 0 auto; }

    /* KPI bar */
    .cmp-kpi-row {
        display: grid; grid-template-columns: repeat(5, 1fr);
        gap: 12px; margin-bottom: 24px;
    }
    .cmp-kpi {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 10px; padding: 14px 16px;
    }
    .cmp-kpi .kpi-label {
        font-size: 10px; font-weight: 600; color: var(--c-text-muted);
        text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;
    }
    .cmp-kpi .kpi-value {
        font-size: 22px; font-weight: 700; color: var(--c-text); line-height: 1.1;
    }
    .cmp-kpi .kpi-sub { font-size: 11px; color: var(--c-text-muted); margin-top: 2px; }

    /* Filters */
    .cmp-filters {
        display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
        align-items: flex-end;
    }
    .cmp-filters .fg { display: flex; flex-direction: column; gap: 3px; }
    .cmp-filters label {
        font-size: 10px; font-weight: 600; color: var(--c-text-muted);
        text-transform: uppercase; letter-spacing: 0.3px; margin: 0;
    }
    .cmp-filters select, .cmp-filters input {
        padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px;
        font-size: 13px; font-family: inherit; color: var(--c-text);
        background: #fff; min-width: 120px;
    }
    .cmp-filters input { min-width: 160px; }
    .cmp-filters select:focus, .cmp-filters input:focus {
        border-color: var(--c-primary); outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .cmp-filters .filter-btn {
        padding: 7px 16px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .cmp-filters .filter-btn:hover { background: #1a5aa8; }
    .cmp-filters .filter-clear {
        padding: 7px 14px; border: 1px solid #d1d5db; border-radius: 6px;
        background: #fff; color: var(--c-text-muted);
        font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .cmp-filters .filter-clear:hover { background: #f5f5f5; }

    /* Table */
    .cmp-table-wrap {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 12px; overflow: hidden;
    }
    .cmp-table { width: 100%; border-collapse: collapse; }
    .cmp-table thead th {
        padding: 10px 16px; font-size: 11px; font-weight: 600;
        color: var(--c-text-muted); text-transform: uppercase;
        letter-spacing: 0.3px; border-bottom: 1px solid var(--c-border);
        background: #fafbfc; text-align: left; white-space: nowrap;
    }
    .cmp-table tbody tr { transition: background 0.08s; }
    .cmp-table tbody tr:hover { background: #f8fafc; }
    .cmp-table tbody td {
        padding: 11px 16px; border-bottom: 1px solid #f1f3f5;
        font-size: 13px; color: var(--c-text); vertical-align: middle;
    }
    .cmp-table tbody tr:last-child td { border-bottom: none; }

    .cmp-table .customer-link {
        font-weight: 500; color: var(--c-text); text-decoration: none;
    }
    .cmp-table .customer-link:hover { color: var(--c-primary); }
    .cmp-table .customer-email { font-size: 11px; color: var(--c-text-muted); }

    .cmp-table .camp-subject { font-size: 13px; font-weight: 500; }
    .cmp-table .camp-name { font-size: 11px; color: var(--c-text-muted); }

    /* Status badges */
    .badge-cmp {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .badge-cmp .dot { width: 5px; height: 5px; border-radius: 50%; }
    .badge-cmp.queued { background: #f3f4f6; color: #6b7280; }
    .badge-cmp.queued .dot { background: #9ca3af; }
    .badge-cmp.sent { background: #eff6ff; color: #2563eb; }
    .badge-cmp.sent .dot { background: #3b82f6; }
    .badge-cmp.delivered { background: #f0fdf4; color: #16a34a; }
    .badge-cmp.delivered .dot { background: #22c55e; }
    .badge-cmp.opened { background: #f5f3ff; color: #7c3aed; }
    .badge-cmp.opened .dot { background: #8b5cf6; }
    .badge-cmp.clicked { background: #fdf4ff; color: #c026d3; }
    .badge-cmp.clicked .dot { background: #d946ef; }
    .badge-cmp.bounced { background: #fef2f2; color: #dc2626; }
    .badge-cmp.bounced .dot { background: #ef4444; }
    .badge-cmp.failed { background: #fef2f2; color: #dc2626; }
    .badge-cmp.failed .dot { background: #ef4444; }
    .badge-cmp.spam { background: #fff7ed; color: #ea580c; }
    .badge-cmp.spam .dot { background: #f97316; }

    .type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 4px;
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .type-badge.email { background: #eff6ff; color: #2563eb; }
    .type-badge.sms { background: #f0fdf4; color: #16a34a; }

    /* Tracking indicators */
    .track-icons {
        display: flex; gap: 6px; align-items: center;
    }
    .track-icons .ti {
        display: inline-flex; align-items: center; gap: 3px;
        font-size: 11px; font-weight: 600;
    }
    .track-icons .ti.done { color: #22c55e; }
    .track-icons .ti.pending { color: #d1d5db; }

    /* Pagination */
    .cmp-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; border-top: 1px solid var(--c-border);
        background: #fafbfc; font-size: 13px; color: var(--c-text-muted);
    }
    .cmp-pagination .pgl { display: flex; gap: 4px; }
    .cmp-pagination .pgl a, .cmp-pagination .pgl span {
        padding: 5px 10px; border-radius: 6px; text-decoration: none;
        font-size: 12px; font-weight: 600; color: var(--c-text);
    }
    .cmp-pagination .pgl a:hover { background: #e5e7eb; }
    .cmp-pagination .pgl span.active { background: var(--c-primary); color: #fff; }

    /* Modal */
    .modal-overlay {
        display: none; position: fixed; z-index: 1000;
        inset: 0; background: rgba(0,0,0,0.35);
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 14px; width: 620px; max-width: 92vw;
        max-height: 82vh; overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        animation: modalIn 0.15s ease-out;
    }
    @keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid var(--c-border);
        position: sticky; top: 0; background: #fff; z-index: 1;
    }
    .modal-header h3 { font-size: 16px; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 8px; }
    .modal-header .close-btn {
        width: 30px; height: 30px; border: 0; border-radius: 6px;
        background: transparent; font-size: 18px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--c-text-muted);
    }
    .modal-header .close-btn:hover { background: #f3f4f6; }
    .modal-body { padding: 16px 20px; }
    .modal-body .ev-item {
        padding: 10px 0; border-bottom: 1px solid #f3f4f6;
        display: flex; gap: 12px; align-items: flex-start;
    }
    .modal-body .ev-item:last-child { border-bottom: none; }
    .modal-body .ev-item .ev-icon {
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; flex-shrink: 0;
    }
    .modal-body .ev-item .ev-info { flex: 1; min-width: 0; }
    .modal-body .ev-item .ev-type {
        font-size: 13px; font-weight: 600; color: var(--c-text);
    }
    .modal-body .ev-item .ev-meta {
        font-size: 11px; color: var(--c-text-muted); margin-top: 2px;
        display: flex; flex-wrap: wrap; gap: 6px;
    }
    .modal-body .ev-item .ev-meta .ev-tag {
        font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 4px;
        background: #f3f4f6; color: #6b7280;
    }

    .cmp-empty {
        text-align: center; padding: 48px 24px; color: var(--c-text-muted);
    }
    .cmp-empty i { font-size: 40px; display: block; margin-bottom: 10px; color: #d1d5db; }

    @media (max-width: 900px) {
        .cmp-kpi-row { grid-template-columns: repeat(3, 1fr); }
        .cmp-filters { flex-direction: column; }
        .cmp-filters input { min-width: 0; width: 100%; }
    }
</style>
@endsection

@section('content')
<div class="cmp">

    {{-- ═══ PAGE HEADING ═══ --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:700; margin:0;">Campaign Log</h1>
            <p style="color:var(--c-text-muted); font-size:14px; margin-top:4px;">
                {{ $emailCount }} email &middot; {{ $smsCount }} SMS — track delivery, opens, and clicks
            </p>
        </div>
    </div>

    {{-- ═══ KPI BAR ═══ --}}
    <div class="cmp-kpi-row">
        <div class="cmp-kpi">
            <div class="kpi-label">Total Sent</div>
            <div class="kpi-value" style="color:var(--c-primary);">{{ $totalSent }}</div>
            <div class="kpi-sub">{{ $deliveryRate }}% delivery rate</div>
        </div>
        <div class="cmp-kpi">
            <div class="kpi-label">Delivered</div>
            <div class="kpi-value" style="color:#22c55e;">{{ $totalDelivered }}</div>
            <div class="kpi-sub">{{ $openRate }}% opened</div>
        </div>
        <div class="cmp-kpi">
            <div class="kpi-label">Opened</div>
            <div class="kpi-value" style="color:#8b5cf6;">{{ $totalOpened }}</div>
            <div class="kpi-sub">{{ $clickRate }}% clicked of opens</div>
        </div>
        <div class="cmp-kpi">
            <div class="kpi-label">Clicked</div>
            <div class="kpi-value" style="color:#c026d3;">{{ $totalClicked }}</div>
            <div class="kpi-sub">Engagement rate</div>
        </div>
        <div class="cmp-kpi">
            <div class="kpi-label">Failed / Bounced</div>
            <div class="kpi-value" style="color:#dc2626;">{{ $totalFailed }}</div>
            <div class="kpi-sub">{{ $totalSent > 0 ? round($totalFailed / $totalSent * 100, 1) : 0 }}% of sent</div>
        </div>
    </div>

    {{-- ═══ FILTERS ═══ --}}
    <form class="cmp-filters" method="GET" action="{{ route('ecommerce.admin.crm.campaigns') }}">
        <div class="fg">
            <label>Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Campaign, subject, or customer...">
        </div>
        <div class="fg">
            <label>Type</label>
            <select name="type">
                <option value="">All</option>
                <option value="email" {{ $type === 'email' ? 'selected' : '' }}>Email</option>
                <option value="sms" {{ $type === 'sms' ? 'selected' : '' }}>SMS</option>
            </select>
        </div>
        <div class="fg">
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <option value="queued" {{ $status === 'queued' ? 'selected' : '' }}>Queued</option>
                <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="delivered" {{ $status === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="opened" {{ $status === 'opened' ? 'selected' : '' }}>Opened</option>
                <option value="clicked" {{ $status === 'clicked' ? 'selected' : '' }}>Clicked</option>
                <option value="bounced" {{ $status === 'bounced' ? 'selected' : '' }}>Bounced</option>
                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div class="fg">
            <label>From</label>
            <input type="date" name="from" value="{{ $from }}">
        </div>
        <div class="fg">
            <label>To</label>
            <input type="date" name="to" value="{{ $to }}">
        </div>
        <button type="submit" class="filter-btn"><i class="ph ph-funnel"></i> Filter</button>
        @if(request()->hasAny(['search','type','status','from','to']))
            <a href="{{ route('ecommerce.admin.crm.campaigns') }}" class="filter-clear"><i class="ph ph-x"></i> Clear</a>
        @endif
    </form>

    {{-- ═══ CAMPAIGNS TABLE ═══ --}}
    <div class="cmp-table-wrap">
        @if($campaigns->count() > 0)
        <table class="cmp-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Campaign</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Tracking</th>
                    <th>Sent</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $log)
                <tr>
                    <td>
                        <div>
                            <a href="{{ route('ecommerce.admin.crm.customers.show', $log->customer_id) }}" class="customer-link">
                                {{ $log->customer->first_name ?? '—' }} {{ $log->customer->last_name ?? '' }}
                            </a>
                            <div class="customer-email">{{ $log->customer->email ?? '' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="camp-subject">{{ $log->subject ?? '—' }}</div>
                        <div class="camp-name">{{ $log->campaign_name }}</div>
                    </td>
                    <td>
                        <span class="type-badge {{ $log->campaign_type }}">
                            <i class="ph ph-{{ $log->campaign_type === 'email' ? 'envelope' : 'chat-text' }}"></i>
                            {{ $log->type_label }}
                        </span>
                    </td>
                    <td>
                        <span class="badge-cmp {{ $log->status }}">
                            <span class="dot"></span> {{ $log->status_label }}
                        </span>
                    </td>
                    <td>
                        <div class="track-icons">
                            <span class="ti {{ $log->has_been_opened ? 'done' : 'pending' }}">
                                <i class="ph ph-{{ $log->has_been_opened ? 'eye' : 'eye-slash' }}"></i>
                                {{ $log->has_been_opened ? 'Opened' : 'No open' }}
                            </span>
                            <span class="ti {{ $log->has_been_clicked ? 'done' : 'pending' }}">
                                <i class="ph ph-{{ $log->has_been_clicked ? 'cursor-click' : 'cursor' }}"></i>
                                {{ $log->has_been_clicked ? 'Clicked' : 'No click' }}
                            </span>
                        </div>
                    </td>
                    <td style="font-size:12px; color:var(--c-text-muted); white-space:nowrap;">
                        {{ $log->sent_at ? $log->sent_at->format('M j, Y') : $log->created_at->format('M j, Y') }}
                        <br><span style="font-size:11px;">{{ ($log->sent_at ?? $log->created_at)->diffForHumans() }}</span>
                    </td>
                    <td>
                        <button class="cmp-view-events" onclick="openEventModal({{ $log->id }}, '{{ addslashes($log->campaign_name) }}')"
                            style="padding:4px 10px; border:1px solid #e1e3e5; border-radius:6px; background:#fff; font-size:12px; font-weight:600; color:var(--c-text-muted); cursor:pointer; white-space:nowrap;">
                            <i class="ph ph-list-bullets"></i> {{ $log->events_count }} events
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="cmp-pagination">
            <span>Showing {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} of {{ $campaigns->total() }}</span>
            <div class="pgl">
                {{ $campaigns->links() }}
            </div>
        </div>
        @else
            <div class="cmp-empty">
                <i class="ph ph-envelope-open"></i>
                <p>No campaign logs found matching your filters.</p>
            </div>
        @endif
    </div>
</div>

{{-- ═══ EVENT DETAIL MODAL ═══ --}}
<div class="modal-overlay" id="ev-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="ph ph-list-bullets"></i> <span id="ev-modal-title">Events</span></h3>
            <button class="close-btn" onclick="closeEventModal()"><i class="ph ph-x"></i></button>
        </div>
        <div id="ev-modal-body" class="modal-body">
            <div style="text-align:center; padding:30px; color:var(--c-text-muted);">
                <i class="ph ph-spinner ph-spin" style="font-size:28px;"></i>
                <p style="margin-top:8px;">Loading events...</p>
            </div>
        </div>
    </div>
</div>

<script>
    var CSRF_TOKEN = document.querySelector('[name=_token]').value;

    function openEventModal(campaignId, campaignName) {
        document.getElementById('ev-modal-title').textContent = 'Events — ' + campaignName;
        document.getElementById('ev-modal').classList.add('open');
        document.getElementById('ev-modal-body').innerHTML =
            '<div style="text-align:center; padding:30px; color:var(--c-text-muted);">' +
            '<i class="ph ph-spinner ph-spin" style="font-size:28px;"></i>' +
            '<p style="margin-top:8px;">Loading events...</p></div>';

        fetch('{{ route("ecommerce.admin.crm.campaigns.events", "__ID__") }}'.replace('__ID__', campaignId))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var body = document.getElementById('ev-modal-body');
                if (data.success && data.data) {
                    var html = '';

                    // Campaign summary
                    var c = data.data.campaign;
                    html += '<div style="margin-bottom:16px; padding:12px; background:#f9fafb; border-radius:8px; display:flex; flex-wrap:wrap; gap:12px;">';
                    html += '<div><span style="font-size:11px; color:var(--c-text-muted);">Status</span><br><span class="badge-cmp ' + c.status + '"><span class="dot"></span> ' + c.status.charAt(0).toUpperCase() + c.status.slice(1) + '</span></div>';
                    if (c.sent_at) html += '<div><span style="font-size:11px; color:var(--c-text-muted);">Sent</span><br><span style="font-size:12px; font-weight:600;">' + timeAgo(c.sent_at) + '</span></div>';
                    if (c.delivered_at) html += '<div><span style="font-size:11px; color:var(--c-text-muted);">Delivered</span><br><span style="font-size:12px; font-weight:600;">' + timeAgo(c.delivered_at) + '</span></div>';
                    if (c.first_opened_at) html += '<div><span style="font-size:11px; color:var(--c-text-muted);">First Open</span><br><span style="font-size:12px; font-weight:600;">' + timeAgo(c.first_opened_at) + '</span></div>';
                    if (c.provider) html += '<div><span style="font-size:11px; color:var(--c-text-muted);">Provider</span><br><span style="font-size:12px; font-weight:600;">' + c.provider + '</span></div>';
                    html += '</div>';

                    // Events
                    if (data.data.events && data.data.events.length > 0) {
                        var iconMap = {
                            'delivered': { icon: 'check-circle', color: '#22c55e', bg: '#f0fdf4' },
                            'opened': { icon: 'eye', color: '#8b5cf6', bg: '#f5f3ff' },
                            'clicked': { icon: 'cursor-click', color: '#c026d3', bg: '#fdf4ff' },
                            'bounced': { icon: 'x-circle', color: '#dc2626', bg: '#fef2f2' },
                            'complained': { icon: 'warning', color: '#ea580c', bg: '#fff7ed' },
                            'unsubscribed': { icon: 'prohibit', color: '#dc2626', bg: '#fef2f2' },
                            'failed': { icon: 'x-circle', color: '#dc2626', bg: '#fef2f2' },
                        };
                        data.data.events.forEach(function(ev) {
                            var im = iconMap[ev.event_type] || { icon: 'circle', color: '#6b7280', bg: '#f3f4f6' };
                            html += '<div class="ev-item">';
                            html += '<div class="ev-icon" style="background:' + im.bg + ';color:' + im.color + ';">';
                            html += '<i class="ph ph-' + im.icon + '"></i></div>';
                            html += '<div class="ev-info">';
                            html += '<div class="ev-type">' + (ev.event_type_label || ev.event_type) + '</div>';
                            html += '<div class="ev-meta">';
                            html += '<span>' + timeAgo(ev.occurred_at) + '</span>';
                            if (ev.country || ev.city) html += '<span>&middot; ' + [ev.city, ev.country].filter(Boolean).join(', ') + '</span>';
                            if (ev.device_type) html += '<span class="ev-tag">' + ev.device_type + '</span>';
                            if (ev.ip_address) html += '<span>&middot; ' + ev.ip_address + '</span>';
                            if (ev.payload) html += '<span>&middot; ' + ev.payload + '</span>';
                            html += '</div></div></div>';
                        });
                    } else {
                        html += '<div style="text-align:center; padding:20px; color:var(--c-text-muted);">No events recorded for this campaign.</div>';
                    }
                    body.innerHTML = html;
                } else {
                    body.innerHTML = '<div style="text-align:center; padding:20px; color:#dc2626;">Failed to load events.</div>';
                }
            })
            .catch(function() {
                document.getElementById('ev-modal-body').innerHTML =
                    '<div style="text-align:center; padding:20px; color:#dc2626;">Network error loading events.</div>';
            });
    }

    function closeEventModal() {
        document.getElementById('ev-modal').classList.remove('open');
    }

    document.addEventListener('click', function(e) {
        if (e.target === document.getElementById('ev-modal')) closeEventModal();
    });

    function timeAgo(isoString) {
        if (!isoString) return '';
        var date = new Date(isoString);
        var now = new Date();
        var seconds = Math.floor((now - date) / 1000);
        if (seconds < 60) return 'just now';
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        var days = Math.floor(hours / 24);
        if (days < 30) return days + 'd ago';
        return date.toLocaleDateString();
    }
</script>
@endsection
