@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Templates — CRM — ' . $companyName)

@section('content')
<div style="max-width:1100px; margin:0 auto;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">Communication Templates</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">Email & SMS templates for automated messaging</p>
        </div>
    </div>

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Trigger Event</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td style="font-weight:600;">{{ $template->name }}</td>
                        <td>
                            <span style="font-size:12px; color:var(--c-text-muted);">{{ $template->type === 'email' ? '📧 Email' : '📱 SMS' }}</span>
                        </td>
                        <td style="color:var(--c-text-muted); font-size:13px;">{{ $template->subject ?? '—' }}</td>
                        <td>
                            @if($template->trigger_event)
                                <span style="display:inline-block; background:#EFF6FF; color:#2563EB; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:600; text-transform:uppercase;">
                                    {{ str_replace('_', ' ', $template->trigger_event) }}
                                </span>
                            @else
                                <span style="color:#aaa;">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusColors = ['draft' => '#F59E0B', 'active' => '#22C55E', 'archived' => '#888'];
                                $color = $statusColors[$template->status] ?? '#888';
                            @endphp
                            <span style="display:inline-block; background:{{ $color }}22; color:{{ $color }}; border:1px solid {{ $color }}44; border-radius:4px; padding:2px 8px; font-size:12px; font-weight:600;">
                                {{ ucfirst($template->status) }}
                            </span>
                        </td>
                        <td style="font-size:13px; color:var(--c-text-muted);">{{ $template->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#aaa;">
                            <i class="ph ph-file-text" style="font-size:32px; display:block; margin-bottom:8px; opacity:0.4;"></i>
                            No templates yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:20px;">
        {{ $templates->links() }}
    </div>
</div>
@endsection
