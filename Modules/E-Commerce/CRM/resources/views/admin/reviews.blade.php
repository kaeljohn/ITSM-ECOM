@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Product Reviews — CRM — ' . $companyName)

@section('content')
<div style="max-width:1100px; margin:0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Product Reviews</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">Manage customer feedback</p>
        </div>
        <a href="{{ route('ecommerce.admin.crm.reviews', ['pending' => true]) }}" class="button alt">
            <i class="ph ph-clock"></i> Pending Approval
        </a>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                    <tr>
                        <td style="font-weight:600;">
                            @if($review->customer)
                                <a href="{{ route('ecommerce.admin.crm.customers.show', $review->customer->id) }}" style="color:var(--c-primary); text-decoration:none;">
                                    {{ $review->customer->full_name }}
                                </a>
                            @else
                                <span style="color:#aaa;">User #{{ $review->user_id }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:2px;">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="ph-fill ph-star" style="color:{{ $i <= $review->rating ? '#F59E0B' : '#ddd' }}; font-size:14px;"></i>
                                @endfor
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600;">{{ $review->title ?? '—' }}</div>
                            @if($review->body)
                                <div style="font-size:12px; color:#888; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $review->body }}</div>
                            @endif
                        </td>
                        <td>
                            @if($review->approved)
                                <span style="display:inline-block; background:#22C55E22; color:#22C55E; border:1px solid #22C55E44; border-radius:4px; padding:2px 8px; font-size:12px; font-weight:600;">LIVE</span>
                            @else
                                <span style="display:inline-block; background:#F59E0B22; color:#F59E0B; border:1px solid #F59E0B44; border-radius:4px; padding:2px 8px; font-size:12px; font-weight:600;">Pending</span>
                            @endif
                        </td>
                        <td style="font-size:13px; color:var(--c-text-muted);">{{ $review->created_at->diffForHumans() }}</td>
                        <td>
                            @unless($review->approved)
                                <form method="POST" action="{{ route('ecommerce.admin.crm.reviews.approve', $review->id) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="button" style="padding:4px 14px; font-size:12px; background:#22C55E;">Approve</button>
                                </form>
                            @else
                                <span style="font-size:12px; color:#22C55E;">✓ Approved</span>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#aaa;">
                            <i class="ph ph-star" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.4;"></i>
                            No reviews yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">
        {{ $reviews->links() }}
    </div>
</div>
@endsection
