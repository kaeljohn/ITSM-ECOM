@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Coupons — CRM — ' . $companyName)

@section('content')
<div style="max-width:1200px; margin:0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Coupons</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">Discount codes & promotions</p>
        </div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('ecommerce.admin.crm.coupons.create') }}" class="button">
                <i class="ph ph-plus"></i> New Coupon
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:24px; align-items:end;">
        <div style="flex:1; min-width:200px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Search</label>
            <input type="text" name="search" placeholder="Coupon code..." value="{{ request('search') }}" style="margin-top:2px;">
        </div>
        <div style="min-width:140px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Status</label>
            <select name="status" style="margin-top:2px;">
                <option value="">All</option>
                @foreach(['active','inactive','expired'] as $st)
                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="button" style="margin-top:18px;">Filter</button>
        @if(request('search') || request('status'))
            <a href="{{ route('ecommerce.admin.crm.coupons') }}" class="button alt" style="margin-top:18px;">Clear</a>
        @endif
    </form>

    <!-- Coupons Table -->
    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Discount</th>
                    <th>Type</th>
                    <th>Uses</th>
                    <th>Min. Order</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td style="font-weight:700; font-family:monospace; font-size:14px;">{{ $coupon->code }}</td>
                        <td style="font-weight:600;">{{ $coupon->display_value }}</td>
                        <td>
                            @php $typeLabels = ['fixed' => 'Fixed', 'percentage' => 'Percentage', 'free_shipping' => 'Free Shipping']; @endphp
                            <span style="font-size:12px; color:var(--c-text-muted);">{{ $typeLabels[$coupon->type] ?? $coupon->type }}</span>
                        </td>
                        <td>
                            <span style="font-weight:600;">{{ $coupon->usage_count }}</span>
                            @if($coupon->max_uses)
                                <span style="color:var(--c-text-muted);">/ {{ $coupon->max_uses }}</span>
                            @endif
                        </td>
                        <td style="font-size:13px; color:var(--c-text-muted);">
                            {{ $coupon->min_order_amount ? '₱'.number_format($coupon->min_order_amount, 0) : '—' }}
                        </td>
                        <td style="font-size:13px;">
                            @if($coupon->expires_at)
                                @if($coupon->is_expired)
                                    <span style="color:#EF4444;">{{ $coupon->expires_at->format('M d, Y') }}</span>
                                @else
                                    <span style="color:var(--c-text-muted);">{{ $coupon->expires_at->diffForHumans() }}</span>
                                @endif
                            @else
                                <span style="color:#aaa;">Never</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusColors = ['active' => '#22C55E', 'inactive' => '#F59E0B', 'expired' => '#EF4444'];
                                $statusColor = $coupon->is_expired ? '#EF4444' : ($statusColors[$coupon->status] ?? '#888');
                            @endphp
                            <span style="display:inline-block; background:{{ $statusColor }}22; color:{{ $statusColor }}; border:1px solid {{ $statusColor }}44; border-radius:4px; padding:2px 8px; font-size:12px; font-weight:600;">
                                {{ $coupon->is_expired ? 'Expired' : ucfirst($coupon->status) }}
                            </span>
                        </td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <a href="{{ route('ecommerce.admin.crm.coupons.edit', $coupon->id) }}" class="button alt" style="padding:4px 10px; font-size:12px;">
                                    <i class="ph ph-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('ecommerce.admin.crm.coupons.destroy', $coupon->id) }}" style="display:inline;" onsubmit="return confirm('Delete this coupon?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button alt" style="padding:4px 10px; font-size:12px; color:#EF4444; border-color:#fca5a5;">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:40px; color:#aaa;">
                            <i class="ph ph-tag" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.4;"></i>
                            No coupons yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">
        {{ $coupons->links() }}
    </div>
</div>
@endsection
