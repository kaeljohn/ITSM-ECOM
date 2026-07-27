@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $type = request()->input('type', '');
    $status = request()->input('status', '');
    $search = request()->input('search', '');
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Templates — CRM — ' . $companyName)

@section('head')
<style>
    .tpl { max-width: 1100px; margin: 0 auto; }

    .tpl-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
    }
    .tpl-header h1 { font-size: 24px; font-weight: 700; margin: 0; }
    .tpl-header p { color: var(--c-text-muted); font-size: 14px; margin-top: 4px; }

    .tpl-create-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 9px 20px; border: 0; border-radius: 8px;
        background: var(--c-primary); color: #fff;
        font-size: 14px; font-weight: 600; text-decoration: none;
        transition: background 0.15s;
    }
    .tpl-create-btn:hover { background: #1a5aa8; }

    .tpl-filters {
        display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
        align-items: flex-end;
    }
    .tpl-filters .fg { display: flex; flex-direction: column; gap: 3px; }
    .tpl-filters label {
        font-size: 10px; font-weight: 600; color: var(--c-text-muted);
        text-transform: uppercase; letter-spacing: 0.3px; margin: 0;
    }
    .tpl-filters select, .tpl-filters input {
        padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px;
        font-size: 13px; font-family: inherit; color: var(--c-text);
        background: #fff; min-width: 120px;
    }
    .tpl-filters input { min-width: 200px; }
    .tpl-filters select:focus, .tpl-filters input:focus {
        border-color: var(--c-primary); outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .tpl-filters .filter-btn {
        padding: 7px 16px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .tpl-filters .filter-btn:hover { background: #1a5aa8; }
    .tpl-filters .filter-clear {
        padding: 7px 14px; border: 1px solid #d1d5db; border-radius: 6px;
        background: #fff; color: var(--c-text-muted);
        font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none;
    }
    .tpl-filters .filter-clear:hover { background: #f5f5f5; }

    /* Template cards */
    .tpl-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 16px;
    }
    .tpl-card {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 12px; overflow: hidden;
        transition: box-shadow 0.15s;
    }
    .tpl-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.04); }
    .tpl-card-inner { padding: 18px 20px; }
    .tpl-card .card-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        margin-bottom: 10px;
    }
    .tpl-card .card-top .tpl-name {
        font-size: 15px; font-weight: 600; color: var(--c-text);
        text-decoration: none;
    }
    .tpl-card .card-top .tpl-name:hover { color: var(--c-primary); }
    .tpl-card .card-badges {
        display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px;
    }
    .tpl-card .type-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 4px;
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .tpl-card .type-badge.email { background: #eff6ff; color: #2563eb; }
    .tpl-card .type-badge.sms { background: #f0fdf4; color: #16a34a; }
    .tpl-card .status-badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .tpl-card .status-badge .dot { width: 5px; height: 5px; border-radius: 50%; }
    .tpl-card .status-badge.active { background: #f0fdf4; color: #16a34a; }
    .tpl-card .status-badge.active .dot { background: #22c55e; }
    .tpl-card .status-badge.draft { background: #fffbeb; color: #d97706; }
    .tpl-card .status-badge.draft .dot { background: #f59e0b; }
    .tpl-card .status-badge.archived { background: #f9fafb; color: #6b7280; }
    .tpl-card .status-badge.archived .dot { background: #9ca3af; }

    .tpl-card .tpl-subject {
        font-size: 13px; color: var(--c-text-muted); margin-top: 4px;
        font-family: 'SF Mono', 'Fira Code', monospace; font-size: 12px;
        background: #f9fafb; padding: 4px 8px; border-radius: 4px;
    }
    .tpl-card .tpl-body-preview {
        font-size: 12px; color: var(--c-text-muted); margin-top: 6px;
        line-height: 1.5; max-height: 60px; overflow: hidden;
        position: relative;
    }
    .tpl-card .tpl-body-preview::after {
        content: ''; position: absolute; bottom: 0; left: 0; right: 0;
        height: 20px; background: linear-gradient(transparent, #fff);
    }
    .tpl-card .tpl-trigger {
        margin-top: 8px; font-size: 11px;
    }
    .tpl-card .tpl-trigger .trigger-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 4px;
        background: #f3f4f6; color: #6b7280; font-weight: 500;
    }

    .tpl-card .tpl-actions {
        display: flex; gap: 6px; margin-top: 12px; padding-top: 12px;
        border-top: 1px solid #f3f4f6;
    }
    .tpl-card .tpl-actions a, .tpl-card .tpl-actions button {
        padding: 5px 12px; border: 1px solid #e1e3e5; border-radius: 6px;
        background: #fff; font-size: 12px; font-weight: 600;
        color: var(--c-text-muted); text-decoration: none; cursor: pointer;
        display: inline-flex; align-items: center; gap: 4px;
        transition: all 0.1s;
    }
    .tpl-card .tpl-actions a:hover, .tpl-card .tpl-actions button:hover {
        border-color: var(--c-primary); color: var(--c-primary);
    }
    .tpl-card .tpl-actions .del-btn:hover {
        border-color: #dc2626; color: #dc2626; background: #fef2f2;
    }
    .tpl-card .tpl-vars-count {
        margin-left: auto; font-size: 11px; color: var(--c-text-muted);
    }

    .tpl-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 0; font-size: 13px; color: var(--c-text-muted);
        margin-top: 20px;
    }
    .tpl-pagination .pgl { display: flex; gap: 4px; }
    .tpl-pagination .pgl a, .tpl-pagination .pgl span {
        padding: 5px 10px; border-radius: 6px; text-decoration: none;
        font-size: 12px; font-weight: 600; color: var(--c-text);
    }
    .tpl-pagination .pgl a:hover { background: #e5e7eb; }
    .tpl-pagination .pgl span.active { background: var(--c-primary); color: #fff; }

    .tpl-empty {
        text-align: center; padding: 60px 24px; color: var(--c-text-muted);
    }
    .tpl-empty i { font-size: 44px; display: block; margin-bottom: 12px; color: #d1d5db; }

    @media (max-width: 700px) {
        .tpl-grid { grid-template-columns: 1fr; }
        .tpl-filters { flex-direction: column; }
        .tpl-filters input { min-width: 0; width: 100%; }
    }
</style>
@endsection

@section('content')
<div class="tpl">

    <div class="tpl-header">
        <div>
            <h1>Communication Templates</h1>
            <p>Email &amp; SMS templates for automated and manual messaging</p>
        </div>
        <a href="{{ route('ecommerce.admin.crm.templates.create') }}" class="tpl-create-btn">
            <i class="ph ph-plus"></i> Create Template
        </a>
    </div>

    {{-- Filters --}}
    <form class="tpl-filters" method="GET" action="{{ route('ecommerce.admin.crm.templates') }}">
        <div class="fg">
            <label>Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Name or subject...">
        </div>
        <div class="fg">
            <label>Type</label>
            <select name="type">
                <option value="">All</option>
                <option value="email" {{ $type === 'email' ? 'selected' : '' }}>Email</option>
                <option value="sms" {{ $type === 'sms' ? 'selected' : '' }}>SMS</option>
            </select>
        </div>
        <div class="fg">
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </div>
        <button type="submit" class="filter-btn"><i class="ph ph-funnel"></i> Filter</button>
        @if(request()->hasAny(['search','type','status']))
            <a href="{{ route('ecommerce.admin.crm.templates') }}" class="filter-clear"><i class="ph ph-x"></i> Clear</a>
        @endif
    </form>

    {{-- Template cards --}}
    @if($templates->count() > 0)
    <div class="tpl-grid">
        @foreach($templates as $t)
        <div class="tpl-card">
            <div class="tpl-card-inner">
                <div class="card-top">
                    <div>
                        <a href="{{ route('ecommerce.admin.crm.templates.edit', $t->id) }}" class="tpl-name">
                            {{ $t->name }}
                        </a>
                        <div class="card-badges">
                            <span class="type-badge {{ $t->type }}">
                                <i class="ph ph-{{ $t->type === 'email' ? 'envelope' : 'chat-text' }}"></i>
                                {{ $t->type === 'email' ? 'Email' : 'SMS' }}
                            </span>
                            <span class="status-badge {{ $t->status }}">
                                <span class="dot"></span> {{ ucfirst($t->status) }}
                            </span>
                        </div>
                    </div>
                </div>

                @if($t->subject)
                    <div class="tpl-subject">{{ $t->subject }}</div>
                @endif

                @if($t->body)
                    <div class="tpl-body-preview">{{ strip_tags($t->body) }}</div>
                @endif

                @if($t->trigger_event)
                    <div class="tpl-trigger">
                        <span class="trigger-chip">
                            <i class="ph ph-lightning"></i> {{ str_replace('_', ' ', $t->trigger_event) }}
                        </span>
                    </div>
                @endif

                <div class="tpl-actions">
                    <a href="{{ route('ecommerce.admin.crm.templates.edit', $t->id) }}">
                        <i class="ph ph-pencil-simple"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('ecommerce.admin.crm.templates.destroy', $t->id) }}" style="display:inline;" onsubmit="return confirm('Delete template &quot;{{ $t->name }}&quot;?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="del-btn"><i class="ph ph-trash"></i> Delete</button>
                    </form>
                    @if($t->variables && count($t->variables) > 0)
                        <span class="tpl-vars-count">{{ count($t->variables) }} variable(s)</span>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="tpl-pagination">
        <span>Showing {{ $templates->firstItem() }}–{{ $templates->lastItem() }} of {{ $templates->total() }}</span>
        <div class="pgl">{{ $templates->links() }}</div>
    </div>
    @else
    <div class="tpl-empty">
        <i class="ph ph-file-text"></i>
        <p style="margin-bottom:16px;">No templates yet. Create your first email or SMS template.</p>
        <a href="{{ route('ecommerce.admin.crm.templates.create') }}" class="tpl-create-btn" style="display:inline-flex;">
            <i class="ph ph-plus"></i> Create Template
        </a>
    </div>
    @endif
</div>
@endsection
