@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Abandoned Carts — CRM — ' . $companyName)

@section('content')
<div style="max-width:1100px; margin:0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Abandoned Carts</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">Recover lost sales</p>
        </div>
    </div>

    <!-- Status Filter -->
    <form method="GET" style="display:flex; gap:12px; margin-bottom:24px; align-items:end;">
        <div style="min-width:160px;">
            <label style="display:block; margin:0; font-size:12px; color:#888;">Status</label>
            <select name="status" style="margin-top:2px;" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach(['pending','recovered','lost','notified'] as $st)
                    <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                @endforeach
            </select>
        </div>
        @if(request('status'))
            <a href="{{ route('ecommerce.admin.crm.abandoned-carts') }}" class="button alt" style="margin-top:18px;">Clear</a>
        @endif
    </form>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Cart Total</th>
                    <th>Status</th>
                    <th>Abandoned</th>
                    <th>Recovered</th>
                </tr>
            </thead>
            <tbody>
                @forelse($carts as $cart)
                    <tr>
                        <td style="font-weight:600;">
                            @if($cart->customer)
                                <a href="{{ route('ecommerce.admin.crm.customers.show', $cart->customer->id) }}" style="color:var(--c-primary); text-decoration:none;">
                                    {{ $cart->customer->full_name }}
                                </a>
                            @else
                                <span style="color:#aaa;">Guest</span>
                            @endif
                        </td>
                        <td style="color:var(--c-text-muted); font-size:13px;">{{ $cart->email ?? '—' }}</td>
                        <td style="font-weight:600;">₱{{ number_format($cart->cart_total, 0) }}</td>
                        <td>
                            @php
                                $statusColors = ['pending' => '#F59E0B', 'recovered' => '#22C55E', 'lost' => '#EF4444', 'notified' => '#3B82F6'];
                            @endphp
                            <span style="display:inline-block; background:{{ $statusColors[$cart->status] ?? '#888' }}22; color:{{ $statusColors[$cart->status] ?? '#888' }}; border:1px solid {{ $statusColors[$cart->status] ?? '#888' }}44; border-radius:4px; padding:2px 8px; font-size:12px; font-weight:600;">
                                {{ ucfirst($cart->status) }}
                            </span>
                        </td>
                        <td style="font-size:13px; color:var(--c-text-muted);">{{ $cart->abandoned_at->diffForHumans() }}</td>
                        <td style="font-size:13px; color:var(--c-text-muted);">{{ $cart->recovered_at ? $cart->recovered_at->diffForHumans() : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#aaa;">
                            <i class="ph ph-shopping-cart" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.4;"></i>
                            No abandoned carts found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">
        {{ $carts->links() }}
    </div>
</div>
@endsection
