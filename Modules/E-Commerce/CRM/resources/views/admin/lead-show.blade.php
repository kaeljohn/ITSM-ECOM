@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', $lead->full_name . ' — Sales Pipeline — CRM — ' . $companyName)

@section('head')
<style>
    .crm-section { background:#fff; border:1px solid var(--c-border); border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.02); }
    .crm-label { font-size:11px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; }
    .crm-value { font-size:15px; font-weight:600; color:var(--c-text); }
    .timeline-item { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid #f0f0f0; }
    .timeline-dot { width:10px; height:10px; border-radius:50%; margin-top:5px; flex-shrink:0; }
    .timeline-action { font-size:13px; font-weight:600; color:var(--c-text); }
    .timeline-desc { font-size:12px; color:#888; }
    .timeline-time { font-size:11px; color:#aaa; }
</style>
@endsection

@section('content')
<div style="max-width:1100px; margin:0 auto;">
    <!-- Back link -->
    <a href="{{ route('ecommerce.admin.crm.leads.pipeline') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--c-text-muted); font-size:14px; margin-bottom:24px; text-decoration:none;">
        <i class="ph ph-arrow-left"></i> Back to Pipeline
    </a>

    <!-- Lead Header -->
    <div style="display:flex; align-items:flex-start; gap:24px; margin-bottom:32px;">
        <div style="width:60px; height:60px; border-radius:50%; background:{{\Modules\Ecommerce\CRM\Models\Lead::statusColor($lead->status)}}; display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; font-weight:700; flex-shrink:0;">
            {{ strtoupper(substr($lead->first_name ?: $lead->email, 0, 1)) }}
        </div>
        <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <h1 style="font-size:22px; font-weight:600; margin:0;">{{ $lead->full_name }}</h1>
                <span style="display:inline-block; padding:2px 10px; border-radius:5px; font-size:12px; font-weight:700; color:#fff; background:{{\Modules\Ecommerce\CRM\Models\Lead::statusColor($lead->status)}};">
                    {{ $lead->status_label }}
                </span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:16px; font-size:13px; color:var(--c-text-muted); margin-top:6px;">
                <span><i class="ph ph-envelope"></i> {{ $lead->email }}</span>
                <span><i class="ph ph-phone"></i> {{ $lead->phone ?? '—' }}</span>
                @if($lead->company_name)
                    <span><i class="ph ph-building"></i> {{ $lead->company_name }}</span>
                @endif
                @if($lead->source)
                    <span><i class="ph ph-flag"></i> {{ ucfirst($lead->source) }}</span>
                @endif
                @if($lead->assigned_to)
                    <span><i class="ph ph-user"></i> {{ $lead->assigned_to }}</span>
                @endif
            </div>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('ecommerce.admin.crm.leads.edit', $lead->id) }}" class="button alt" style="padding:8px 16px; font-size:13px;">
                <i class="ph ph-pencil"></i> Edit
            </a>
            @if(!$lead->customer_id)
                <form method="POST" action="{{ route('ecommerce.admin.crm.leads.convert', $lead->id) }}" style="display:inline;" onsubmit="return confirm('Convert this lead to a customer? This will mark the lead as won.');">
                    @csrf
                    <button type="submit" class="button" style="background:#22C55E; padding:8px 16px; font-size:13px;">
                        <i class="ph ph-arrows-clockwise"></i> Convert
                    </button>
                </form>
            @else
                <a href="{{ route('ecommerce.admin.crm.customers.show', $lead->customer_id) }}" class="button" style="background:#22C55E; padding:8px 16px; font-size:13px; text-decoration:none;">
                    <i class="ph ph-user-check"></i> View Customer
                </a>
            @endif
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px; margin-bottom:24px;">
        <!-- Deal Details -->
        <div class="crm-section">
            <div class="crm-label">Deal Details</div>
            <div style="margin-top:12px;">
                <div class="crm-label" style="font-size:10px;">Expected Value</div>
                <div class="crm-value" style="font-size:24px; color:var(--c-primary);">₱{{ number_format($lead->expected_value, 0) }}</div>
            </div>
            <div style="margin-top:12px;">
                <div class="crm-label" style="font-size:10px;">Actual Value</div>
                <div class="crm-value" style="font-size:18px;">
                    ₱{{ number_format($lead->actual_value, 0) }}
                    @if($lead->status === 'won') <span style="font-size:11px; color:#22C55E;">(won)</span> @endif
                </div>
            </div>
            <div style="margin-top:16px;">
                <div class="crm-label" style="font-size:10px;">Probability</div>
                <div style="display:flex; align-items:center; gap:8px; margin-top:4px;">
                    <div style="flex:1; height:8px; background:#eee; border-radius:4px;">
                        <div style="width:{{ $lead->probability }}%; height:8px; background:{{\Modules\Ecommerce\CRM\Models\Lead::statusColor($lead->status)}}; border-radius:4px; transition:width 0.3s;"></div>
                    </div>
                    <span style="font-weight:700; font-size:16px;">{{ $lead->probability }}%</span>
                </div>
            </div>
            @if($lead->expected_close_date)
                <div style="margin-top:16px;">
                    <div class="crm-label" style="font-size:10px;">Expected Close</div>
                    <div style="font-size:14px; font-weight:600;">{{ $lead->expected_close_date->format('M d, Y') }}</div>
                </div>
            @endif
            <div style="margin-top:16px;">
                <div class="crm-label" style="font-size:10px;">Days in Stage</div>
                <div style="font-size:14px; font-weight:600;">{{ $lead->days_in_stage }} days</div>
            </div>
        </div>

        <!-- Notes + Activity Timeline -->
        <div style="display:flex; flex-direction:column; gap:20px;">
            <!-- Notes -->
            <div class="crm-section">
                <div class="crm-label">Notes</div>
                @if($lead->notes)
                    <p style="font-size:14px; line-height:1.6; margin-top:8px; white-space:pre-wrap;">{{ $lead->notes }}</p>
                @else
                    <p style="color:#aaa; font-size:13px; margin-top:8px;">No notes for this lead.</p>
                @endif
            </div>

            <!-- Activity Timeline -->
            <div class="crm-section">
                <div class="crm-label">Activity Timeline</div>
                @php $activities = collect($lead->activity_log ?? [])->sortByDesc('timestamp'); @endphp
                @forelse($activities as $activity)
                    <div class="timeline-item">
                        @php
                            $actionType = $activity['action'] ?? '';
                            $dotColor = match(true) {
                                str_contains($actionType, 'status_change') => '#F59E0B',
                                str_contains($actionType, 'created')        => '#3B82F6',
                                str_contains($actionType, 'converted')      => '#22C55E',
                                default                                     => '#888',
                            };
                        @endphp
                        <div class="timeline-dot" style="background:{{ $dotColor }};"></div>
                        <div style="flex:1;">
                            <div class="timeline-action">
                                {{ ucfirst(str_replace('_', ' ', $activity['action'] ?? 'action')) }}
                            </div>
                            @if($activity['description'] ?? null)
                                <div class="timeline-desc">{{ $activity['description'] }}</div>
                            @endif
                            <div class="timeline-time">
                                {{ \Carbon\Carbon::parse($activity['timestamp'] ?? $lead->created_at)->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p style="color:#aaa; font-size:13px; margin-top:8px;">
                        No activity logged yet. Activities are recorded automatically when the lead status changes.
                    </p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="display:flex; gap:12px;">
        <form method="POST" action="{{ route('ecommerce.admin.crm.leads.update-status', $lead->id) }}" style="display:inline;">
            @csrf @method('PATCH')
            <select name="status" onchange="this.form.submit()" style="width:auto; margin:0;">
                @foreach(\Modules\Ecommerce\CRM\Models\Lead::STATUSES as $val => $label)
                    <option value="{{ $val }}" {{ $lead->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="button alt" style="margin-left:8px;">Move</button></noscript>
        </form>

        @if(!$lead->is_closed)
            <form method="POST" action="{{ route('ecommerce.admin.crm.leads.update-status', $lead->id) }}" style="display:inline;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="lost">
                <button type="submit" class="button alt" style="color:#EF4444;" onclick="return confirm('Mark this lead as lost?');">
                    <i class="ph ph-x-circle"></i> Mark as Lost
                </button>
            </form>
        @endif

        @if($lead->is_closed)
            <form method="POST" action="{{ route('ecommerce.admin.crm.leads.update-status', $lead->id) }}" style="display:inline;">
                @csrf @method('PATCH')
                <input type="hidden" name="status" value="new">
                <button type="submit" class="button alt">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reopen
                </button>
            </form>
        @endif

        <form method="POST" action="{{ route('ecommerce.admin.crm.leads.destroy', $lead->id) }}" style="display:inline;" onsubmit="return confirm('Delete this lead permanently?');">
            @csrf @method('DELETE')
            <button type="submit" class="button alt" style="color:#EF4444;">
                <i class="ph ph-trash"></i> Delete
            </button>
        </form>
    </div>
</div>
@endsection
