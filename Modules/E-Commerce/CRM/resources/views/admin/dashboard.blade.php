@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'CRM Dashboard — ' . $companyName)

@section('head')
<style>
    .crm-kpi { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .crm-kpi-value { font-size: 26px; font-weight: 700; color: var(--c-text); margin-top: 4px; }
    .crm-kpi-label { font-size: 11px; font-weight: 600; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .crm-quick-link { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: border-color 0.2s, box-shadow 0.2s; }
    .crm-quick-link:hover { border-color: var(--c-primary); box-shadow: 0 2px 12px rgba(27,111,200,0.08); }
    .crm-quick-link-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .crm-source-tag { display: inline-flex; align-items: center; gap: 8px; background: #f5f5f5; padding: 6px 14px; border-radius: 8px; font-size: 13px; }
</style>
@endsection

@section('content')
<div style="max-width:1200px; margin:0 auto;">
    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">CRM Dashboard</h1>
            <p style="color:var(--c-text-muted); font-size:14px; margin-top:4px;">{{ $companyName }} — Customer relationship overview</p>
        </div>
        <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--c-text-muted);">
            <span style="font-size:18px;"><i class="ph ph-users-three"></i></span>
            <span><strong style="color:var(--c-text);">{{ number_format($totalCustomers) }}</strong> total customers</span>
        </div>
    </div>

    <!-- KPI Grid -->
    <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:32px;">
        <div class="crm-kpi">
            <div class="crm-kpi-label">Total Spent</div>
            <div class="crm-kpi-value" style="color:var(--c-primary);">₱{{ number_format($totalSpent, 0) }}</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">Avg Order Value</div>
            <div class="crm-kpi-value" style="color:#22C55E;">₱{{ number_format($avgOrderValue, 0) }}</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">Repeat Customers</div>
            <div class="crm-kpi-value" style="color:#F59E0B;">{{ $repeatCount }}</div>
            <div style="font-size:12px; color:#999; margin-top:4px;">{{ $totalCustomers > 0 ? round($repeatCount / $totalCustomers * 100) : 0 }}% of total</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">New This Month</div>
            <div class="crm-kpi-value" style="color:#3B82F6;">{{ $newThisMonth }}</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">Abandoned Carts</div>
            <div class="crm-kpi-value" style="color:#EF4444;">{{ $abandonedCount }}</div>
            <div style="font-size:12px; color:#999; margin-top:4px;">{{ $recoveredCount }} recovered</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">At-Risk Customers</div>
            <div class="crm-kpi-value" style="color:#F97316;">{{ $atRiskCount }}</div>
            <div style="font-size:12px; color:#999; margin-top:4px;">No purchase in 90+ days</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">Active Coupons</div>
            <div class="crm-kpi-value" style="color:#A855F7;">{{ $activeCoupons }}</div>
            <div style="font-size:12px; color:#999; margin-top:4px;">{{ $totalRedemptions }} total redemptions</div>
        </div>
        @if(isset($pipelineValue))
        <div class="crm-kpi">
            <div class="crm-kpi-label">Pipeline Value</div>
            <div class="crm-kpi-value" style="color:var(--c-primary);">₱{{ number_format($pipelineValue, 0) }}</div>
        </div>
        <div class="crm-kpi">
            <div class="crm-kpi-label">Win Rate</div>
            <div class="crm-kpi-value" style="color:#22C55E;">{{ $winRate }}%</div>
            <div style="font-size:12px; color:#999; margin-top:4px;">{{ $wonCount }} won / {{ $wonCount + $lostCount }} closed</div>
        </div>
        @endif
        <div class="crm-kpi" style="grid-column:span 2;">
            <div class="crm-kpi-label">Acquisition Sources</div>
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:12px;">
                @forelse($sources as $source)
                    <div class="crm-source-tag">
                        <span style="font-weight:600; color:var(--c-text-muted);">{{ $source->source }}</span>
                        <span style="font-weight:700; color:var(--c-text);">{{ $source->count }}</span>
                    </div>
                @empty
                    <span style="font-size:13px; color:#aaa;">No source data yet</span>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
        <a href="{{ route('ecommerce.admin.crm.customers') }}" class="crm-quick-link">
            <div class="crm-quick-link-icon" style="background:rgba(27,111,200,0.12); color:var(--c-primary);">
                <i class="ph ph-users"></i>
            </div>
            <div>
                <div style="font-size:14px; font-weight:600; color:var(--c-text);">Browse Customers</div>
                <div style="font-size:12px; color:var(--c-text-muted);">View profiles & segments</div>
            </div>
        </a>
        <a href="{{ route('ecommerce.admin.crm.abandoned-carts') }}" class="crm-quick-link">
            <div class="crm-quick-link-icon" style="background:rgba(239,68,68,0.12); color:#EF4444;">
                <i class="ph ph-shopping-cart"></i>
            </div>
            <div>
                <div style="font-size:14px; font-weight:600; color:var(--c-text);">Abandoned Carts</div>
                <div style="font-size:12px; color:var(--c-text-muted);">Recover lost sales</div>
            </div>
        </a>
        <a href="{{ route('ecommerce.admin.crm.reviews') }}" class="crm-quick-link">
            <div class="crm-quick-link-icon" style="background:rgba(34,197,94,0.12); color:#22C55E;">
                <i class="ph ph-star"></i>
            </div>
            <div>
                <div style="font-size:14px; font-weight:600; color:var(--c-text);">Product Reviews</div>
                <div style="font-size:12px; color:var(--c-text-muted);">Approve & manage feedback</div>
            </div>
        </a>
        <a href="{{ route('ecommerce.admin.crm.leads.pipeline') }}" class="crm-quick-link">
            <div class="crm-quick-link-icon" style="background:rgba(245,158,11,0.12); color:#F59E0B;">
                <i class="ph ph-funnel"></i>
            </div>
            <div>
                <div style="font-size:14px; font-weight:600; color:var(--c-text);">Sales Pipeline</div>
                <div style="font-size:12px; color:var(--c-text-muted);">Track leads & opportunities</div>
            </div>
        </a>
        <a href="{{ route('ecommerce.admin.crm.coupons') }}" class="crm-quick-link">
            <div class="crm-quick-link-icon" style="background:rgba(168,85,247,0.12); color:#A855F7;">
                <i class="ph ph-tag"></i>
            </div>
            <div>
                <div style="font-size:14px; font-weight:600; color:var(--c-text);">Coupons</div>
                <div style="font-size:12px; color:var(--c-text-muted);">Create & manage promotions</div>
            </div>
        </a>
    </div>
</div>
@endsection
