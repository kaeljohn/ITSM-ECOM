@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $adminName = trim(($crmAdmin?->first_name ?? '') . ' ' . ($crmAdmin?->last_name ?? '')) ?: 'Admin';
    $phTime = now()->timezone('Asia/Manila');
    $phHour = (int) $phTime->format('H');
    $greeting = $phHour < 12 ? 'morning' : ($phHour < 18 ? 'afternoon' : 'evening');

    // Compute repeat rate
    $repeatRate = $totalCustomers > 0 ? round($repeatCount / $totalCustomers * 100) : 0;

    // Churn percentages
    $churnTotal = $churnHigh + $churnMedium + $churnLow;
    $churnHighPct = $churnTotal > 0 ? round($churnHigh / $churnTotal * 100) : 0;
    $churnMedPct = $churnTotal > 0 ? round($churnMedium / $churnTotal * 100) : 0;
    $churnLowPct = $churnTotal > 0 ? round($churnLow / $churnTotal * 100) : 0;

    // Engagement percentages
    $engTotal = $highEngagement + $mediumEngagement + $lowEngagement;
    $highEngPct = $engTotal > 0 ? round($highEngagement / $engTotal * 100) : 0;
    $medEngPct = $engTotal > 0 ? round($mediumEngagement / $engTotal * 100) : 0;
    $lowEngPct = $engTotal > 0 ? round($lowEngagement / $engTotal * 100) : 0;
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'CRM Dashboard — ' . $companyName)

@section('head')
<style>
    /* ── CRM Dashboard v2 ── */
    .crm-dash {
        max-width: 1320px;
        margin: 0 auto;
    }

    /* Welcome strip */
    .crm-welcome {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 28px;
        gap: 20px;
        flex-wrap: wrap;
    }
    .crm-welcome-left h1 {
        font-size: 26px;
        font-weight: 700;
        color: var(--c-text);
        margin: 0;
    }
    .crm-welcome-left h1 span { color: var(--c-primary); }
    .crm-welcome-left p {
        margin: 6px 0 0;
        font-size: 14px;
        color: var(--c-text-muted);
    }
    .crm-welcome-right {
        text-align: right;
        flex-shrink: 0;
    }
    .crm-welcome-right .date {
        font-weight: 600;
        font-size: 15px;
        color: var(--c-text);
    }
    .crm-welcome-right .time {
        font-size: 13px;
        color: var(--c-text-muted);
        margin-top: 2px;
    }
    .crm-welcome-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 20px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
        margin-top: 8px;
    }

    /* ── KPI SUPER GRID ── */
    .crm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }
    .crm-kpi-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 18px 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .crm-kpi-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.04);
        transform: translateY(-2px);
        border-color: #d0d5dd;
    }
    .crm-kpi-card .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .crm-kpi-card .kpi-info { flex: 1; min-width: 0; }
    .crm-kpi-card .kpi-label {
        font-size: 11px;
        font-weight: 600;
        color: var(--c-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .crm-kpi-card .kpi-value {
        font-size: 26px;
        font-weight: 700;
        color: var(--c-text);
        line-height: 1.1;
    }
    .crm-kpi-card .kpi-sub {
        font-size: 12px;
        color: var(--c-text-muted);
        margin-top: 4px;
    }
    .crm-kpi-card .kpi-trend {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 4px;
    }
    .kpi-trend.up { color: #16a34a; }
    .kpi-trend.down { color: #dc2626; }
    .kpi-trend.neutral { color: #f59e0b; }

    .kpi-icon.blue { background: #eff6ff; color: #2563eb; }
    .kpi-icon.green { background: #f0fdf4; color: #16a34a; }
    .kpi-icon.amber { background: #fffbeb; color: #d97706; }
    .kpi-icon.red { background: #fef2f2; color: #dc2626; }
    .kpi-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .kpi-icon.teal { background: #f0fdfa; color: #14b8a6; }
    .kpi-icon.indigo { background: #eef2ff; color: #4f46e5; }

    /* ── ALERTS BAR ── */
    .crm-alerts {
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    .crm-alert-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 18px;
        border-radius: 10px;
        border: 1px solid var(--c-border);
        background: #fff;
        text-decoration: none;
        flex: 1;
        min-width: 180px;
        transition: all 0.15s;
    }
    .crm-alert-card:hover {
        box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        transform: translateY(-1px);
    }
    .crm-alert-card .alert-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .crm-alert-card .alert-icon.red { background: #fef2f2; color: #dc2626; }
    .crm-alert-card .alert-icon.amber { background: #fffbeb; color: #d97706; }
    .crm-alert-card .alert-icon.blue { background: #eff6ff; color: #2563eb; }
    .crm-alert-card .alert-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .crm-alert-card .alert-body { flex: 1; }
    .crm-alert-card .alert-count {
        font-size: 18px;
        font-weight: 700;
        color: var(--c-text);
        line-height: 1.1;
    }
    .crm-alert-card .alert-label {
        font-size: 12px;
        color: var(--c-text-muted);
        margin-top: 1px;
    }
    .crm-alert-card .alert-arrow {
        color: var(--c-text-muted);
        font-size: 16px;
        transition: transform 0.15s;
    }
    .crm-alert-card:hover .alert-arrow {
        transform: translateX(3px);
        color: var(--c-primary);
    }

    /* ── DISTRIBUTION BARS (churn + engagement) ── */
    .crm-distro-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        margin-bottom: 28px;
    }
    .crm-distro-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 18px 20px;
        transition: box-shadow 0.15s;
    }
    .crm-distro-card:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,0.03);
    }
    .crm-distro-card .distro-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .crm-distro-card .distro-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .crm-distro-card .distro-title i { font-size: 16px; }
    .crm-distro-card .distro-value {
        font-size: 22px;
        font-weight: 700;
        color: var(--c-text);
    }
    .crm-distro-bar {
        display: flex;
        height: 10px;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 10px;
        background: #f3f4f6;
    }
    .crm-distro-bar .seg {
        transition: width 0.6s ease;
        height: 100%;
    }
    .crm-distro-bar .seg.high { background: #ef4444; }
    .crm-distro-bar .seg.medium { background: #f59e0b; }
    .crm-distro-bar .seg.low { background: #22c55e; }
    .crm-distro-bar .seg.high-eng { background: #22c55e; }
    .crm-distro-bar .seg.med-eng { background: #f59e0b; }
    .crm-distro-bar .seg.low-eng { background: #ef4444; }
    .crm-distro-legend {
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
    }
    .crm-distro-legend .legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: var(--c-text-muted);
    }
    .crm-distro-legend .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── MIDDLE ROW: quick actions ── */
    .crm-actions-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }
    .crm-action-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 18px 20px;
        text-decoration: none;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }
    .crm-action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.05);
        border-color: #d0d5dd;
    }
    .crm-action-card .act-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 14px;
    }
    .crm-action-card .act-icon.blue { background: #eff6ff; color: #2563eb; }
    .crm-action-card .act-icon.green { background: #f0fdf4; color: #16a34a; }
    .crm-action-card .act-icon.amber { background: #fffbeb; color: #d97706; }
    .crm-action-card .act-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .crm-action-card .act-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        margin-bottom: 4px;
    }
    .crm-action-card .act-desc {
        font-size: 12px;
        color: var(--c-text-muted);
        line-height: 1.4;
    }
    .crm-action-card .act-footer {
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 600;
        color: var(--c-primary);
    }
    .crm-action-card .act-footer i {
        font-size: 13px;
        transition: transform 0.15s;
    }
    .crm-action-card:hover .act-footer i {
        transform: translateX(3px);
    }

    /* ── DUAL-PANEL: tickets + top customers ── */
    .crm-dual-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
        margin-bottom: 28px;
        align-items: start;
    }
    .crm-panel {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        overflow: hidden;
    }
    .crm-panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .crm-panel-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    .crm-panel-header h3 i { font-size: 16px; color: var(--c-text-muted); }
    .crm-panel-header a {
        font-size: 12px;
        font-weight: 600;
        color: var(--c-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .crm-panel-header a:hover { text-decoration: underline; }
    .crm-panel-body { padding: 0; }

    /* Table inside panels */
    .crm-table {
        width: 100%;
        border-collapse: collapse;
    }
    .crm-table thead th {
        padding: 9px 16px;
        font-size: 11px;
        font-weight: 600;
        color: var(--c-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .crm-table tbody tr {
        transition: background 0.1s;
    }
    .crm-table tbody tr:hover {
        background: #f8fafc;
    }
    .crm-table tbody td {
        padding: 10px 16px;
        border-bottom: 1px solid #f1f3f5;
        font-size: 13px;
        color: var(--c-text);
        vertical-align: middle;
    }
    .crm-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Badges */
    .ticket-prio {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .ticket-prio .dot {
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }
    .ticket-prio.urgent { background: #fef2f2; color: #dc2626; }
    .ticket-prio.urgent .dot { background: #ef4444; }
    .ticket-prio.high { background: #fffbeb; color: #d97706; }
    .ticket-prio.high .dot { background: #f59e0b; }
    .ticket-prio.normal { background: #eff6ff; color: #2563eb; }
    .ticket-prio.normal .dot { background: #3b82f6; }
    .ticket-prio.low { background: #f9fafb; color: #6b7280; }
    .ticket-prio.low .dot { background: #9ca3af; }

    .ticket-status-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .ticket-status-sm .dot { width: 5px; height: 5px; border-radius: 50%; }
    .ticket-status-sm.open { background: #eff6ff; color: #2563eb; }
    .ticket-status-sm.open .dot { background: #3b82f6; }
    .ticket-status-sm.pending { background: #fffbeb; color: #d97706; }
    .ticket-status-sm.pending .dot { background: #f59e0b; }
    .ticket-status-sm.resolved { background: #f0fdf4; color: #16a34a; }
    .ticket-status-sm.resolved .dot { background: #22c55e; }
    .ticket-status-sm.closed { background: #f9fafb; color: #6b7280; }
    .ticket-status-sm.closed .dot { background: #9ca3af; }

    /* Customer tier badges */
    .tier-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .tier-badge.platinum { background: #f5f3ff; color: #7c3aed; }
    .tier-badge.gold { background: #fffbeb; color: #d97706; }
    .tier-badge.silver { background: #f1f5f9; color: #64748b; }
    .tier-badge.bronze { background: #fff7ed; color: #c2410c; }

    /* Churn risk badge */
    .churn-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
    }
    .churn-badge.high { background: #fef2f2; color: #dc2626; }
    .churn-badge.medium { background: #fffbeb; color: #d97706; }
    .churn-badge.low { background: #f0fdf4; color: #16a34a; }

    .customer-name {
        font-weight: 500;
        color: var(--c-text);
    }
    .customer-email {
        font-size: 11px;
        color: var(--c-text-muted);
    }
    .price-cell {
        font-weight: 700;
        color: var(--c-text);
    }

    /* ── ACTIVITY FEED ── */
    .crm-activity-feed {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .crm-activity-feed .feed-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .crm-activity-feed .feed-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    .crm-activity-feed .feed-header h3 i { font-size: 16px; color: var(--c-text-muted); }
    .crm-activity-feed .feed-body { padding: 4px 0; }
    .crm-activity-feed .feed-item {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 12px 20px;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.1s;
    }
    .crm-activity-feed .feed-item:last-child { border-bottom: none; }
    .crm-activity-feed .feed-item:hover { background: #f9fafb; }
    .feed-item .feed-dot {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .feed-item .feed-dot.blue { background: #eff6ff; color: #2563eb; }
    .feed-item .feed-dot.green { background: #f0fdf4; color: #16a34a; }
    .feed-item .feed-dot.amber { background: #fffbeb; color: #d97706; }
    .feed-item .feed-dot.purple { background: #f5f3ff; color: #7c3aed; }
    .feed-item .feed-dot.red { background: #fef2f2; color: #dc2626; }
    .feed-item .feed-dot.teal { background: #f0fdfa; color: #14b8a6; }
    .feed-item .feed-content { flex: 1; min-width: 0; }
    .feed-item .feed-summary {
        font-size: 13px;
        color: var(--c-text);
        line-height: 1.4;
    }
    .feed-item .feed-meta {
        font-size: 11px;
        color: var(--c-text-muted);
        margin-top: 2px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .feed-item .feed-meta .type-tag {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 1px 6px;
        border-radius: 4px;
        background: #f3f4f6;
        color: #6b7280;
    }

    /* ── MONTHLY TREND ── */
    .crm-trend-row {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 18px 20px;
        margin-bottom: 24px;
    }
    .crm-trend-row .trend-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
    }
    .crm-trend-row .trend-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .crm-trend-row .trend-title i { font-size: 16px; }
    .crm-trend-bars {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        height: 60px;
    }
    .crm-trend-bars .bar-wrap {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        height: 100%;
        justify-content: flex-end;
    }
    .crm-trend-bars .bar {
        width: 100%;
        max-width: 40px;
        border-radius: 4px 4px 0 0;
        background: linear-gradient(180deg, #3b82f6, #60a5fa);
        transition: height 0.4s ease;
        min-height: 4px;
    }
    .crm-trend-bars .bar-label {
        font-size: 10px;
        color: var(--c-text-muted);
        font-weight: 500;
    }
    .crm-trend-bars .bar-count {
        font-size: 10px;
        font-weight: 700;
        color: var(--c-text);
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 1100px) {
        .crm-kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .crm-actions-row { grid-template-columns: repeat(2, 1fr); }
        .crm-dual-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .crm-kpi-grid { grid-template-columns: 1fr; }
        .crm-actions-row { grid-template-columns: 1fr; }
        .crm-distro-grid { grid-template-columns: 1fr; }
        .crm-alerts { flex-direction: column; }
    }
</style>
@endsection

@section('content')
<div class="crm-dash">

    {{-- WELCOME --}}
    <div class="crm-welcome">
        <div class="crm-welcome-left">
            <h1>Good {{ $greeting }}, <span>{{ $adminName }}</span></h1>
            <p>{{ $companyName }} — customer relationship overview &middot; <strong>{{ number_format($totalCustomers) }}</strong> total customers</p>
            <div class="crm-welcome-badge">
                <i class="ph ph-storefront"></i> {{ $companyName }}
            </div>
        </div>
        <div class="crm-welcome-right">
            <div class="date">{{ $phTime->format('F j, Y') }}</div>
            <div class="time">{{ $phTime->format('l') }} &middot; {{ $phTime->format('g:i A') }} PHT</div>
        </div>
    </div>

    {{-- ═══ ALERTS & NOTIFICATIONS ═══ --}}
    <div class="crm-alerts">
        @if($unassignedTickets > 0)
            <a href="{{ route('ecommerce.admin.crm.tickets', ['assigned_to' => '__unassigned__']) }}" class="crm-alert-card">
                <div class="alert-icon red"><i class="ph ph-warning"></i></div>
                <div class="alert-body">
                    <div class="alert-count">{{ $unassignedTickets }}</div>
                    <div class="alert-label">Unassigned Tickets</div>
                </div>
                <i class="ph ph-caret-right alert-arrow"></i>
            </a>
        @endif

        @if($atRiskCount > 0)
            <div class="crm-alert-card" style="cursor:default;">
                <div class="alert-icon purple"><i class="ph ph-warning-circle"></i></div>
                <div class="alert-body">
                    <div class="alert-count">{{ $atRiskCount }}</div>
                    <div class="alert-label">At-Risk Customers</div>
                </div>
            </div>
        @endif
    </div>

    {{-- KPI GRID --}}
    <div class="crm-kpi-grid">
        {{-- Total Spent --}}
        <div class="crm-kpi-card">
            <div class="kpi-icon blue"><i class="ph ph-currency-circle-dollar"></i></div>
            <div class="kpi-info">
                <div class="kpi-label">Total Spent</div>
                <div class="kpi-value">₱{{ number_format($totalSpent) }}</div>
                <div class="kpi-sub">{{ number_format($totalCustomers) }} customers</div>
            </div>
        </div>

        {{-- Avg Order Value --}}
        <div class="crm-kpi-card">
            <div class="kpi-icon green"><i class="ph ph-chart-bar"></i></div>
            <div class="kpi-info">
                <div class="kpi-label">Avg Order Value</div>
                <div class="kpi-value">₱{{ number_format($avgOrderValue) }}</div>
                <div class="kpi-trend up">
                    <i class="ph ph-trend-up"></i> {{ $repeatRate }}% repeat
                </div>
            </div>
        </div>

        {{-- Active Tickets --}}
        <div class="crm-kpi-card">
            <div class="kpi-icon amber"><i class="ph ph-ticket"></i></div>
            <div class="kpi-info">
                <div class="kpi-label">Active Tickets</div>
                <div class="kpi-value">{{ $activeTickets }}</div>
                <div class="kpi-sub">{{ $urgentTickets }} urgent &middot; {{ $unassignedTickets }} unassigned</div>
            </div>
        </div>

        {{-- At Risk --}}
        <div class="crm-kpi-card">
            <div class="kpi-icon red"><i class="ph ph-warning-circle"></i></div>
            <div class="kpi-info">
                <div class="kpi-label">At Risk</div>
                <div class="kpi-value">{{ $atRiskCount }}</div>
                <div class="kpi-sub">{{ $totalCustomers > 0 ? round($atRiskCount / $totalCustomers * 100) : 0 }}% of total</div>
            </div>
        </div>

        {{-- New This Month --}}
        <div class="crm-kpi-card">
            <div class="kpi-icon teal"><i class="ph ph-user-plus"></i></div>
            <div class="kpi-info">
                <div class="kpi-label">New This Month</div>
                <div class="kpi-value">{{ $newThisMonth }}</div>
                <div class="kpi-trend up">
                    <i class="ph ph-trend-up"></i> {{ $newThisWeek }} this week
                </div>
            </div>
        </div>

        {{-- Avg Engagement --}}
        <div class="crm-kpi-card">
            <div class="kpi-icon indigo"><i class="ph ph-heartbeat"></i></div>
            <div class="kpi-info">
                <div class="kpi-label">Avg Engagement</div>
                <div class="kpi-value">{{ number_format($avgEngagement, 1) }}</div>
                <div class="kpi-sub">{{ $optInEmail }} email &middot; {{ $optInSms }} SMS</div>
            </div>
        </div>
    </div>

    {{-- CHURN & ENGAGEMENT DISTRIBUTION --}}
    <div class="crm-distro-grid">
        {{-- Churn Risk Distribution --}}
        <div class="crm-distro-card">
            <div class="distro-header">
                <div class="distro-title"><i class="ph ph-shield-warning" style="color:#ef4444;"></i> Churn Risk</div>
                <div class="distro-value">{{ $churnHigh }}</div>
            </div>
            <div class="crm-distro-bar">
                <div class="seg high" style="width:{{ $churnHighPct }}%"></div>
                <div class="seg medium" style="width:{{ $churnMedPct }}%"></div>
                <div class="seg low" style="width:{{ $churnLowPct }}%"></div>
            </div>
            <div class="crm-distro-legend">
                <span class="legend-item"><span class="legend-dot" style="background:#ef4444;"></span> High ({{ $churnHigh }})</span>
                <span class="legend-item"><span class="legend-dot" style="background:#f59e0b;"></span> Medium ({{ $churnMedium }})</span>
                <span class="legend-item"><span class="legend-dot" style="background:#22c55e;"></span> Low ({{ $churnLow }})</span>
            </div>
        </div>

        {{-- Engagement Level Distribution --}}
        <div class="crm-distro-card">
            <div class="distro-header">
                <div class="distro-title"><i class="ph ph-lightning" style="color:#f59e0b;"></i> Engagement Score</div>
                <div class="distro-value">{{ number_format($avgEngagement, 1) }}</div>
            </div>
            <div class="crm-distro-bar">
                <div class="seg high-eng" style="width:{{ $highEngPct }}%"></div>
                <div class="seg med-eng" style="width:{{ $medEngPct }}%"></div>
                <div class="seg low-eng" style="width:{{ $lowEngPct }}%"></div>
            </div>
            <div class="crm-distro-legend">
                <span class="legend-item"><span class="legend-dot" style="background:#22c55e;"></span> High ({{ $highEngagement }})</span>
                <span class="legend-item"><span class="legend-dot" style="background:#f59e0b;"></span> Medium ({{ $mediumEngagement }})</span>
                <span class="legend-item"><span class="legend-dot" style="background:#ef4444;"></span> Low ({{ $lowEngagement }})</span>
            </div>
        </div>
    </div>

    {{-- QUICK ACTIONS --}}
    <div class="crm-actions-row">
        <a href="{{ route('ecommerce.admin.crm.customers') }}" class="crm-action-card">
            <div class="act-icon blue"><i class="ph ph-users"></i></div>
            <div class="act-label">Browse Customers</div>
            <div class="act-desc">View profiles, segments, and engagement data</div>
            <div class="act-footer">Open customers <i class="ph ph-arrow-right"></i></div>
        </a>
        <a href="{{ route('ecommerce.admin.crm.tickets') }}" class="crm-action-card">
            <div class="act-icon amber"><i class="ph ph-ticket"></i></div>
            <div class="act-label">Manage Tickets</div>
            <div class="act-desc">{{ $activeTickets }} open ({{ $urgentTickets }} urgent &middot; {{ $unassignedTickets }} unassigned)</div>
            <div class="act-footer">View all tickets <i class="ph ph-arrow-right"></i></div>
        </a>
        <a href="{{ route('ecommerce.admin.crm.segments') }}" class="crm-action-card">
            <div class="act-icon purple"><i class="ph ph-funnel"></i></div>
            <div class="act-label">Segments & Tags</div>
            <div class="act-desc">{{ $totalSegments }} segments &middot; {{ $autoSegments }} auto &middot; RFM scoring and tag management</div>
            <div class="act-footer">Manage segments <i class="ph ph-arrow-right"></i></div>
        </a>
    </div>

    {{-- TICKETS + TOP CUSTOMERS --}}
    <div class="crm-dual-grid">
        {{-- Recent Tickets --}}
        <div class="crm-panel">
            <div class="crm-panel-header">
                <h3><i class="ph ph-ticket"></i> Active Tickets <span style="font-size:12px; font-weight:500; color:var(--c-text-muted); margin-left:6px;">({{ $activeTickets }} open)</span></h3>
            </div>
            <div class="crm-panel-body">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTickets as $ticket)
                            <tr>
                                <td>
                                    <div class="customer-name">
                                        {{ $ticket['customer']['first_name'] ?? '—' }} {{ $ticket['customer']['last_name'] ?? '' }}
                                    </div>
                                    <div class="customer-email">{{ $ticket['subject'] }}</div>
                                </td>
                                <td>
                                    <span class="ticket-status-sm {{ $ticket['status'] }}">
                                        <span class="dot"></span> {{ ucfirst($ticket['status']) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="ticket-prio {{ $ticket['priority'] }}">
                                        <span class="dot"></span> {{ ucfirst($ticket['priority']) }}
                                    </span>
                                </td>
                                <td style="font-size:12px; color:var(--c-text-muted);">
                                    {{ \Carbon\Carbon::parse($ticket['created_at'])->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center; padding:32px 16px; color:var(--c-text-muted); font-size:13px;">No active tickets</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top Customers --}}
        <div class="crm-panel">
            <div class="crm-panel-header">
                <h3><i class="ph ph-crown"></i> Top Customers</h3>
                <a href="{{ route('ecommerce.admin.crm.customers') }}">View all <i class="ph ph-arrow-right"></i></a>
            </div>
            <div class="crm-panel-body">
                <table class="crm-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Spent</th>
                            <th>Tier</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCustomers as $c)
                            <tr>
                                <td>
                                    <div class="customer-name">{{ $c['first_name'] }} {{ $c['last_name'] }}</div>
                                    <div class="customer-email">{{ $c['email'] }}</div>
                                </td>
                                <td class="price-cell">₱{{ number_format($c['total_spent']) }}</td>
                                <td>
                                    @if($c['tier'])
                                        <span class="tier-badge {{ $c['tier'] }}">{{ $c['tier'] }}</span>
                                    @else
                                        <span style="color:var(--c-text-muted); font-size:12px;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($c['churn_risk'])
                                        <span class="churn-badge {{ $c['churn_risk'] }}">{{ ucfirst($c['churn_risk']) }}</span>
                                    @else
                                        <span style="color:var(--c-text-muted); font-size:12px;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center; padding:32px 16px; color:var(--c-text-muted); font-size:13px;">No customer data yet</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MONTHLY SIGNUP TREND --}}
    @if(count($monthlySignups) > 0)
    <div class="crm-trend-row">
        <div class="trend-header">
            <div class="trend-title"><i class="ph ph-chart-line" style="color:#3b82f6;"></i> Monthly New Customers</div>
            <div style="font-size:12px; color:var(--c-text-muted);">Last 6 months</div>
        </div>
        @php
            $maxCount = max(array_column($monthlySignups, 'count'));
        @endphp
        <div class="crm-trend-bars">
            @foreach($monthlySignups as $m)
                @php $pct = $maxCount > 0 ? ($m['count'] / $maxCount * 100) : 0; @endphp
                <div class="bar-wrap">
                    <div class="bar-count">{{ $m['count'] }}</div>
                    <div class="bar" style="height:{{ $pct }}%;"></div>
                    <div class="bar-label">{{ \Carbon\Carbon::parse($m['month'] . '-01')->format('M') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ACTIVITY FEED --}}
    <div class="crm-activity-feed">
        <div class="feed-header">
            <h3><i class="ph ph-activity"></i> Recent Activity</h3>
            <span style="font-size:12px; color:var(--c-text-muted);">
                @if(count($recentActivity) > 0)
                    Latest: {{ \Carbon\Carbon::parse($recentActivity[0]['occurred_at'])->diffForHumans() }}
                @endif
            </span>
        </div>
        <div class="feed-body">
            @forelse($recentActivity as $log)
                @php
                    $dotColor = match($log['type']) {
                        'order' => 'green',
                        'ticket' => 'amber',
                        'note' => 'blue',
                        'campaign' => 'purple',
                        'system' => 'teal',
                        'review' => 'green',
                        default => 'blue'
                    };
                    $actionLabel = str_replace('_', ' ', $log['action']);
                @endphp
                <div class="feed-item">
                    <div class="feed-dot {{ $dotColor }}">
                        <i class="ph ph-{{ $log['type'] === 'order' ? 'shopping-cart' : ($log['type'] === 'ticket' ? 'ticket' : ($log['type'] === 'campaign' ? 'envelope' : ($log['type'] === 'system' ? 'gear' : 'circle'))) }}"></i>
                    </div>
                    <div class="feed-content">
                        <div class="feed-summary">
                            @if(!empty($log['customer']))
                                <strong>{{ $log['customer']['first_name'] }} {{ $log['customer']['last_name'] }}</strong> &mdash;
                            @endif
                            {{ $log['summary'] ?? $actionLabel }}
                        </div>
                        <div class="feed-meta">
                            <span class="type-tag">{{ $log['type'] }}</span>
                            <span>{{ \Carbon\Carbon::parse($log['occurred_at'])->diffForHumans() }}</span>
                            @if($log['action'])
                                <span>&middot; {{ $actionLabel }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div style="text-align:center; padding:40px 20px; color:var(--c-text-muted); font-size:13px;">
                    <i class="ph ph-activity" style="font-size:36px; display:block; margin-bottom:8px; color:#d1d5db;"></i>
                    No recent activity — activity will appear as customers interact
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection
