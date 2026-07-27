@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $status = request()->input('status', '');
    $priority = request()->input('priority', '');
    $search = request()->input('search', '');
    $category = request()->input('category', '');
    $assigned = request()->input('assigned_to', '');
    $unassigned = request()->boolean('unassigned');
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Tickets — CRM — ' . $companyName)

@section('head')
<style>
    .tk { max-width: 1200px; margin: 0 auto; }

    /* KPI bar */
    .tk-kpi-row {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 12px; margin-bottom: 24px;
    }
    .tk-kpi {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 10px; padding: 14px 16px;
    }
    .tk-kpi .kpi-label {
        font-size: 10px; font-weight: 600; color: var(--c-text-muted);
        text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;
    }
    .tk-kpi .kpi-value {
        font-size: 22px; font-weight: 700; color: var(--c-text); line-height: 1.1;
    }
    .tk-kpi .kpi-sub { font-size: 11px; color: var(--c-text-muted); margin-top: 2px; }

    /* Filter bar */
    .tk-filters {
        display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;
        align-items: flex-end;
    }
    .tk-filters .filter-group { display: flex; flex-direction: column; gap: 3px; }
    .tk-filters label {
        font-size: 10px; font-weight: 600; color: var(--c-text-muted);
        text-transform: uppercase; letter-spacing: 0.3px; margin: 0;
    }
    .tk-filters select, .tk-filters input {
        padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px;
        font-size: 13px; font-family: inherit; color: var(--c-text);
        background: #fff; min-width: 120px;
    }
    .tk-filters input { min-width: 200px; }
    .tk-filters select:focus, .tk-filters input:focus {
        border-color: var(--c-primary); outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .tk-filters .filter-btn {
        padding: 7px 16px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .tk-filters .filter-btn:hover { background: #1a5aa8; }
    .tk-filters .filter-clear {
        padding: 7px 14px; border: 1px solid #d1d5db; border-radius: 6px;
        background: #fff; color: var(--c-text-muted);
        font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none;
        display: inline-flex; align-items: center; gap: 5px;
    }
    .tk-filters .filter-clear:hover { background: #f5f5f5; }

    /* Table */
    .tk-table-wrap {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 12px; overflow: hidden;
    }
    .tk-table { width: 100%; border-collapse: collapse; }
    .tk-table thead th {
        padding: 10px 16px; font-size: 11px; font-weight: 600;
        color: var(--c-text-muted); text-transform: uppercase;
        letter-spacing: 0.3px; border-bottom: 1px solid var(--c-border);
        background: #fafbfc; text-align: left; white-space: nowrap;
    }
    .tk-table tbody tr { transition: background 0.08s; }
    .tk-table tbody tr:hover { background: #f8fafc; }
    .tk-table tbody td {
        padding: 12px 16px; border-bottom: 1px solid #f1f3f5;
        font-size: 13px; color: var(--c-text); vertical-align: middle;
    }
    .tk-table tbody tr:last-child td { border-bottom: none; }

    .tk-table .customer-cell {
        display: flex; flex-direction: column;
    }
    .tk-table .customer-cell .name {
        font-weight: 500; color: var(--c-text); text-decoration: none;
    }
    .tk-table .customer-cell .name:hover { color: var(--c-primary); }
    .tk-table .customer-cell .email {
        font-size: 11px; color: var(--c-text-muted);
    }

    .tk-table .subject-cell { max-width: 280px; }
    .tk-table .subject-cell .subj {
        font-weight: 500; overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; display: block;
    }
    .tk-table .subject-cell .cat {
        font-size: 11px; color: var(--c-text-muted); margin-top: 1px;
    }

    /* Status and priority badges (duplicated from dashboard for isolation) */
    .badge-st {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
        cursor: pointer; position: relative;
    }
    .badge-st .dot { width: 5px; height: 5px; border-radius: 50%; }
    .badge-st.open { background: #eff6ff; color: #2563eb; }
    .badge-st.open .dot { background: #3b82f6; }
    .badge-st.pending { background: #fffbeb; color: #d97706; }
    .badge-st.pending .dot { background: #f59e0b; }
    .badge-st.resolved { background: #f0fdf4; color: #16a34a; }
    .badge-st.resolved .dot { background: #22c55e; }
    .badge-st.closed { background: #f9fafb; color: #6b7280; }
    .badge-st.closed .dot { background: #9ca3af; }
    .badge-pr {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .badge-pr .dot { width: 5px; height: 5px; border-radius: 50%; }
    .badge-pr.urgent { background: #fef2f2; color: #dc2626; }
    .badge-pr.urgent .dot { background: #ef4444; }
    .badge-pr.high { background: #fffbeb; color: #d97706; }
    .badge-pr.high .dot { background: #f59e0b; }
    .badge-pr.normal { background: #eff6ff; color: #2563eb; }
    .badge-pr.normal .dot { background: #3b82f6; }
    .badge-pr.low { background: #f9fafb; color: #6b7280; }
    .badge-pr.low .dot { background: #9ca3af; }

    /* SLA age */
    .sla-age { font-size: 12px; white-space: nowrap; }
    .sla-age .hours { font-weight: 600; }
    .sla-age.urgent-sla .hours { color: #dc2626; }
    .sla-age.warning-sla .hours { color: #f59e0b; }
    .sla-age.ok-sla .hours { color: #22c55e; }

    /* Inline status dropdown */
    .status-dropdown {
        display: none; position: absolute; top: 100%; left: 0; z-index: 20;
        background: #fff; border: 1px solid #e1e3e5; border-radius: 8px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1); padding: 4px; min-width: 110px;
    }
    .badge-st:hover .status-dropdown { display: block; }
    .status-dropdown .sd-item {
        display: block; width: 100%; padding: 6px 10px; border: 0;
        background: transparent; font-size: 12px; font-weight: 500;
        color: var(--c-text); cursor: pointer; border-radius: 4px;
        text-align: left; font-family: inherit;
    }
    .status-dropdown .sd-item:hover { background: #f4f6f8; }
    .status-dropdown .sd-item.active { background: #f0f4ff; color: var(--c-primary); font-weight: 600; }

    /* Action buttons */
    .tk-actions {
        display: flex; gap: 4px; flex-wrap: nowrap;
    }
    .tk-action-btn {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border: 0; border-radius: 6px;
        background: transparent; color: var(--c-text-muted); font-size: 16px;
        cursor: pointer; transition: all 0.1s;
    }
    .tk-action-btn:hover { background: #f3f4f6; color: var(--c-text); }
    .tk-action-btn.notes:hover { color: #2563eb; background: #eff6ff; }

    /* Pagination */
    .tk-pagination {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; border-top: 1px solid var(--c-border);
        background: #fafbfc; font-size: 13px; color: var(--c-text-muted);
    }
    .tk-pagination .pagination-links { display: flex; gap: 4px; }
    .tk-pagination .pagination-links a, .tk-pagination .pagination-links span {
        padding: 5px 10px; border-radius: 6px; text-decoration: none;
        font-size: 12px; font-weight: 600; color: var(--c-text);
    }
    .tk-pagination .pagination-links a:hover { background: #e5e7eb; }
    .tk-pagination .pagination-links span.active { background: var(--c-primary); color: #fff; }

    /* ── Note Modal ── */
    .modal-overlay {
        display: none; position: fixed; z-index: 1000;
        inset: 0; background: rgba(0,0,0,0.35);
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 14px; width: 520px; max-width: 90vw;
        max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        animation: modalIn 0.15s ease-out;
    }
    @keyframes modalIn { from { opacity: 0; transform: scale(0.95) translateY(10px); } to { opacity: 1; transform: scale(1) translateY(0); } }
    .modal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px; border-bottom: 1px solid var(--c-border);
    }
    .modal-header h3 { font-size: 16px; font-weight: 600; margin: 0; }
    .modal-header .close-btn {
        width: 30px; height: 30px; border: 0; border-radius: 6px;
        background: transparent; font-size: 18px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: var(--c-text-muted);
    }
    .modal-header .close-btn:hover { background: #f3f4f6; }
    .modal-body { padding: 16px 20px; }
    .modal-body .note-item {
        padding: 10px 0; border-bottom: 1px solid #f3f4f6;
    }
    .modal-body .note-item:last-child { border-bottom: none; }
    .modal-body .note-item .n-body {
        font-size: 13px; color: var(--c-text); line-height: 1.5;
    }
    .modal-body .note-item .n-meta {
        font-size: 11px; color: var(--c-text-muted); margin-top: 4px;
        display: flex; align-items: center; gap: 8px;
    }
    .modal-body .note-item .n-meta .internal-tag {
        font-size: 10px; font-weight: 600; text-transform: uppercase;
        padding: 1px 6px; border-radius: 4px;
        background: #fffbeb; color: #d97706;
    }
    .modal-footer {
        padding: 12px 20px; border-top: 1px solid var(--c-border);
        display: flex; gap: 8px;
    }
    .modal-footer textarea {
        flex: 1; border: 1px solid #d1d5db; border-radius: 8px;
        padding: 8px 10px; font-size: 13px; font-family: inherit;
        resize: vertical; min-height: 60px;
    }
    .modal-footer textarea:focus {
        border-color: var(--c-primary); outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .modal-footer button {
        align-self: flex-end; padding: 8px 16px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap;
    }
    .modal-footer button:hover { background: #1a5aa8; }
    .modal-footer button:disabled { opacity: 0.5; cursor: not-allowed; }

    /* Empty state */
    .tk-empty {
        text-align: center; padding: 48px 24px; color: var(--c-text-muted);
    }
    .tk-empty i { font-size: 40px; display: block; margin-bottom: 10px; color: #d1d5db; }
    .tk-empty p { font-size: 14px; }

    @media (max-width: 900px) {
        .tk-kpi-row { grid-template-columns: repeat(2, 1fr); }
        .tk-filters { flex-direction: column; }
        .tk-filters input { min-width: 0; width: 100%; }
    }
</style>
@endsection

@section('content')
<div class="tk">

    {{-- ═══ PAGE HEADING ═══ --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:700; margin:0;">Tickets</h1>
            <p style="color:var(--c-text-muted); font-size:14px; margin-top:4px;">Manage support tickets and customer inquiries</p>
        </div>
        <button class="create-ticket-btn" onclick="openCreateTicketModal()" style="padding:9px 18px; border:0; border-radius:8px; background:var(--c-primary); color:#fff; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:background 0.1s;">
            <i class="ph ph-plus"></i> New Ticket
        </button>
    </div>

    {{-- ═══ KPI BAR ═══ --}}
    <div class="tk-kpi-row">
        <div class="tk-kpi">
            <div class="kpi-label">Total Tickets</div>
            <div class="kpi-value" style="color:var(--c-primary);">{{ $totalTickets }}</div>
            <div class="kpi-sub">All time</div>
        </div>
        <div class="tk-kpi">
            <div class="kpi-label">Active</div>
            <div class="kpi-value" style="color:#2563eb;">{{ $activeTickets }}</div>
            <div class="kpi-sub">Open + Pending</div>
        </div>
        <div class="tk-kpi">
            <div class="kpi-label">Urgent</div>
            <div class="kpi-value" style="color:#dc2626;">{{ $urgentTickets }}</div>
            <div class="kpi-sub">Needs immediate attention</div>
        </div>
        <div class="tk-kpi">
            <div class="kpi-label">Unassigned</div>
            <div class="kpi-value" style="color:#f59e0b;">{{ $unassignedTickets }}</div>
            <div class="kpi-sub">Awaiting assignment</div>
        </div>
    </div>

    {{-- ═══ FILTERS ═══ --}}
    <form class="tk-filters" method="GET" action="{{ route('ecommerce.admin.crm.tickets') }}">
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search subject or customer...">
        </div>
        <div class="filter-group">
            <label>Status</label>
            <select name="status">
                <option value="">All Active</option>
                <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="resolved" {{ $status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Priority</label>
            <select name="priority">
                <option value="">All</option>
                <option value="urgent" {{ $priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                <option value="high" {{ $priority === 'high' ? 'selected' : '' }}>High</option>
                <option value="normal" {{ $priority === 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="low" {{ $priority === 'low' ? 'selected' : '' }}>Low</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Category</label>
            <select name="category">
                <option value="">All</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ $category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label>Assigned To</label>
            <select name="assigned_to">
                <option value="">All</option>
                <option value="__unassigned__" {{ $unassigned ? 'selected' : '' }}>Unassigned</option>
                @foreach($assignedUsers as $user)
                    <option value="{{ $user }}" {{ $assigned === $user ? 'selected' : '' }}>{{ $user }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="filter-btn"><i class="ph ph-funnel"></i> Filter</button>
        @if(request()->hasAny(['search','status','priority','category','assigned_to','unassigned']))
            <a href="{{ route('ecommerce.admin.crm.tickets') }}" class="filter-clear"><i class="ph ph-x"></i> Clear</a>
        @endif
    </form>

    {{-- ═══ TICKETS TABLE ═══ --}}
    <div class="tk-table-wrap">
        @if($tickets->count() > 0)
        <table class="tk-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Assigned</th>
                    <th>SLA</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tickets as $ticket)
                @php
                    $slaHours = $ticket->age_in_hours;
                    $slaClass = $slaHours >= 48 ? 'urgent-sla' : ($slaHours >= 24 ? 'warning-sla' : 'ok-sla');
                @endphp
                <tr>
                    <td>
                        <div class="customer-cell">
                            <a href="{{ route('ecommerce.admin.crm.customers.show', $ticket->customer_id) }}" class="name">
                                {{ $ticket->customer->first_name ?? '—' }} {{ $ticket->customer->last_name ?? '' }}
                            </a>
                            <span class="email">{{ $ticket->customer->email ?? '' }}</span>
                        </div>
                    </td>
                    <td class="subject-cell">
                        <span class="subj" title="{{ $ticket->subject }}">{{ $ticket->subject }}</span>
                        @if($ticket->category)
                            <span class="cat">{{ $ticket->category }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge-st {{ $ticket->status }}" onclick="event.stopPropagation();" data-ticket-id="{{ $ticket->id }}">
                            <span class="dot"></span> {{ $ticket->status_label }}
                            <span class="status-dropdown">
                                @foreach(['open','pending','resolved','closed'] as $s)
                                    <button type="button" class="sd-item {{ $ticket->status === $s ? 'active' : '' }}"
                                        onclick="updateTicketStatus({{ $ticket->id }}, '{{ $s }}')">
                                        {{ ucfirst($s) }}
                                    </button>
                                @endforeach
                            </span>
                        </span>
                    </td>
                    <td>
                        <span class="badge-pr {{ $ticket->priority }}">
                            <span class="dot"></span> {{ $ticket->priority_label }}
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--c-text-muted);">
                        {{ $ticket->assigned_to ?? '—' }}
                    </td>
                    <td>
                        <span class="sla-age {{ $slaClass }}">
                            @if($slaHours < 1)
                                <span class="hours">&lt;1h</span>
                            @elseif($slaHours < 24)
                                <span class="hours">{{ floor($slaHours) }}h</span>
                            @else
                                <span class="hours">{{ floor($slaHours / 24) }}d {{ floor($slaHours % 24) }}h</span>
                            @endif
                        </span>
                    </td>
                    <td style="font-size:12px; color:var(--c-text-muted); white-space:nowrap;">
                        {{ $ticket->created_at->format('M j') }}
                        <br><span style="font-size:11px;">{{ $ticket->created_at->diffForHumans() }}</span>
                    </td>
                    <td>
                        <div class="tk-actions">
                            <button class="tk-action-btn notes" title="View notes"
                                onclick="openNoteModal({{ $ticket->id }}, '{{ addslashes($ticket->subject) }}')">
                                <i class="ph ph-chat-text"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="tk-pagination">
            <span>Showing {{ $tickets->firstItem() }}–{{ $tickets->lastItem() }} of {{ $tickets->total() }}</span>
            <div class="pagination-links">
                {{ $tickets->links() }}
            </div>
        </div>
        @else
            <div class="tk-empty">
                <i class="ph ph-ticket"></i>
                <p>No tickets found matching your filters.</p>
            </div>
        @endif
    </div>
</div>

{{-- ═══ CREATE TICKET MODAL ═══ --}}
<div class="modal-overlay" id="create-ticket-modal">
    <div class="modal-box" style="width:520px;">
        <div class="modal-header">
            <h3><i class="ph ph-plus-circle" style="margin-right:6px;"></i> Create New Ticket</h3>
            <button class="close-btn" onclick="closeCreateTicketModal()"><i class="ph ph-x"></i></button>
        </div>
        <form id="create-ticket-form" onsubmit="event.preventDefault(); submitCreateTicket();">
            @csrf
            <div class="modal-body">
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Customer <span style="color:#dc2626;">*</span></label>
                    <select name="customer_id" id="ct-customer" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-family:inherit; color:var(--c-text); background:#fff;">
                        <option value="">Select a customer...</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->first_name }} {{ $c->last_name }} ({{ $c->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Subject <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="subject" id="ct-subject" required placeholder="Brief description of the issue..." style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-family:inherit; color:var(--c-text);">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Priority</label>
                        <select name="priority" id="ct-priority" style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-family:inherit; color:var(--c-text); background:#fff;">
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Category</label>
                        <select name="category" id="ct-category" style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-family:inherit; color:var(--c-text); background:#fff;">
                            <option value="">Select...</option>
                            <option value="account">Account</option>
                            <option value="billing">Billing</option>
                            <option value="shipping">Shipping</option>
                            <option value="product">Product</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Channel</label>
                    <select name="channel" id="ct-channel" style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-family:inherit; color:var(--c-text); background:#fff;">
                        <option value="email">Email</option>
                        <option value="chat">Chat</option>
                        <option value="phone">Phone</option>
                        <option value="portal">Portal</option>
                        <option value="social">Social</option>
                    </select>
                </div>
                <div style="margin-bottom:8px;">
                    <label style="font-size:12px; font-weight:600; color:var(--c-text); display:block; margin-bottom:4px;">Description (optional)</label>
                    <textarea name="description" id="ct-description" placeholder="Detailed description of the issue..." rows="4" style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; font-family:inherit; color:var(--c-text); resize:vertical;"></textarea>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:flex-end;">
                <button type="button" class="filter-clear" onclick="closeCreateTicketModal()" style="padding:8px 16px; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:var(--c-text-muted); font-size:13px; font-weight:600; cursor:pointer;">Cancel</button>
                <button type="submit" id="ct-submit-btn" style="padding:8px 18px; border:0; border-radius:6px; background:var(--c-primary); color:#fff; font-size:13px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    <i class="ph ph-check"></i> Create Ticket
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ NOTE MODAL ═══ --}}
<div class="modal-overlay" id="note-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3><i class="ph ph-chat-text" style="margin-right:6px;"></i> Notes — <span id="note-modal-subject"></span></h3>
            <button class="close-btn" onclick="closeNoteModal()"><i class="ph ph-x"></i></button>
        </div>
        <div class="modal-body" id="note-modal-body">
            <div style="text-align:center; padding:20px; color:var(--c-text-muted);">
                <i class="ph ph-spinner ph-spin" style="font-size:24px;"></i>
                <p style="margin-top:8px;">Loading notes...</p>
            </div>
        </div>
        <div class="modal-footer">
            <textarea id="note-input" placeholder="Add a note... (will be marked as internal)"></textarea>
            <button id="note-submit-btn" onclick="addNote()">
                <i class="ph ph-paper-plane-tilt"></i> Add
            </button>
        </div>
    </div>
</div>

<script>
    var currentTicketId = null;

    // ── Open note modal ──
    function openNoteModal(ticketId, subject) {
        currentTicketId = ticketId;
        document.getElementById('note-modal-subject').textContent = subject;
        document.getElementById('note-modal').classList.add('open');
        document.getElementById('note-modal-body').innerHTML =
            '<div style="text-align:center; padding:20px; color:var(--c-text-muted);">' +
            '<i class="ph ph-spinner ph-spin" style="font-size:24px;"></i>' +
            '<p style="margin-top:8px;">Loading notes...</p></div>';
        document.getElementById('note-input').value = '';

        loadNotes(ticketId);
    }

    function closeNoteModal() {
        document.getElementById('note-modal').classList.remove('open');
        currentTicketId = null;
    }

    // ── Load notes ──
    function loadNotes(ticketId) {
        var notesUrl = '{{ route("ecommerce.admin.crm.api.tickets.notes", "__ID__") }}';
            fetch(notesUrl.replace('__ID__', ticketId))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var body = document.getElementById('note-modal-body');
                if (data.success && data.data && data.data.length > 0) {
                    var html = '';
                    data.data.forEach(function(note) {
                        html += '<div class="note-item">';
                        html += '<div class="n-body">' + (note.body || '—') + '</div>';
                        html += '<div class="n-meta">';
                        html += '<span>' + (note.author_name || 'System') + '</span>';
                        html += '<span>&middot;</span>';
                        html += '<span>' + (note.created_at ? timeAgo(note.created_at) : '') + '</span>';
                        if (note.is_internal) {
                            html += '<span class="internal-tag">Internal</span>';
                        }
                        html += '</div></div>';
                    });
                    body.innerHTML = html;
                } else {
                    body.innerHTML = '<div style="text-align:center; padding:30px; color:var(--c-text-muted);">' +
                        '<i class="ph ph-chat-text" style="font-size:32px; display:block; margin-bottom:8px; color:#d1d5db;"></i>' +
                        '<p>No notes yet. Add the first note below.</p></div>';
                }
            })
            .catch(function() {
                document.getElementById('note-modal-body').innerHTML =
                    '<div style="text-align:center; padding:20px; color:#dc2626;">Error loading notes.</div>';
            });
    }

    // ── Add note ──
    function addNote() {
        var input = document.getElementById('note-input');
        var btn = document.getElementById('note-submit-btn');
        var body = input.value.trim();
        if (!body || !currentTicketId) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';

        var addNoteUrl = '{{ route("ecommerce.admin.crm.api.tickets.notes.store", "__ID__") }}';
        fetch(addNoteUrl.replace('__ID__', currentTicketId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name=_token]').value,
            },
            body: JSON.stringify({ body: body, is_internal: true, author_name: '{{ $crmAdmin?->first_name ?? "Admin" }}' }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success || data.message) {
                input.value = '';
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-paper-plane-tilt"></i> Add';
                loadNotes(currentTicketId);
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-x"></i> Error';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-x"></i> Error';
        });
    }

    // ── Create Ticket Modal ──
    function openCreateTicketModal() {
        document.getElementById('create-ticket-modal').classList.add('open');
        document.getElementById('ct-customer').focus();
    }

    function closeCreateTicketModal() {
        document.getElementById('create-ticket-modal').classList.remove('open');
        document.getElementById('create-ticket-form').reset();
    }

    function submitCreateTicket() {
        var btn = document.getElementById('ct-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Creating...';

        var data = {
            customer_id: document.getElementById('ct-customer').value,
            subject: document.getElementById('ct-subject').value.trim(),
            priority: document.getElementById('ct-priority').value,
            category: document.getElementById('ct-category').value,
            channel: document.getElementById('ct-channel').value,
            description: document.getElementById('ct-description').value.trim(),
        };

        if (!data.customer_id || !data.subject) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-check"></i> Create Ticket';
            return;
        }

        var storeUrl = '{{ route("ecommerce.admin.crm.api.tickets.store") }}';
        fetch(storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name=_token]').value,
            },
            body: JSON.stringify(data),
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (resp.success || resp.message) {
                window.location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="ph ph-check"></i> Create Ticket';
                alert('Error creating ticket.');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-check"></i> Create Ticket';
            alert('Network error.');
        });
    }

    // ── Update ticket status inline ──
    function updateTicketStatus(ticketId, newStatus) {
        var updateUrl = '{{ route("ecommerce.admin.crm.api.tickets.update", "__ID__") }}';
        fetch(updateUrl.replace('__ID__', ticketId), {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name=_token]').value,
            },
            body: JSON.stringify({ status: newStatus }),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success || data.message) {
                window.location.reload();
            }
        })
        .catch(function() {});
    }

    // ── Simple time ago helper (since moment/carbon not available in JS) ──
    function timeAgo(isoString) {
        var date = new Date(isoString);
        var now = new Date();
        var seconds = Math.floor((now - date) / 1000);
        if (seconds < 60) return 'just now';
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm ago';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h ago';
        var days = Math.floor(hours / 24);
        if (days < 30) return days + 'd ago';
        return date.toLocaleDateString();
    }

    // ── Close modal on overlay click ──
    document.addEventListener('click', function(e) {
        var modal = document.getElementById('note-modal');
        if (e.target === modal) closeNoteModal();
    });
</script>
@endsection
