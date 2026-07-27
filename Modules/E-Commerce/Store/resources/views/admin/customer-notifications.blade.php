@php
    $admin = auth('ecommerce_admin')->user();
    $company = $admin?->getCompany();
    $companyName = $company?->company_name ?? 'Store';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Customer Notifications — ' . $companyName)

@section('head')
<style>
    .cn-page { max-width: 860px; margin: 0 auto; }
    .cn-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; gap: 16px; flex-wrap: wrap; }
    .cn-header h1 { font-size: 24px; font-weight: 700; color: var(--c-text); margin: 0; display: flex; align-items: center; gap: 10px; }
    .cn-header h1 i { color: var(--c-primary); }

    .cn-create-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 28px;
    }
    .cn-create-card h2 { font-size: 16px; font-weight: 600; margin: 0 0 16px; color: var(--c-text); display: flex; align-items: center; gap: 8px; }

    .cn-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
    .cn-form-grid .full-width { grid-column: 1 / -1; }

    .cn-list { display: flex; flex-direction: column; gap: 8px; }
    .cn-card {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 18px;
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.1s;
    }
    .cn-card .cn-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; flex-shrink: 0;
    }
    .cn-card .cn-icon.blue { background: #eff6ff; color: #2563eb; }
    .cn-card .cn-icon.green { background: #f0fdf4; color: #16a34a; }
    .cn-card .cn-icon.amber { background: #fffbeb; color: #d97706; }
    .cn-card .cn-icon.purple { background: #f5f3ff; color: #7c3aed; }
    .cn-card .cn-icon.red { background: #fef2f2; color: #dc2626; }
    .cn-card .cn-icon.teal { background: #f0fdfa; color: #14b8a6; }
    .cn-card .cn-icon.gray { background: #f3f4f6; color: #6b7280; }

    .cn-card .cn-content { flex: 1; min-width: 0; }
    .cn-card .cn-title { font-size: 14px; font-weight: 600; color: var(--c-text); margin-bottom: 2px; }
    .cn-card .cn-body { font-size: 13px; color: var(--c-text-muted); line-height: 1.4; margin-bottom: 4px; }
    .cn-card .cn-meta { display: flex; align-items: center; gap: 10px; font-size: 11px; color: var(--c-text-muted); }
    .cn-card .cn-meta .broadcast-badge {
        padding: 1px 7px; border-radius: 4px;
        background: #eff6ff; color: #2563eb;
        font-weight: 600; font-size: 10px; text-transform: uppercase;
    }

    .cn-card .cn-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .cn-card .cn-delete-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 6px;
        border: 0; background: transparent;
        color: var(--c-text-muted); cursor: pointer;
        transition: all 0.1s; font-size: 14px;
    }
    .cn-card .cn-delete-btn:hover { background: #fef2f2; color: #dc2626; }

    .cn-empty {
        text-align: center; padding: 48px 24px;
        background: #fff; border: 1px solid var(--c-border); border-radius: 12px;
    }
    .cn-empty i { font-size: 36px; color: #d1d5db; margin-bottom: 10px; display: block; }
    .cn-empty h3 { font-size: 16px; font-weight: 600; color: var(--c-text); margin-bottom: 4px; }
    .cn-empty p { font-size: 13px; color: var(--c-text-muted); }

    @media (max-width: 640px) { .cn-form-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="cn-page">
    <div class="cn-header">
        <h1><i class="ph ph-megaphone"></i> Customer Notifications</h1>
    </div>

    {{-- Create Form --}}
    <div class="cn-create-card">
        <h2><i class="ph ph-plus-circle" style="color: var(--c-primary);"></i> Send a Notification</h2>
        <form method="POST" action="{{ route('ecommerce.admin.customer-notifications.store') }}">
            @csrf
            <div class="cn-form-grid">
                <label class="full-width" style="margin-top: 0;">
                    Title <span style="color: #dc2626;">*</span>
                    <input name="title" value="{{ old('title') }}" placeholder="e.g. New Arrivals Are Here!" required maxlength="255">
                </label>
                <label class="full-width" style="margin-top: 12px;">
                    Message
                    <textarea name="body" rows="3" placeholder="Write a short message for your customers..." maxlength="5000" style="resize: vertical;">{{ old('body') }}</textarea>
                </label>
                <label style="margin-top: 12px;">
                    Link URL (optional)
                    <input name="link" value="{{ old('link') }}" placeholder="e.g. /collections">
                </label>
                <label style="margin-top: 12px;">
                    Icon
                    <select name="icon">
                        <option value="ph-megaphone">📢 Megaphone</option>
                        <option value="ph-gift">🎁 Gift</option>
                        <option value="ph-tag">🏷️ Tag</option>
                        <option value="ph-truck">🚚 Truck</option>
                        <option value="ph-star">⭐ Star</option>
                        <option value="ph-warning-circle">⚠️ Warning</option>
                        <option value="ph-info">ℹ️ Info</option>
                        <option value="ph-bell">🔔 Bell</option>
                    </select>
                </label>
                <label style="margin-top: 12px;">
                    Badge Color
                    <select name="icon_color">
                        <option value="blue">Blue</option>
                        <option value="green">Green</option>
                        <option value="amber">Amber</option>
                        <option value="purple">Purple</option>
                        <option value="red">Red</option>
                        <option value="teal">Teal</option>
                    </select>
                </label>
                <div class="full-width" style="margin-top: 20px; display: flex; gap: 10px; align-items: center;">
                    <button type="submit" class="button" style="padding: 10px 24px;">
                        <i class="ph ph-paper-plane-right"></i> Send to All Customers
                    </button>
                    <span class="hint" style="margin: 0; font-size: 12px;">
                        <i class="ph ph-users"></i> This notification will appear for all customers immediately.
                    </span>
                </div>
            </div>
        </form>
    </div>

    {{-- Notification List --}}
    <h2 style="font-size: 16px; font-weight: 600; color: var(--c-text); margin-bottom: 12px;">
        <i class="ph ph-clock-counter-clockwise"></i> Sent Notifications
    </h2>

    <div class="cn-list">
        @forelse($notifications as $notif)
            <div class="cn-card">
                <div class="cn-icon {{ $notif->icon_color ?? 'blue' }}">
                    <i class="ph {{ $notif->icon ?? 'ph-megaphone' }}"></i>
                </div>
                <div class="cn-content">
                    <div class="cn-title">{{ $notif->title }}</div>
                    @if($notif->body)
                        <div class="cn-body">{{ Str::limit($notif->body, 120) }}</div>
                    @endif
                    <div class="cn-meta">
                        <span class="broadcast-badge"><i class="ph ph-users" style="font-size: 10px;"></i> Broadcast</span>
                        <span>{{ $notif->created_at->diffForHumans() }}</span>
                        @if($notif->link)
                            <span>· <a href="{{ $notif->link }}" target="_blank" style="color: var(--c-primary); text-decoration: none;">{{ $notif->link }}</a></span>
                        @endif
                    </div>
                </div>
                <div class="cn-actions">
                    <form method="POST" action="{{ route('ecommerce.admin.customer-notifications.destroy', $notif->id) }}" onsubmit="return confirm('Delete this notification?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="cn-delete-btn" title="Delete">
                            <i class="ph ph-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="cn-empty">
                <i class="ph ph-megaphone-simple"></i>
                <h3>No notifications sent yet</h3>
                <p>Use the form above to send your first broadcast notification to all customers.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
