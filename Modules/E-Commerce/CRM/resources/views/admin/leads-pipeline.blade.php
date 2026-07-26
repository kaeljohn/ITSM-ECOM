@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $search = request('search');
    $selectedSource = request('source');
    $selectedAssigned = request('assigned_to');
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Sales Pipeline — CRM — ' . $companyName)

@section('head')
<style>
    .kanban-grid { display:flex; gap:16px; overflow-x:auto; padding-bottom:12px; min-height:70vh; }
    .kanban-column { min-width:260px; max-width:280px; flex:1; display:flex; flex-direction:column; }
    .kanban-header { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:10px 10px 0 0; }
    .kanban-count { font-size:12px; font-weight:700; background:rgba(255,255,255,0.3); padding:2px 8px; border-radius:6px; }
    .kanban-body { flex:1; background:#fff; border:1px solid var(--c-border); border-top:none; border-radius:0 0 10px 10px; padding:8px; display:flex; flex-direction:column; gap:8px; min-height:200px; }
    .lead-card { background:#fff; border:1px solid var(--c-border); border-radius:8px; padding:12px; cursor:default; transition:box-shadow 0.15s, border-color 0.15s; }
    .lead-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.06); border-color:#bbb; }
    .lead-card-name { font-size:14px; font-weight:600; color:var(--c-text); }
    .lead-card-value { font-size:13px; font-weight:700; color:var(--c-text); }
    .lead-card-email { font-size:12px; color:var(--c-text-muted); margin-top:2px; }
    .lead-card-source { font-size:11px; color:#888; }
    .status-dot { width:10px; height:10px; border-radius:50%; display:inline-block; flex-shrink:0; }
    .lead-card-actions { display:flex; gap:4px; margin-top:8px; flex-wrap:wrap; }
    .lead-card-actions a { font-size:11px; color:var(--c-primary); text-decoration:none; }
    .lead-card-actions a:hover { text-decoration:underline; }
    .pipeline-kpi { background:#fff; border:1px solid var(--c-border); border-radius:10px; padding:14px 18px; }
    .pipeline-kpi-value { font-size:20px; font-weight:700; color:var(--c-text); }
    .pipeline-kpi-label { font-size:11px; font-weight:600; color:var(--c-text-muted); text-transform:uppercase; letter-spacing:0.3px; }
</style>
@endsection

@section('content')
<div style="max-width:1400px; margin:0 auto;">
    <!-- Header -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Sales Pipeline</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">{{ $totalLeads }} total leads</p>
        </div>
        <a href="{{ route('ecommerce.admin.crm.leads.create') }}" class="button">
            <i class="ph ph-plus"></i> Add Lead
        </a>
    </div>

    <!-- Pipeline KPIs -->
    <div style="display:grid; grid-template-columns:repeat(5, 1fr); gap:12px; margin-bottom:24px;">
        <div class="pipeline-kpi">
            <div class="pipeline-kpi-label">Pipeline Value</div>
            <div class="pipeline-kpi-value" style="color:var(--c-primary);">₱{{ number_format($pipelineValue, 0) }}</div>
        </div>
        <div class="pipeline-kpi">
            <div class="pipeline-kpi-label">Win Rate</div>
            <div class="pipeline-kpi-value" style="color:#22C55E;">{{ $winRate }}%</div>
            <div style="font-size:11px; color:#999;">{{ $wonCount }} won / {{ $wonCount + $lostCount }} closed</div>
        </div>
        <div class="pipeline-kpi">
            <div class="pipeline-kpi-label">Won</div>
            <div class="pipeline-kpi-value" style="color:#22C55E;">{{ $wonCount }}</div>
        </div>
        <div class="pipeline-kpi">
            <div class="pipeline-kpi-label">Lost</div>
            <div class="pipeline-kpi-value" style="color:#EF4444;">{{ $lostCount }}</div>
        </div>
        <div class="pipeline-kpi">
            <div class="pipeline-kpi-label">Active Leads</div>
            <div class="pipeline-kpi-value">{{ $totalLeads - $wonCount - $lostCount }}</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:24px; align-items:end;">
        <div style="flex:1; min-width:200px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Search</label>
            <input type="text" name="search" placeholder="Name, email or company..." value="{{ $search }}" style="margin-top:2px;">
        </div>
        <div style="min-width:140px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Source</label>
            <select name="source" style="margin-top:2px;">
                <option value="">All Sources</option>
                @foreach($sources as $src)
                    <option value="{{ $src->source }}" {{ $selectedSource == $src->source ? 'selected' : '' }}>{{ ucfirst($src->source) }}</option>
                @endforeach
            </select>
        </div>
        <div style="min-width:150px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Assigned To</label>
            <select name="assigned_to" style="margin-top:2px;">
                <option value="">Everyone</option>
                @foreach($assignedReps as $rep)
                    <option value="{{ $rep }}" {{ $selectedAssigned == $rep ? 'selected' : '' }}>{{ $rep }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="button" style="margin-top:18px;">Filter</button>
        @if($search || $selectedSource || $selectedAssigned)
            <a href="{{ route('ecommerce.admin.crm.leads.pipeline') }}" class="button alt" style="margin-top:18px;">Clear</a>
        @endif
    </form>

    <!-- Kanban Board -->
    <div class="kanban-grid">
        @php $allStatuses = array_merge($statuses, $closedStatuses); @endphp
        @foreach($allStatuses as $status)
            @php
                $leads = $leadsByStatus[$status] ?? collect();
                $color = \Modules\Ecommerce\CRM\Models\Lead::statusColor($status);
                $label = \Modules\Ecommerce\CRM\Models\Lead::STATUSES[$status] ?? ucfirst($status);
            @endphp
            <div class="kanban-column">
                <div class="kanban-header" style="background:{{ $color }}; color:#fff;">
                    <span class="status-dot" style="background:rgba(255,255,255,0.6);"></span>
                    <span style="font-size:13px; font-weight:700;">{{ $label }}</span>
                    <span class="kanban-count">{{ $leads->count() }}</span>
                </div>
                <div class="kanban-body">
                    @forelse($leads as $lead)
                        <div class="lead-card">
                            <div style="display:flex; align-items:flex-start; justify-content:space-between;">
                                <div>
                                    <div class="lead-card-name">{{ $lead->full_name }}</div>
                                    <div class="lead-card-email">{{ $lead->email }}</div>
                                </div>
                                <div class="lead-card-value">₱{{ number_format($lead->expected_value, 0) }}</div>
                            </div>
                            <div style="display:flex; gap:8px; margin-top:6px; flex-wrap:wrap;">
                                @if($lead->company_name)
                                    <span class="lead-card-source"><i class="ph ph-building"></i> {{ $lead->company_name }}</span>
                                @endif
                                @if($lead->source)
                                    <span class="lead-card-source"><i class="ph ph-flag"></i> {{ ucfirst($lead->source) }}</span>
                                @endif
                                @if($lead->assigned_to)
                                    <span class="lead-card-source"><i class="ph ph-user"></i> {{ $lead->assigned_to }}</span>
                                @endif
                            </div>
                            <div style="margin-top:6px;">
                                <div style="display:flex; align-items:center; gap:6px; font-size:11px; color:#888;">
                                    <span>{{ $lead->probability }}%</span>
                                    <div style="flex:1; height:4px; background:#eee; border-radius:2px;">
                                        <div style="width:{{ $lead->probability }}%; height:4px; background:{{ $color }}; border-radius:2px;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="lead-card-actions">
                                <a href="{{ route('ecommerce.admin.crm.leads.show', $lead->id) }}">View</a>
                                <span style="color:#ddd;">·</span>
                                <a href="{{ route('ecommerce.admin.crm.leads.edit', $lead->id) }}">Edit</a>
                                @if(!$lead->is_closed)
                                    <span style="color:#ddd;">·</span>
                                    <form method="POST" action="{{ route('ecommerce.admin.crm.leads.update-status', $lead->id) }}" style="display:inline;">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $lead->status === 'new' ? 'contacted' : ($lead->status === 'contacted' ? 'qualified' : ($lead->status === 'qualified' ? 'proposal' : ($lead->status === 'proposal' ? 'negotiation' : 'won'))) }}">
                                        <button type="submit" style="background:none; border:none; color:var(--c-primary); font-size:11px; cursor:pointer; padding:0; font-family:inherit;">Advance</button>
                                    </form>
                                @endif
                                @if(in_array($status, ['won', 'lost']) && $lead->customer_id)
                                    <span style="color:#ddd;">·</span>
                                    <a href="{{ route('ecommerce.admin.crm.customers.show', $lead->customer_id) }}" style="color:#22C55E;">Customer</a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center; padding:24px 12px; color:#bbb; font-size:13px;">
                            <i class="ph ph-arrow-circle-right" style="font-size:24px; display:block; margin-bottom:6px; opacity:0.4;"></i>
                            No leads here
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
