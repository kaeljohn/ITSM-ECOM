@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', $customer->full_name . ' — CRM — ' . $companyName)

@section('head')
<style>
    .crm-section { background: #fff; border: 1px solid var(--c-border); border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
    .crm-label { font-size: 11px; font-weight: 600; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .crm-value { font-size: 15px; font-weight: 600; color: var(--c-text); }
    .crm-tag { display: inline-block; padding: 2px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .crm-comm-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
    .crm-comm-icon { width: 28px; height: 28px; border-radius: 6px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; font-size: 14px; }
</style>
@endsection

@section('content')
<div style="max-width:1100px; margin:0 auto;">
    <!-- Back link -->
    <a href="{{ route('ecommerce.admin.crm.customers') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--c-text-muted); font-size:14px; margin-bottom:24px; text-decoration:none;">
        <i class="ph ph-arrow-left"></i> Back to Customers
    </a>

    <!-- Customer Header -->
    <div style="display:flex; align-items:flex-start; gap:24px; margin-bottom:32px;">
        <div style="width:60px; height:60px; border-radius:50%; background:var(--c-primary); display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; font-weight:700; flex-shrink:0;">
            {{ strtoupper(substr($customer->first_name ?: $customer->email, 0, 1)) }}
        </div>
        <div style="flex:1;">
            <h1 style="font-size:22px; font-weight:600; margin:0 0 4px;">{{ $customer->full_name }}</h1>
            <div style="display:flex; flex-wrap:wrap; gap:16px; font-size:13px; color:var(--c-text-muted);">
                <span><i class="ph ph-envelope"></i> {{ $customer->email ?? '—' }}</span>
                <span><i class="ph ph-phone"></i> {{ $customer->phone ?? '—' }}</span>
                <span><i class="ph ph-flag"></i> {{ $customer->source ? ucfirst($customer->source) : 'Unknown source' }}</span>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                @foreach($customer->tags as $tag)
                    <span class="crm-tag" style="background:{{ $tag->color }}22; color:{{ $tag->color }}; border:1px solid {{ $tag->color }}44;">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px; margin-bottom:24px;">
        <div class="crm-section" style="text-align:center;">
            <div class="crm-label">Total Spent</div>
            <div class="crm-value" style="font-size:22px; color:var(--c-primary);">₱{{ number_format($customer->total_spent, 0) }}</div>
        </div>
        <div class="crm-section" style="text-align:center;">
            <div class="crm-label">Orders</div>
            <div class="crm-value" style="font-size:22px;">{{ $customer->order_count }}</div>
        </div>
        <div class="crm-section" style="text-align:center;">
            <div class="crm-label">Avg Order Value</div>
            <div class="crm-value" style="font-size:22px;">₱{{ number_format($customer->average_order_value, 0) }}</div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px; margin-bottom:24px;">
        <!-- Segments -->
        <div class="crm-section">
            <div class="crm-label">Segments</div>
            @forelse($customer->segments as $segment)
                <span style="display:inline-block; background:#EFF6FF; color:#2563EB; border-radius:4px; padding:2px 10px; font-size:12px; font-weight:600; margin:2px;">
                    {{ $segment->name }}
                </span>
            @empty
                <p style="color:#aaa; font-size:13px;">No segments assigned</p>
            @endforelse
        </div>

        <!-- Recent Communications -->
        <div class="crm-section">
            <div class="crm-label">Recent Communications</div>
            @forelse($customer->communications->take(5) as $comm)
                <div class="crm-comm-row">
                    <div class="crm-comm-icon">
                        <i class="ph ph-{{ $comm->type === 'email' ? 'envelope' : 'chat-text' }}"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:13px; font-weight:600;">{{ $comm->subject ?? '(No subject)' }}</div>
                        <div style="font-size:11px; color:#888;">{{ $comm->created_at->diffForHumans() }} · {{ ucfirst($comm->status) }}</div>
                    </div>
                </div>
            @empty
                <p style="color:#aaa; font-size:13px;">No communications logged yet</p>
            @endforelse
        </div>
    </div>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:20px;">
        <!-- Notes -->
        <div class="crm-section">
            <div class="crm-label">Notes</div>
            <form method="POST" action="{{ route('ecommerce.admin.crm.customers.update', $customer->id) }}">
                @csrf
                @method('PUT')
                <textarea name="notes" rows="4" style="margin-top:4px;">{{ $customer->notes }}</textarea>
                <button type="submit" class="button" style="margin-top:8px;">Save Notes</button>
            </form>
        </div>

        <!-- Reviews -->
        <div class="crm-section">
            <div class="crm-label">Product Reviews</div>
            @forelse($reviews as $review)
                <div style="padding:8px 0; border-bottom:1px solid #f0f0f0;">
                    <div style="display:flex; gap:2px; margin-bottom:4px;">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="ph-fill ph-star" style="color:{{ $i <= $review->rating ? '#F59E0B' : '#ddd' }}; font-size:12px;"></i>
                        @endfor
                        @if($review->approved)
                            <span style="margin-left:auto; font-size:10px; color:#22C55E; font-weight:600;">LIVE</span>
                        @endif
                    </div>
                    @if($review->title)
                        <div style="font-size:13px; font-weight:600;">{{ $review->title }}</div>
                    @endif
                </div>
            @empty
                <p style="color:#aaa; font-size:13px;">No reviews yet</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
