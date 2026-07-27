@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';

    $initial = strtoupper(substr($customer->first_name ?: $customer->email ?: '?', 0, 1));
    $churnColor = match($customer->churn_risk) {
        'high' => '#EF4444',
        'medium' => '#F59E0B',
        'low' => '#22C55E',
        default => '#6B7280',
    };
    $tierColor = match($customer->tier) {
        'platinum' => '#7C3AED',
        'gold' => '#F59E0B',
        'silver' => '#64748B',
        'bronze' => '#C2410C',
        default => '#6B7280',
    };
    $activeTab = request()->input('tab', 'overview');
@endphp

@extends('ecommerce::admin.layout')

@section('title', $customer->full_name . ' — CRM — ' . $companyName)

@section('head')
<style>
    /* ── Customer 360 v2 ── */
    .c360 { max-width: 1200px; margin: 0 auto; }

    /* Back link */
    .c360-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: var(--c-text-muted); font-size: 13px; font-weight: 500;
        margin-bottom: 20px; text-decoration: none; transition: color 0.15s;
    }
    .c360-back:hover { color: var(--c-primary); }

    /* ── Header ── */
    .c360-header {
        display: flex; align-items: flex-start; gap: 24px;
        margin-bottom: 28px; flex-wrap: wrap;
    }
    .c360-avatar {
        width: 64px; height: 64px; border-radius: 50%;
        background: linear-gradient(135deg, var(--c-primary), #1a5aa8);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 26px; font-weight: 700; flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(27,111,200,0.25);
    }
    .c360-info { flex: 1; min-width: 0; }
    .c360-info h1 {
        font-size: 24px; font-weight: 700; color: var(--c-text);
        margin: 0 0 6px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    }
    .c360-info h1 .tier-badge-c360 {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 10px; border-radius: 20px;
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .c360-info h1 .tier-badge-c360.platinum { background: #f5f3ff; color: #7c3aed; }
    .c360-info h1 .tier-badge-c360.gold { background: #fffbeb; color: #d97706; }
    .c360-info h1 .tier-badge-c360.silver { background: #f1f5f9; color: #64748b; }
    .c360-info h1 .tier-badge-c360.bronze { background: #fff7ed; color: #c2410c; }

    .c360-info .c360-meta {
        display: flex; flex-wrap: wrap; gap: 14px;
        font-size: 13px; color: var(--c-text-muted); margin-bottom: 8px;
    }
    .c360-info .c360-meta span { display: flex; align-items: center; gap: 5px; }
    .c360-info .c360-meta i { font-size: 15px; }

    /* Tags inline edit */
    .c360-tags {
        display: flex; flex-wrap: wrap; gap: 5px; align-items: center;
    }
    .c360-tag {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 10px; border-radius: 6px;
        font-size: 12px; font-weight: 600;
        transition: transform 0.1s;
    }
    .c360-tag:hover { transform: translateY(-1px); }
    .c360-tag .tag-remove {
        cursor: pointer; font-size: 14px; opacity: 0.5; line-height: 1;
    }
    .c360-tag .tag-remove:hover { opacity: 1; }
    .c360-tags .tag-add-btn {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 2px 8px; border-radius: 6px; border: 1px dashed #d0d5dd;
        font-size: 11px; font-weight: 600; color: var(--c-text-muted);
        cursor: pointer; background: transparent; transition: all 0.15s;
    }
    .c360-tags .tag-add-btn:hover {
        border-color: var(--c-primary); color: var(--c-primary); background: #f0f4ff;
    }

    /* ── KPI Row ── */
    .c360-kpi-row {
        display: grid; grid-template-columns: repeat(5, 1fr);
        gap: 12px; margin-bottom: 24px;
    }
    .c360-kpi {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 10px; padding: 14px 16px;
        transition: box-shadow 0.15s, transform 0.1s;
    }
    .c360-kpi:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.03); transform: translateY(-1px); }
    .c360-kpi .kpi-label {
        font-size: 10px; font-weight: 600; color: var(--c-text-muted);
        text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px;
    }
    .c360-kpi .kpi-value {
        font-size: 20px; font-weight: 700; color: var(--c-text); line-height: 1.1;
    }
    .c360-kpi .kpi-sub {
        font-size: 11px; color: var(--c-text-muted); margin-top: 2px;
    }

    /* Churn + Risk mini card */
    .c360-risks {
        display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap;
    }
    .c360-risk-badge {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
        background: #fff; border: 1px solid var(--c-border);
    }
    .c360-risk-badge .dot {
        width: 8px; height: 8px; border-radius: 50%;
    }
    .c360-risk-badge .label { color: var(--c-text-muted); font-weight: 500; }
    .c360-risk-badge strong { color: var(--c-text); }

    /* ── Tab Navigation ── */
    .c360-tabs {
        display: flex; gap: 0; border-bottom: 2px solid var(--c-border);
        margin-bottom: 24px; overflow-x: auto;
    }
    .c360-tab {
        display: flex; align-items: center; gap: 7px;
        padding: 10px 20px; font-size: 13px; font-weight: 500;
        color: var(--c-text-muted); text-decoration: none;
        border-bottom: 2px solid transparent; margin-bottom: -2px;
        transition: all 0.15s; white-space: nowrap;
    }
    .c360-tab:hover { color: var(--c-text); }
    .c360-tab.active {
        color: var(--c-primary); border-bottom-color: var(--c-primary);
        font-weight: 600;
    }
    .c360-tab i { font-size: 16px; }
    .c360-tab .badge {
        background: #f3f4f6; color: #6b7280; font-size: 10px; font-weight: 700;
        padding: 1px 6px; border-radius: 10px; margin-left: 2px;
    }
    .c360-tab.active .badge { background: #eff6ff; color: var(--c-primary); }

    /* ── Section Cards ── */
    .c360-section {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 12px; overflow: hidden; margin-bottom: 20px;
    }
    .c360-section-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .c360-section-header h3 {
        font-size: 14px; font-weight: 600; color: var(--c-text);
        display: flex; align-items: center; gap: 8px; margin: 0;
    }
    .c360-section-header h3 i { font-size: 16px; color: var(--c-text-muted); }
    .c360-section-header .hdr-action {
        font-size: 12px; font-weight: 600; color: var(--c-primary);
        cursor: pointer; display: flex; align-items: center; gap: 4px;
    }
    .c360-section-header .hdr-action:hover { text-decoration: underline; }
    .c360-section-body { padding: 16px 20px; }

    /* ── Tables ── */
    .c360-table { width: 100%; border-collapse: collapse; }
    .c360-table thead th {
        padding: 8px 16px; font-size: 11px; font-weight: 600;
        color: var(--c-text-muted); text-transform: uppercase;
        letter-spacing: 0.3px; border-bottom: 1px solid var(--c-border);
        background: #fafbfc; text-align: left;
    }
    .c360-table tbody tr { transition: background 0.08s; }
    .c360-table tbody tr:hover { background: #f8fafc; }
    .c360-table tbody td {
        padding: 10px 16px; border-bottom: 1px solid #f1f3f5;
        font-size: 13px; color: var(--c-text); vertical-align: middle;
    }
    .c360-table tbody tr:last-child td { border-bottom: none; }

    /* ── Status/Priority Badges ── */
    .badge-status {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .badge-status .dot { width: 5px; height: 5px; border-radius: 50%; }
    .badge-status.open { background: #eff6ff; color: #2563eb; }
    .badge-status.open .dot { background: #3b82f6; }
    .badge-status.pending { background: #fffbeb; color: #d97706; }
    .badge-status.pending .dot { background: #f59e0b; }
    .badge-status.resolved { background: #f0fdf4; color: #16a34a; }
    .badge-status.resolved .dot { background: #22c55e; }
    .badge-status.closed { background: #f9fafb; color: #6b7280; }
    .badge-status.closed .dot { background: #9ca3af; }
    .badge-prio {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .badge-prio .dot { width: 5px; height: 5px; border-radius: 50%; }
    .badge-prio.urgent { background: #fef2f2; color: #dc2626; }
    .badge-prio.urgent .dot { background: #ef4444; }
    .badge-prio.high { background: #fffbeb; color: #d97706; }
    .badge-prio.high .dot { background: #f59e0b; }
    .badge-prio.normal { background: #eff6ff; color: #2563eb; }
    .badge-prio.normal .dot { background: #3b82f6; }
    .badge-prio.low { background: #f9fafb; color: #6b7280; }
    .badge-prio.low .dot { background: #9ca3af; }
    .badge-delivery {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .badge-delivery.delivered, .badge-delivery.sent { background: #f0fdf4; color: #16a34a; }
    .badge-delivery.opened { background: #eff6ff; color: #2563eb; }
    .badge-delivery.clicked { background: #f5f3ff; color: #7c3aed; }
    .badge-delivery.bounced, .badge-delivery.failed { background: #fef2f2; color: #dc2626; }
    .badge-delivery.queued { background: #fffbeb; color: #d97706; }

    /* ── Timeline ── */
    .c360-timeline { position: relative; padding-left: 28px; }
    .c360-timeline::before {
        content: ''; position: absolute; left: 11px; top: 8px; bottom: 8px;
        width: 2px; background: #e5e7eb; border-radius: 2px;
    }
    .c360-tl-item {
        position: relative; padding: 0 0 20px 0;
    }
    .c360-tl-item:last-child { padding-bottom: 0; }
    .c360-tl-item .tl-dot {
        position: absolute; left: -28px; top: 4px;
        width: 24px; height: 24px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; z-index: 1;
        box-shadow: 0 0 0 3px #fff;
    }
    .c360-tl-item .tl-dot.blue { background: #eff6ff; color: #2563eb; }
    .c360-tl-item .tl-dot.green { background: #f0fdf4; color: #16a34a; }
    .c360-tl-item .tl-dot.amber { background: #fffbeb; color: #d97706; }
    .c360-tl-item .tl-dot.purple { background: #f5f3ff; color: #7c3aed; }
    .c360-tl-item .tl-dot.red { background: #fef2f2; color: #dc2626; }
    .c360-tl-item .tl-dot.teal { background: #f0fdfa; color: #14b8a6; }
    .c360-tl-content { min-width: 0; }
    .c360-tl-content .tl-summary {
        font-size: 13px; color: var(--c-text); line-height: 1.4;
    }
    .c360-tl-content .tl-meta {
        font-size: 11px; color: var(--c-text-muted); margin-top: 2px;
        display: flex; align-items: center; gap: 8px;
    }
    .c360-tl-content .tl-meta .tl-type {
        font-size: 10px; font-weight: 600; text-transform: uppercase;
        letter-spacing: 0.3px; padding: 1px 6px; border-radius: 4px;
        background: #f3f4f6; color: #6b7280;
    }

    /* ── Consent toggles ── */
    .consent-switch {
        position: relative; display: inline-flex; align-items: center;
        gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500;
        color: var(--c-text); user-select: none;
    }
    .consent-switch input { display: none; }
    .consent-switch .slider {
        width: 40px; height: 22px; background: #d1d5db; border-radius: 11px;
        transition: background 0.2s; position: relative;
        flex-shrink: 0;
    }
    .consent-switch .slider::after {
        content: ''; position: absolute; top: 2px; left: 2px;
        width: 18px; height: 18px; border-radius: 50%; background: #fff;
        transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    .consent-switch input:checked + .slider { background: #22c55e; }
    .consent-switch input:checked + .slider::after { transform: translateX(18px); }

    /* ── Order item expand ── */
    .order-items-toggle {
        font-size: 11px; color: var(--c-primary); cursor: pointer;
        font-weight: 600; display: inline-flex; align-items: center; gap: 3px;
    }
    .order-items-toggle:hover { text-decoration: underline; }
    .order-items-detail { display: none; margin-top: 4px; }
    .order-items-detail.open { display: block; }
    .order-item-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 3px 0; font-size: 12px; color: var(--c-text-muted);
    }

    /* ── Inline form ── */
    .c360-notes-textarea {
        width: 100%; border: 1px solid #e1e3e5; border-radius: 8px;
        padding: 10px 12px; font: inherit; font-size: 13px;
        color: var(--c-text); resize: vertical; min-height: 80px;
        transition: border-color 0.15s;
    }
    .c360-notes-textarea:focus {
        border-color: var(--c-primary); outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .c360-save-btn {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 8px; padding: 7px 16px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: background 0.15s;
    }
    .c360-save-btn:hover { background: #1a5aa8; }
    .c360-save-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .c360-cancel-btn {
        display: inline-flex; align-items: center; gap: 6px;
        margin-top: 8px; margin-left: 6px; padding: 7px 14px; border: 1px solid #e1e3e5;
        border-radius: 6px; background: #fff; color: var(--c-text-muted);
        font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.15s;
    }
    .c360-cancel-btn:hover { background: #f5f5f5; }

    /* ── Quick actions row ── */
    .c360-actions {
        display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;
    }
    .c360-action-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 8px 16px; border-radius: 8px; border: 1px solid var(--c-border);
        background: #fff; font-size: 12px; font-weight: 600; color: var(--c-text);
        cursor: pointer; text-decoration: none; transition: all 0.15s;
    }
    .c360-action-btn:hover {
        border-color: var(--c-primary); color: var(--c-primary);
        box-shadow: 0 2px 8px rgba(27,111,200,0.06);
    }
    .c360-action-btn i { font-size: 15px; }

    /* ── RFM Score display ── */
    .rfm-score {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 3px 10px; border-radius: 6px; background: #f3f4f6;
        font-size: 13px; font-weight: 700; color: var(--c-text);
    }
    .rfm-score span { font-weight: 500; color: var(--c-text-muted); }

    /* ── Empty state ── */
    .c360-empty {
        text-align: center; padding: 40px 20px; color: var(--c-text-muted);
    }
    .c360-empty i {
        font-size: 36px; display: block; margin-bottom: 8px; color: #d1d5db;
    }
    .c360-empty p { font-size: 13px; }

    /* ── Order status badges ── */
    .order-status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .order-status-badge .dot { width: 5px; height: 5px; border-radius: 50%; }
    .order-status-badge.pending { background: #fffbeb; color: #d97706; }
    .order-status-badge.pending .dot { background: #f59e0b; }
    .order-status-badge.processing { background: #eff6ff; color: #2563eb; }
    .order-status-badge.processing .dot { background: #3b82f6; }
    .order-status-badge.shipped { background: #f5f3ff; color: #7c3aed; }
    .order-status-badge.shipped .dot { background: #8b5cf6; }
    .order-status-badge.delivered { background: #f0fdf4; color: #16a34a; }
    .order-status-badge.delivered .dot { background: #22c55e; }
    .order-status-badge.cancelled { background: #fef2f2; color: #dc2626; }
    .order-status-badge.cancelled .dot { background: #ef4444; }

    /* ── Responsive ── */
    @media (max-width: 900px) {
        .c360-kpi-row { grid-template-columns: repeat(3, 1fr); }
        .c360-tabs { gap: 0; }
        .c360-tab { padding: 8px 12px; font-size: 12px; }
    }
    @media (max-width: 640px) {
        .c360-kpi-row { grid-template-columns: repeat(2, 1fr); }
        .c360-header { flex-direction: column; align-items: center; text-align: center; }
        .c360-meta { justify-content: center; }
        .c360-tags { justify-content: center; }
    }
</style>
@endsection

@section('content')
<div class="c360">

    {{-- ═══ BACK LINK ═══ --}}
    <a href="{{ route('ecommerce.admin.crm.customers') }}" class="c360-back">
        <i class="ph ph-arrow-left"></i> Back to Customers
    </a>

    {{-- ═══ HEADER ═══ --}}
    <div class="c360-header">
        <div class="c360-avatar">{{ $initial }}</div>
        <div class="c360-info">
            <h1>
                {{ $customer->full_name }}
                @if($customer->tier)
                    <span class="tier-badge-c360 {{ $customer->tier }}">{{ $customer->tier }}</span>
                @endif
            </h1>
            <div class="c360-meta">
                <span><i class="ph ph-envelope"></i> {{ $customer->email ?? '—' }}</span>
                <span><i class="ph ph-phone"></i> {{ $customer->phone ?? '—' }}</span>
                <span><i class="ph ph-flag"></i> {{ $customer->source ? ucfirst($customer->source) : 'Unknown' }}</span>
                <span><i class="ph ph-calendar"></i> Joined {{ $customer->created_at?->format('M j, Y') ?? '—' }}</span>
            </div>
            <div class="c360-tags" id="c360-tags-container">
                @foreach($customer->tags as $tag)
                    <span class="c360-tag" style="background:{{ $tag->color }}18; color:{{ $tag->color }}; border:1px solid {{ $tag->color }}33;">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ KPI ROW ═══ --}}
    <div class="c360-kpi-row">
        <div class="c360-kpi">
            <div class="kpi-label">Total Spent</div>
            <div class="kpi-value" style="color:var(--c-primary);">₱{{ number_format($customer->total_spent, 0) }}</div>
            <div class="kpi-sub">{{ $customer->order_count }} orders</div>
        </div>
        <div class="c360-kpi">
            <div class="kpi-label">Avg Order Value</div>
            <div class="kpi-value">₱{{ number_format($customer->average_order_value, 0) }}</div>
            <div class="kpi-sub">{{ $monthsSincePurchase }} months since last</div>
        </div>
        <div class="c360-kpi">
            <div class="kpi-label">Projected LTV</div>
            <div class="kpi-value">₱{{ number_format($projectedLtv, 0) }}</div>
            <div class="kpi-sub">Next 12 months estimate</div>
        </div>
        <div class="c360-kpi">
            <div class="kpi-label">Engagement</div>
            <div class="kpi-value">{{ number_format($customer->engagement_score, 1) }}</div>
            <div class="kpi-sub">/ 5.00 score</div>
        </div>
        <div class="c360-kpi">
            <div class="kpi-label">Open Tickets</div>
            <div class="kpi-value">{{ $openTickets }}</div>
            <div class="kpi-sub">
                @if($openTickets > 0)
                    <a href="#tab-tickets" onclick="switchTab('tickets')" style="color:var(--c-primary);">View tickets</a>
                @else
                    No open tickets
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ RISK + SCORE BADGES ═══ --}}
    <div class="c360-risks">
        <div class="c360-risk-badge">
            <span class="label">Churn Risk:</span>
            <span class="dot" style="background:{{ $churnColor }};"></span>
            <strong style="color:{{ $churnColor }};">{{ ucfirst($customer->churn_risk ?? 'n/a') }}</strong>
        </div>
        <div class="c360-risk-badge">
            <span class="label">RFM:</span>
            <span class="rfm-score">{{ $rfm['total'] ?? '—' }} <span>/ 15</span></span>
        </div>
        <div class="c360-risk-badge">
            <span class="label">Segment:</span>
            <strong>{{ $rfm['segment']['label'] ?? 'Unsegmented' }}</strong>
        </div>
        <div class="c360-risk-badge">
            <span class="label">Opt-ins:</span>
            <strong>{{ $customer->opt_in_email ? 'Email' : '' }}{{ $customer->opt_in_email && $customer->opt_in_sms ? ' + ' : '' }}{{ $customer->opt_in_sms ? 'SMS' : 'None' }}</strong>
        </div>
    </div>

    {{-- ═══ QUICK ACTIONS ═══ --}}
    <div class="c360-actions">
        <a class="c360-action-btn" href="#" onclick="event.preventDefault(); document.getElementById('recalc-form').submit();">
            <i class="ph ph-arrows-clockwise"></i> Recalculate LTV & Risk
        </a>
        <form id="recalc-form" method="POST" action="{{ route('ecommerce.admin.crm.api.customers.recalculate', $customer->id) }}" style="display:none;">
            @csrf
        </form>
        <a class="c360-action-btn" href="#tab-consent" onclick="switchTab('consent')">
            <i class="ph ph-checks"></i> Manage Consent
        </a>
    </div>

    {{-- ═══ TABS ═══ --}}
    <div class="c360-tabs">
        <a class="c360-tab {{ $activeTab === 'overview' ? 'active' : '' }}" href="#" onclick="switchTab('overview')" data-tab="overview">
            <i class="ph ph-user-circle"></i> Overview
        </a>
        <a class="c360-tab {{ $activeTab === 'orders' ? 'active' : '' }}" href="#" onclick="switchTab('orders')" data-tab="orders">
            <i class="ph ph-shopping-cart"></i> Orders
            <span class="badge">{{ $orders->count() }}</span>
        </a>
        <a class="c360-tab {{ $activeTab === 'tickets' ? 'active' : '' }}" href="#" onclick="switchTab('tickets')" data-tab="tickets">
            <i class="ph ph-ticket"></i> Tickets
            <span class="badge">{{ $customer->tickets->count() }}</span>
        </a>
        <a class="c360-tab {{ $activeTab === 'timeline' ? 'active' : '' }}" href="#" onclick="switchTab('timeline')" data-tab="timeline">
            <i class="ph ph-activity"></i> Timeline
        </a>
        <a class="c360-tab {{ $activeTab === 'campaigns' ? 'active' : '' }}" href="#" onclick="switchTab('campaigns')" data-tab="campaigns">
            <i class="ph ph-envelope"></i> Campaigns
            <span class="badge">{{ $customer->campaignLogs->count() }}</span>
        </a>
        <a class="c360-tab {{ $activeTab === 'consent' ? 'active' : '' }}" href="#" onclick="switchTab('consent')" data-tab="consent">
            <i class="ph ph-checks"></i> Consent
        </a>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB: OVERVIEW                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="c360-tab-content" id="tab-overview" style="display: {{ $activeTab === 'overview' ? 'block' : 'none' }};">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">
            {{-- Notes --}}
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-note-pencil"></i> Notes</h3>
                    <span class="hdr-action" onclick="document.getElementById('notes-form').querySelector('.c360-save-btn').disabled=false;">
                        <i class="ph ph-pencil-simple"></i> Edit
                    </span>
                </div>
                <div class="c360-section-body">
                    <form id="notes-form" method="POST" action="{{ route('ecommerce.admin.crm.api.customers.notes', $customer->id) }}" onsubmit="event.preventDefault(); saveNotes(this);">
                        @csrf
                        @method('PUT')
                        <textarea name="notes" class="c360-notes-textarea" placeholder="Add internal notes about this customer...">{{ $customer->notes }}</textarea>
                        <button type="submit" class="c360-save-btn" disabled>
                            <i class="ph ph-check"></i> Save Notes
                        </button>
                    </form>
                </div>
            </div>

            {{-- Segments --}}
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-funnel"></i> Segments</h3>
                </div>
                <div class="c360-section-body">
                    @forelse($customer->segments as $segment)
                        <span style="display:inline-flex; align-items:center; gap:4px; background:#EFF6FF; color:#2563EB; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600; margin:2px;">
                            {{ $segment->name }}
                            @if($segment->is_auto)
                                <span style="font-size:9px; background:#2563EB22; padding:0 4px; border-radius:3px;">AUTO</span>
                            @endif
                        </span>
                    @empty
                        <div class="c360-empty" style="padding:20px;">
                            <p>No segments assigned — <a href="{{ route('ecommerce.admin.crm.api.segments') }}" style="color:var(--c-primary);">manage segments</a></p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Communications --}}
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-chats-circle"></i> Recent Communications</h3>
                </div>
                <div class="c360-section-body" style="padding:0;">
                    @php $comms = $customer->communications->take(5); @endphp
                    @forelse($comms as $comm)
                        <div style="display:flex; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid #f3f4f6;">
                            <div style="width:30px; height:30px; border-radius:8px; background:#f5f5f5; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0;">
                                <i class="ph ph-{{ $comm->type === 'email' ? 'envelope' : 'chat-text' }}"></i>
                            </div>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:13px; font-weight:600;">{{ $comm->subject ?? '(No subject)' }}</div>
                                <div style="font-size:11px; color:var(--c-text-muted); margin-top:1px;">
                                    {{ $comm->created_at->diffForHumans() }} &middot; {{ ucfirst($comm->status) }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="c360-empty"><p>No communications yet</p></div>
                    @endforelse
                </div>
            </div>

            {{-- Reviews --}}
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-star"></i> Product Reviews</h3>
                </div>
                <div class="c360-section-body" style="padding:0;">
                    @forelse($reviews as $review)
                        <div style="padding:10px 16px; border-bottom:1px solid #f3f4f6;">
                            <div style="display:flex; align-items:center; gap:4px; margin-bottom:3px;">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="ph-fill ph-star" style="color:{{ $i <= $review->rating ? '#F59E0B' : '#E5E7EB' }}; font-size:12px;"></i>
                                @endfor
                                @if($review->approved)
                                    <span style="margin-left:auto; font-size:10px; color:#22C55E; font-weight:600;">LIVE</span>
                                @endif
                            </div>
                            @if($review->title)
                                <div style="font-size:12px; font-weight:600;">{{ $review->title }}</div>
                            @endif
                            @if(!empty($review->body) || !empty($review->comment))
                                <div style="font-size:12px; color:var(--c-text-muted); margin-top:2px;">{{ Str::limit($review->body ?? $review->comment ?? '', 100) }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="c360-empty"><p>No reviews yet</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB: ORDERS                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="c360-tab-content" id="tab-orders" style="display: {{ $activeTab === 'orders' ? 'block' : 'none' }};">
        <div class="c360-section">
            <div class="c360-section-header">
                <h3><i class="ph ph-shopping-cart"></i> Order History</h3>
                @if($orders->count() > 0)
                    <span style="font-size:12px; color:var(--c-text-muted);">{{ $orders->count() }} orders</span>
                @endif
            </div>
            <div class="c360-section-body" style="padding:0;">
                @if($orders->count() > 0)
                <table class="c360-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td style="font-weight:600;">#{{ $order['id'] }}</td>
                            <td style="font-size:12px; color:var(--c-text-muted);">
                                {{ \Carbon\Carbon::parse($order['created_at'])->format('M j, Y') }}
                                <br><span style="font-size:11px;">{{ \Carbon\Carbon::parse($order['created_at'])->diffForHumans() }}</span>
                            </td>
                            <td>
                                <span style="font-weight:500;">{{ $order['item_count'] }} item(s)</span>
                                @if(count($order['items']) > 0)
                                    <span class="order-items-toggle" onclick="toggleOrderItems(this)">
                                        <i class="ph ph-caret-down"></i> Details
                                    </span>
                                    <div class="order-items-detail">
                                        @foreach($order['items'] as $item)
                                            <div class="order-item-row">
                                                <span>{{ $item['name'] }} × {{ $item['quantity'] }}</span>
                                                <span>₱{{ number_format($item['price'] * $item['quantity'], 2) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="price-cell" style="font-weight:700;">₱{{ number_format($order['total'], 0) }}</td>
                            <td>
                                <span class="order-status-badge {{ $order['status'] }}">
                                    <span class="dot"></span> {{ ucfirst($order['status']) }}
                                </span>
                                @if($order['tracking_number'])
                                    <div style="font-size:10px; color:var(--c-text-muted); margin-top:2px;">
                                        Tracking: {{ $order['tracking_number'] }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:12px; color:var(--c-text-muted);">{{ $order['payment_method'] ?? '—' }}</span>
                                <div style="font-size:11px; font-weight:600; color:{{ $order['payment_status'] === 'paid' ? '#22C55E' : ($order['payment_status'] === 'pending' ? '#F59E0B' : 'var(--c-text-muted)') }};">
                                    {{ ucfirst($order['payment_status'] ?? 'unknown') }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div class="c360-empty">
                        <i class="ph ph-shopping-cart"></i>
                        <p>No orders yet. Orders will appear once the customer makes a purchase.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB: TICKETS                                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="c360-tab-content" id="tab-tickets" style="display: {{ $activeTab === 'tickets' ? 'block' : 'none' }};">
        <div class="c360-section">
            <div class="c360-section-header">
                <h3><i class="ph ph-ticket"></i> Support Tickets</h3>
                <span class="hdr-action" onclick="showCreateTicket()">
                    <i class="ph ph-plus-circle"></i> New Ticket
                </span>
            </div>
            <div class="c360-section-body" style="padding:0;">
                @if($customer->tickets->count() > 0)
                <table class="c360-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->tickets as $ticket)
                        <tr>
                            <td>
                                <div style="font-weight:500;">{{ $ticket->subject }}</div>
                                @if($ticket->category)
                                    <div style="font-size:11px; color:var(--c-text-muted);">{{ $ticket->category }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge-status {{ $ticket->status }}">
                                    <span class="dot"></span> {{ $ticket->status_label }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-prio {{ $ticket->priority }}">
                                    <span class="dot"></span> {{ $ticket->priority_label }}
                                </span>
                            </td>
                            <td style="font-size:12px; color:var(--c-text-muted);">
                                {{ $ticket->assigned_to ?? 'Unassigned' }}
                            </td>
                            <td style="font-size:12px; color:var(--c-text-muted);">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div class="c360-empty">
                        <i class="ph ph-ticket"></i>
                        <p>No support tickets yet.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Create ticket form (hidden by default) --}}
        <div id="create-ticket-form" style="display:none; margin-top:16px;">
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-plus-circle"></i> Create Support Ticket</h3>
                    <span class="hdr-action" onclick="hideCreateTicket()">
                        <i class="ph ph-x"></i> Cancel
                    </span>
                </div>
                <div class="c360-section-body">
                    <form id="ticket-create-form" onsubmit="event.preventDefault(); createTicket(this);">
                        @csrf
                        <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:12px;">
                            <div>
                                <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Subject *</label>
                                <input type="text" name="subject" required placeholder="Brief issue summary..." style="width:100%; border:1px solid #e1e3e5; border-radius:6px; padding:8px 10px; font-size:13px;">
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Priority</label>
                                <select name="priority" style="width:100%; border:1px solid #e1e3e5; border-radius:6px; padding:8px 10px; font-size:13px;">
                                    <option value="normal">Normal</option>
                                    <option value="low">Low</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div>
                                <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Category</label>
                                <input type="text" name="category" placeholder="e.g. Billing" style="width:100%; border:1px solid #e1e3e5; border-radius:6px; padding:8px 10px; font-size:13px;">
                            </div>
                        </div>
                        <div style="margin-top:12px;">
                            <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Description</label>
                            <textarea name="description" rows="3" placeholder="Detailed description..." style="width:100%; border:1px solid #e1e3e5; border-radius:6px; padding:8px 10px; font-size:13px; resize:vertical;"></textarea>
                        </div>
                        <button type="submit" class="c360-save-btn" style="margin-top:12px;">
                            <i class="ph ph-ticket"></i> Create Ticket
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB: TIMELINE                                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="c360-tab-content" id="tab-timeline" style="display: {{ $activeTab === 'timeline' ? 'block' : 'none' }};">
        <div class="c360-section">
            <div class="c360-section-header">
                <h3><i class="ph ph-activity"></i> Activity Timeline</h3>
                <span style="font-size:12px; color:var(--c-text-muted);">
                    {{ $timeline->count() }} events
                </span>
            </div>
            <div class="c360-section-body">
                @if($timeline->count() > 0)
                <div class="c360-timeline">
                    @foreach($timeline as $event)
                        @php
                            $dotColor = match($event['type']) {
                                'order' => 'green',
                                'ticket' => 'amber',
                                'note' => 'blue',
                                'campaign' => 'purple',
                                'system' => 'teal',
                                'review' => 'green',
                                default => 'blue'
                            };
                            $icon = match($event['type']) {
                                'order' => 'shopping-cart',
                                'ticket' => 'ticket',
                                'note' => 'note-pencil',
                                'campaign' => 'envelope',
                                'system' => 'gear',
                                'review' => 'star',
                                default => 'circle'
                            };
                        @endphp
                        <div class="c360-tl-item">
                            <div class="tl-dot {{ $dotColor }}">
                                <i class="ph ph-{{ $icon }}"></i>
                            </div>
                            <div class="c360-tl-content">
                                <div class="tl-summary">{{ $event['summary'] ?? str_replace('_', ' ', $event['action']) }}</div>
                                <div class="tl-meta">
                                    <span class="tl-type">{{ $event['type'] }}</span>
                                    <span>{{ \Carbon\Carbon::parse($event['occurred_at'])->diffForHumans() }}</span>
                                    @if($event['metadata']['status'] ?? false)
                                        <span>&middot; {{ $event['metadata']['status'] }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                    <div class="c360-empty">
                        <i class="ph ph-activity"></i>
                        <p>No activity recorded yet. Events appear here when customers interact.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB: CAMPAIGNS                                               --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="c360-tab-content" id="tab-campaigns" style="display: {{ $activeTab === 'campaigns' ? 'block' : 'none' }};">
        <div class="c360-section">
            <div class="c360-section-header">
                <h3><i class="ph ph-envelope"></i> Campaign History</h3>
                <span style="font-size:12px; color:var(--c-text-muted);">{{ $customer->campaignLogs->count() }} campaigns</span>
            </div>
            <div class="c360-section-body" style="padding:0;">
                @if($customer->campaignLogs->count() > 0)
                <table class="c360-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->campaignLogs as $campaign)
                        <tr>
                            <td style="font-weight:500;">{{ $campaign->campaign_name }}</td>
                            <td style="font-size:12px; color:var(--c-text-muted); text-transform:uppercase;">
                                {{ $campaign->campaign_type }}
                            </td>
                            <td>
                                <div style="font-size:13px;">{{ $campaign->subject ?? '—' }}</div>
                                <div style="font-size:11px; color:var(--c-text-muted); margin-top:1px;">
                                    {{ $campaign->template_name ?? '' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge-delivery {{ $campaign->status }}">
                                    <span class="dot"></span> {{ ucfirst($campaign->status) }}
                                </span>
                                @if($campaign->has_been_opened)
                                    <div style="font-size:10px; color:#2563eb; margin-top:1px;">Opened ✓</div>
                                @endif
                                @if($campaign->has_been_clicked)
                                    <div style="font-size:10px; color:#7c3aed; margin-top:1px;">Clicked ✓</div>
                                @endif
                            </td>
                            <td style="font-size:12px; color:var(--c-text-muted);">
                                {{ $campaign->sent_at ? $campaign->sent_at->diffForHumans() : ($campaign->created_at->diffForHumans()) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div class="c360-empty">
                        <i class="ph ph-envelope"></i>
                        <p>No campaigns sent yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TAB: CONSENT                                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="c360-tab-content" id="tab-consent" style="display: {{ $activeTab === 'consent' ? 'block' : 'none' }};">
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; align-items:start;">
            {{-- Consent toggles --}}
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-checks"></i> Marketing Consent</h3>
                    <span class="hdr-action" onclick="saveConsent()">
                        <i class="ph ph-floppy-disk"></i> Save
                    </span>
                </div>
                <div class="c360-section-body">
                    <form id="consent-form" method="POST" action="{{ route('ecommerce.admin.crm.api.customers.consent', $customer->id) }}" onsubmit="event.preventDefault(); saveConsent();">
                        @csrf
                        @method('PUT')
                        <div style="display:flex; flex-direction:column; gap:16px;">
                            <label class="consent-switch">
                                <input type="checkbox" name="opt_in_email" value="1" {{ $customer->opt_in_email ? 'checked' : '' }} onchange="document.getElementById('consent-form').querySelector('.hdr-action').style.display='flex';">
                                <span class="slider"></span>
                                <span>Email Marketing <span style="font-weight:400; color:var(--c-text-muted);">— receive promotional emails</span></span>
                            </label>
                            <label class="consent-switch">
                                <input type="checkbox" name="opt_in_sms" value="1" {{ $customer->opt_in_sms ? 'checked' : '' }} onchange="document.getElementById('consent-form').querySelector('.hdr-action').style.display='flex';">
                                <span class="slider"></span>
                                <span>SMS Marketing <span style="font-weight:400; color:var(--c-text-muted);">— receive promotional texts</span></span>
                            </label>
                        </div>
                        @if($customer->opted_in_at)
                            <div style="margin-top:16px; padding:10px 12px; background:#f9fafb; border-radius:8px; font-size:12px; color:var(--c-text-muted);">
                                <i class="ph ph-clock"></i> Last updated {{ $customer->opted_in_at->diffForHumans() }}
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Consent history --}}
            <div class="c360-section">
                <div class="c360-section-header">
                    <h3><i class="ph ph-clock-counter-clockwise"></i> Consent History</h3>
                </div>
                <div class="c360-section-body" style="padding:0;">
                    @if($customer->consentLogs->count() > 0)
                        @foreach($customer->consentLogs as $log)
                            <div style="display:flex; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid #f3f4f6;">
                                <div style="width:28px; height:28px; border-radius:50%; background:{{ $log->action === 'opt_in' ? '#f0fdf4' : '#fef2f2' }}; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0;">
                                    <i class="ph ph-{{ $log->action === 'opt_in' ? 'check' : 'x' }}" style="color:{{ $log->action === 'opt_in' ? '#16a34a' : '#dc2626' }};"></i>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-size:13px; font-weight:600;">
                                        {{ $log->action === 'opt_in' ? 'Opted in' : 'Opted out' }} — {{ ucfirst($log->channel) }}
                                    </div>
                                    <div style="font-size:11px; color:var(--c-text-muted);">
                                        {{ $log->occurred_at->diffForHumans() }}
                                        @if($log->source)
                                            &middot; via {{ ucfirst($log->source) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="c360-empty">
                            <i class="ph ph-clock-counter-clockwise"></i>
                            <p>No consent changes recorded yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    // ── Tab switching ──
    function switchTab(tabId) {
        document.querySelectorAll('.c360-tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.c360-tab').forEach(el => el.classList.remove('active'));
        document.getElementById('tab-' + tabId).style.display = 'block';
        document.querySelector('[data-tab="' + tabId + '"]').classList.add('active');
    }

    // ── Save notes via API ──
    function saveNotes(form) {
        const btn = form.querySelector('.c360-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
        fetch(form.action, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('[name=_token]').value },
            body: JSON.stringify({ notes: form.querySelector('[name=notes]').value }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="ph ph-check"></i> Saved';
                setTimeout(() => { btn.innerHTML = '<i class="ph ph-check"></i> Save Notes'; }, 2000);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-x"></i> Error';
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i> Error'; });
    }

    // ── Save consent via API ──
    function saveConsent() {
        const form = document.getElementById('consent-form');
        const btn = form.querySelector('.hdr-action');
        fetch(form.action, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('[name=_token]').value },
            body: JSON.stringify({
                opt_in_email: form.querySelector('[name=opt_in_email]').checked,
                opt_in_sms: form.querySelector('[name=opt_in_sms]').checked,
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.innerHTML = '<i class="ph ph-check"></i> Saved!';
                setTimeout(() => { btn.innerHTML = '<i class="ph ph-floppy-disk"></i> Save'; }, 2000);
            } else { btn.innerHTML = '<i class="ph ph-x"></i> Error'; }
        })
        .catch(() => { btn.innerHTML = '<i class="ph ph-x"></i> Error'; });
    }

    // ── Toggle order items expand ──
    function toggleOrderItems(el) {
        var detail = el.parentElement.querySelector('.order-items-detail');
        if (detail) {
            detail.classList.toggle('open');
            el.querySelector('i').classList.toggle('ph-caret-down');
            el.querySelector('i').classList.toggle('ph-caret-up');
        }
    }

    // ── Create ticket via API ──
    function showCreateTicket() { document.getElementById('create-ticket-form').style.display = 'block'; }
    function hideCreateTicket() { document.getElementById('create-ticket-form').style.display = 'none'; }
    function createTicket(form) {
        var btn = form.querySelector('.c360-save-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Creating...';
        fetch('{{ route("ecommerce.admin.crm.api.tickets.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('[name=_token]').value },
            body: JSON.stringify({
                customer_id: {{ $customer->id }},
                subject: form.querySelector('[name=subject]').value,
                description: form.querySelector('[name=description]').value,
                priority: form.querySelector('[name=priority]').value,
                category: form.querySelector('[name=category]').value,
            }),
        })
        .then(r => r.json())
        .then(function(data) {
            if (data.success || data.message) {
                btn.innerHTML = '<i class="ph ph-check"></i> Created!';
                setTimeout(function() { window.location.reload(); }, 1000);
            } else { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i> Error'; }
        })
        .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i> Error'; });
    }
</script>
@endsection
