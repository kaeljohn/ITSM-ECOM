@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
@endphp

@extends('ecommerce::admin.layout')

@section('title', 'Segmentation & Tags — CRM — ' . $companyName)

@section('head')
<style>
    .sg { max-width: 1200px; margin: 0 auto; }

    /* ── Two-panel layout ── */
    .sg-panels {
        display: grid;
        grid-template-columns: 360px 1fr;
        gap: 20px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .sg-panels { grid-template-columns: 1fr; }
    }

    /* ── Card ── */
    .sg-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .sg-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .sg-card-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    .sg-card-header h3 i { font-size: 16px; color: var(--c-text-muted); }
    .sg-card-body { padding: 12px 16px; }

    /* ── Tag items ── */
    .sg-tag-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .sg-tag-item:last-child { border-bottom: none; }
    .sg-tag-item .tag-swatch {
        width: 14px;
        height: 14px;
        border-radius: 4px;
        flex-shrink: 0;
        border: 1px solid rgba(0,0,0,0.08);
    }
    .sg-tag-item .tag-name {
        flex: 1;
        font-size: 13px;
        font-weight: 500;
        color: var(--c-text);
    }
    .sg-tag-item .tag-count {
        font-size: 12px;
        color: var(--c-text-muted);
        font-weight: 500;
        white-space: nowrap;
    }
    .sg-tag-item .tag-actions {
        display: flex;
        gap: 2px;
    }
    .sg-tag-item .tag-actions button {
        width: 26px; height: 26px;
        border: 0; border-radius: 5px;
        background: transparent;
        color: var(--c-text-muted);
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px;
        transition: all 0.1s;
    }
    .sg-tag-item .tag-actions button:hover { background: #f3f4f6; color: var(--c-text); }
    .sg-tag-item .tag-actions button.del:hover { background: #fef2f2; color: #dc2626; }

    /* ── Tag create form ── */
    .sg-add-tag {
        display: flex;
        gap: 8px;
        padding: 12px 0 4px;
        border-top: 1px solid #f3f4f6;
        align-items: center;
        flex-wrap: wrap;
    }
    .sg-add-tag input, .sg-add-tag select {
        padding: 6px 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 13px;
        font-family: inherit;
        color: var(--c-text);
    }
    .sg-add-tag input:focus, .sg-add-tag select:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .sg-add-tag input[type="text"] { flex: 1; min-width: 120px; }
    .sg-add-tag input[type="color"] {
        width: 36px; height: 36px;
        padding: 2px; cursor: pointer;
    }
    .sg-add-tag button {
        padding: 6px 14px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
        white-space: nowrap;
    }
    .sg-add-tag button:hover { background: #1a5aa8; }

    /* ── Segment row ── */
    .sg-seg-item {
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .sg-seg-item:last-child { border-bottom: none; }
    .sg-seg-item .seg-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .sg-seg-item .seg-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sg-seg-item .seg-name .auto-badge {
        font-size: 10px; font-weight: 700;
        padding: 1px 6px; border-radius: 4px;
        background: #eff6ff; color: #2563eb;
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .sg-seg-item .seg-count {
        font-size: 13px;
        font-weight: 700;
        color: var(--c-text);
    }
    .sg-seg-item .seg-meta {
        font-size: 12px;
        color: var(--c-text-muted);
        margin-top: 2px;
    }
    .sg-seg-item .seg-actions {
        display: flex;
        gap: 4px;
    }
    .sg-seg-item .seg-actions button {
        padding: 4px 10px; border: 1px solid #e1e3e5; border-radius: 5px;
        background: #fff; font-size: 11px; font-weight: 600;
        color: var(--c-text-muted); cursor: pointer;
        transition: all 0.1s;
    }
    .sg-seg-item .seg-actions button:hover { border-color: #ccc; color: var(--c-text); }
    .sg-seg-item .seg-actions button.del:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

    /* ── RFM Definition card ── */
    .sg-rfm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 8px;
    }
    .sg-rfm-item {
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fafbfc;
    }
    .sg-rfm-item .rfm-label {
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sg-rfm-item .rfm-label .rfm-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .sg-rfm-item .rfm-range {
        font-size: 11px;
        color: var(--c-text-muted);
        margin-top: 2px;
    }

    /* ── Evaluate button ── */
    .sg-evaluate-wrap {
        margin: 20px 0;
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }
    .sg-evaluate-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 22px;
        border: 0;
        border-radius: 8px;
        background: linear-gradient(135deg, #7c3aed, #6366f1);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.15s, transform 0.1s;
        box-shadow: 0 2px 8px rgba(124,58,237,0.2);
    }
    .sg-evaluate-btn:hover { opacity: 0.92; transform: translateY(-1px); }
    .sg-evaluate-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    /* ── Create segment form ── */
    .sg-create-seg {
        padding: 12px 0;
        border-top: 1px solid #f3f4f6;
    }
    .sg-create-seg .cs-row {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    .sg-create-seg input, .sg-create-seg textarea {
        padding: 7px 10px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 13px;
        font-family: inherit;
        color: var(--c-text);
        width: 100%;
    }
    .sg-create-seg input:focus, .sg-create-seg textarea:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .sg-create-seg .cs-row input[type="text"] { flex: 1; }
    .sg-create-seg .cs-row input[type="text"]:first-child { flex: 2; }
    .sg-create-seg button {
        padding: 7px 16px; border: 0; border-radius: 6px;
        background: var(--c-primary); color: #fff;
        font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .sg-create-seg button:hover { background: #1a5aa8; }

    /* ── Evaluation result toast ── */
    .sg-toast {
        display: none;
        padding: 12px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 16px;
    }
    .sg-toast.show { display: flex; align-items: center; gap: 8px; }
    .sg-toast.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .sg-toast.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    /* ── Segment criteria display ── */
    .sg-criteria {
        font-size: 11px;
        color: var(--c-text-muted);
        margin-top: 4px;
        font-family: 'SF Mono', 'Fira Code', monospace;
        background: #f9fafb;
        padding: 4px 8px;
        border-radius: 4px;
        display: inline-block;
    }

    /* Delete confirm overlay */
    .sg-del-overlay {
        display: none;
        position: fixed; inset: 0; z-index: 1000;
        background: rgba(0,0,0,0.3);
        align-items: center; justify-content: center;
    }
    .sg-del-overlay.open { display: flex; }
    .sg-del-box {
        background: #fff; border-radius: 12px;
        padding: 24px; width: 360px; max-width: 90vw;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }
    .sg-del-box h3 { margin: 0 0 8px; font-size: 16px; font-weight: 600; }
    .sg-del-box p { font-size: 13px; color: var(--c-text-muted); margin-bottom: 16px; }
    .sg-del-box .del-actions { display: flex; gap: 8px; justify-content: flex-end; }
    .sg-del-box .del-actions button {
        padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .sg-del-box .del-actions .cancel-btn {
        background: #fff; border: 1px solid #d1d5db; color: var(--c-text);
    }
    .sg-del-box .del-actions .confirm-btn {
        background: #dc2626; border: 0; color: #fff;
    }
    .sg-del-box .del-actions .confirm-btn:hover { background: #b91c1c; }
</style>
@endsection

@section('content')
<div class="sg">

    {{-- ═══ PAGE HEADING ═══ --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px;">
        <div>
            <h1 style="font-size:24px; font-weight:700; margin:0;">Segmentation &amp; Tags</h1>
            <p style="color:var(--c-text-muted); font-size:14px; margin-top:4px;">Manage customer tags, segments, and RFM-based auto-segmentation</p>
        </div>
    </div>

    {{-- Evaluation toast --}}
    <div class="sg-toast" id="eval-toast"></div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{--  TWO-PANEL LAYOUT: LEFT (TAGS) | RIGHT (SEGMENTS + RFM)     --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="sg-panels">

        {{-- ═══ LEFT PANEL: TAGS ═══ --}}
        <div>
            {{-- Tags list --}}
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3><i class="ph ph-tags"></i> Tags <span style="font-size:12px; font-weight:500; color:var(--c-text-muted);">({{ $tags->count() }})</span></h3>
                </div>
                <div class="sg-card-body">
                    @forelse($tags as $tag)
                        <div class="sg-tag-item" id="tag-row-{{ $tag->id }}">
                            <span class="tag-swatch" style="background:{{ $tag->color ?? '#6B7280' }};"></span>
                            <span class="tag-name">{{ $tag->name }}</span>
                            <span class="tag-count">{{ $tag->customers_count }} customers</span>
                            <div class="tag-actions">
                                <button onclick="editTag({{ $tag->id }}, '{{ addslashes($tag->name) }}', '{{ $tag->color ?? '#6B7280' }}')" title="Edit">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button class="del" onclick="confirmDeleteTag({{ $tag->id }}, '{{ addslashes($tag->name) }}')" title="Delete">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div style="text-align:center; padding:20px; color:var(--c-text-muted); font-size:13px;">
                            <i class="ph ph-tag" style="font-size:28px; display:block; margin-bottom:6px; color:#d1d5db;"></i>
                            No tags created yet
                        </div>
                    @endforelse

                    {{-- Add tag form --}}
                    <form class="sg-add-tag" id="tag-create-form" onsubmit="event.preventDefault(); saveTag(this);">
                        @csrf
                        <input type="hidden" name="tag_id" id="tag-edit-id" value="">
                        <input type="text" name="name" id="tag-input" placeholder="Tag name..." required>
                        <select name="color" id="tag-color">
                            <option value="#3B82F6" style="color:#3B82F6;">● Blue</option>
                            <option value="#22C55E" style="color:#22C55E;">● Green</option>
                            <option value="#F59E0B" style="color:#F59E0B;">● Amber</option>
                            <option value="#EF4444" style="color:#EF4444;">● Red</option>
                            <option value="#8B5CF6" style="color:#8B5CF6;">● Purple</option>
                            <option value="#EC4899" style="color:#EC4899;">● Pink</option>
                            <option value="#14B8A6" style="color:#14B8A6;">● Teal</option>
                            <option value="#6B7280" selected style="color:#6B7280;">● Gray</option>
                        </select>
                        <button type="submit" id="tag-save-btn"><i class="ph ph-plus"></i> Add</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══ RIGHT PANEL: SEGMENTS + RFM ═══ --}}
        <div>

            {{-- Evaluate button --}}
            <div class="sg-evaluate-wrap">
                <button class="sg-evaluate-btn" id="eval-btn" onclick="runEvaluation()">
                    <i class="ph ph-lightning"></i> Run RFM Evaluation
                </button>
                <span style="font-size:12px; color:var(--c-text-muted);">
                    Scores every customer (R/F/M 1-5) and reassigns auto-segments
                </span>
            </div>

            {{-- Segments list --}}
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3><i class="ph ph-funnel"></i> Segments <span style="font-size:12px; font-weight:500; color:var(--c-text-muted);">({{ $segments->count() }})</span></h3>
                </div>
                <div class="sg-card-body">
                    @forelse($segments as $segment)
                        <div class="sg-seg-item" id="seg-row-{{ $segment->id }}">
                            <div class="seg-top">
                                <div>
                                    <div class="seg-name">
                                        {{ $segment->name }}
                                        @if($segment->is_auto)
                                            <span class="auto-badge">Auto</span>
                                        @endif
                                    </div>
                                    <div class="seg-meta">
                                        {{ $segment->description ?? 'No description' }}
                                        @if($segment->slug)
                                            &middot; <code style="font-size:11px;">{{ $segment->slug }}</code>
                                        @endif
                                    </div>
                                    @if($segment->criteria)
                                        <div class="sg-criteria">{{ json_encode($segment->criteria) }}</div>
                                    @endif
                                </div>
                                <div style="text-align:right;">
                                    <div class="seg-count">{{ $segment->customers_count }}</div>
                                    <div style="font-size:11px; color:var(--c-text-muted);">customers</div>
                                </div>
                            </div>
                            @if(!$segment->is_auto)
                            <div class="seg-actions" style="margin-top:8px;">
                                <!-- <button onclick="editSegment({{ $segment->id }})">Edit</button> -->
                                <button class="del" onclick="confirmDeleteSeg({{ $segment->id }}, '{{ addslashes($segment->name) }}')">Delete</button>
                            </div>
                            @endif
                        </div>
                    @empty
                        <div style="text-align:center; padding:20px; color:var(--c-text-muted); font-size:13px;">
                            <i class="ph ph-funnel" style="font-size:28px; display:block; margin-bottom:6px; color:#d1d5db;"></i>
                            No segments created yet
                        </div>
                    @endforelse

                    {{-- Create segment form (static only — auto-segments are managed by RFM) --}}
                    <form class="sg-create-seg" id="seg-create-form" onsubmit="event.preventDefault(); saveSegment(this);">
                        @csrf
                        <input type="hidden" name="seg_id" id="seg-edit-id" value="">
                        <div class="cs-row">
                            <input type="text" name="name" id="seg-name" placeholder="Segment name..." required>
                            <input type="text" name="slug" id="seg-slug" placeholder="slug-name" required>
                        </div>
                        <div class="cs-row">
                            <textarea name="description" id="seg-desc" placeholder="Description (optional)" rows="1"></textarea>
                        </div>
                        <button type="submit" id="seg-save-btn"><i class="ph ph-plus"></i> Create Segment</button>
                    </form>
                </div>
            </div>

            {{-- RFM Segment Definitions --}}
            <div class="sg-card">
                <div class="sg-card-header">
                    <h3><i class="ph ph-chart-pie"></i> RFM Segment Definitions</h3>
                </div>
                <div class="sg-card-body">
                    <p style="font-size:12px; color:var(--c-text-muted); margin-bottom:10px;">
                        Customers are scored on <strong>Recency</strong> (R), <strong>Frequency</strong> (F), and <strong>Monetary</strong> (M) — each 1-5 — and assigned to the first matching segment below.
                    </p>
                    <div class="sg-rfm-grid">
                        @foreach($rfmDefinitions as $key => $def)
                            <div class="sg-rfm-item">
                                <div class="rfm-label">
                                    <span class="rfm-dot" style="background:{{ $def['color'] }};"></span>
                                    {{ $def['label'] }}
                                </div>
                                <div class="rfm-range">
                                    R: {{ $def['rfm'][0] }}-{{ $def['rfm'][1] }} &middot;
                                    F: {{ $def['rfm'][2] }}-{{ $def['rfm'][3] }} &middot;
                                    M: {{ $def['rfm'][4] }}-{{ $def['rfm'][5] }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ═══ DELETE CONFIRMATION OVERLAY ═══ --}}
<div class="sg-del-overlay" id="del-overlay">
    <div class="sg-del-box">
        <h3>Confirm Delete</h3>
        <p id="del-message">Are you sure you want to delete this item?</p>
        <div class="del-actions">
            <button class="cancel-btn" onclick="closeDelOverlay()">Cancel</button>
            <button class="confirm-btn" id="del-confirm-btn" onclick="">Delete</button>
        </div>
    </div>
</div>

<script>
    var CSRF_TOKEN = document.querySelector('[name=_token]').value;

    // ── Tag CRUD ──

    function saveTag(form) {
        var id = form.querySelector('[name=tag_id]').value;
        var name = form.querySelector('[name=name]').value.trim();
        var color = form.querySelector('[name=color]').value;
        var btn = form.querySelector('button');
        var isEdit = !!id;

        if (!name) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i>';

        if (isEdit) {
            // Update via PUT API
            fetch('{{ route("ecommerce.admin.crm.api.tags.update", "__ID__") }}'.replace('__ID__', id), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ name: name, color: color }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ph ph-x"></i>';
                }
            })
            .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i>'; });
        } else {
            // Create via POST API
            fetch('{{ route("ecommerce.admin.crm.api.tags.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ name: name, color: color }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success || data.message) {
                    window.location.reload();
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ph ph-x"></i>';
                }
            })
            .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i>'; });
        }
    }

    function editTag(id, name, color) {
        document.getElementById('tag-edit-id').value = id;
        document.getElementById('tag-input').value = name;
        document.getElementById('tag-color').value = color;
        document.getElementById('tag-save-btn').innerHTML = '<i class="ph ph-check"></i> Update';
    }

    function confirmDeleteTag(id, name) {
        document.getElementById('del-message').textContent = 'Delete tag "' + name + '"? It will be removed from all customers.';
        document.getElementById('del-confirm-btn').onclick = function() { deleteTag(id); };
        document.getElementById('del-overlay').classList.add('open');
    }

    function deleteTag(id) {
        fetch('{{ route("ecommerce.admin.crm.api.tags.destroy", "__ID__") }}'.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success || data.message) {
                window.location.reload();
            }
        })
        .catch(function() {});
    }

    // ── Segment CRUD ──

    function saveSegment(form) {
        var id = form.querySelector('[name=seg_id]').value;
        var name = form.querySelector('[name=name]').value.trim();
        var slug = form.querySelector('[name=slug]').value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '-');
        var description = form.querySelector('[name=description]').value.trim();
        var btn = form.querySelector('button');
        var isEdit = !!id;

        if (!name || !slug) return;
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i>';

        if (isEdit) {
            fetch('{{ route("ecommerce.admin.crm.api.segments.update", "__ID__") }}'.replace('__ID__', id), {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ name: name, description: description }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) { window.location.reload(); }
                else { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i>'; }
            })
            .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i>'; });
        } else {
            fetch('{{ route("ecommerce.admin.crm.api.segments.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ name: name, slug: slug, description: description, is_auto: false }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success || data.message) { window.location.reload(); }
                else { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i>'; }
            })
            .catch(function() { btn.disabled = false; btn.innerHTML = '<i class="ph ph-x"></i>'; });
        }
    }

    function confirmDeleteSeg(id, name) {
        document.getElementById('del-message').textContent = 'Delete segment "' + name + '"? It will be removed from all assigned customers.';
        document.getElementById('del-confirm-btn').onclick = function() { deleteSeg(id); };
        document.getElementById('del-overlay').classList.add('open');
    }

    function deleteSeg(id) {
        fetch('{{ route("ecommerce.admin.crm.api.segments.destroy", "__ID__") }}'.replace('__ID__', id), {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success || data.message) { window.location.reload(); }
        })
        .catch(function() {});
    }

    // ── RFM Evaluation ──

    function runEvaluation() {
        var btn = document.getElementById('eval-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Evaluating...';

        fetch('{{ route("ecommerce.admin.crm.api.segments.evaluate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({}),
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var toast = document.getElementById('eval-toast');
            if (data.success) {
                var dist = data.data.segment_distribution || {};
                var distStr = Object.keys(dist).map(function(k) { return k + ': ' + dist[k]; }).join(', ');
                toast.className = 'sg-toast show success';
                toast.innerHTML = '<i class="ph ph-check-circle"></i> Evaluation complete! ' +
                    data.data.customers_scored + ' customers scored, ' +
                    data.data.auto_segments_updated + ' auto-segments updated. ' +
                    (distStr ? '(' + distStr + ')' : '');
            } else {
                toast.className = 'sg-toast show error';
                toast.innerHTML = '<i class="ph ph-x-circle"></i> Evaluation failed.';
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-lightning"></i> Run RFM Evaluation';
            setTimeout(function() { toast.classList.remove('show'); }, 8000);
        })
        .catch(function() {
            var toast = document.getElementById('eval-toast');
            toast.className = 'sg-toast show error';
            toast.innerHTML = '<i class="ph ph-x-circle"></i> Network error.';
            btn.disabled = false;
            btn.innerHTML = '<i class="ph ph-lightning"></i> Run RFM Evaluation';
        });
    }

    // ── Overlay ──
    function closeDelOverlay() {
        document.getElementById('del-overlay').classList.remove('open');
    }
    document.addEventListener('click', function(e) {
        if (e.target === document.getElementById('del-overlay')) closeDelOverlay();
    });

    // ── Auto-slug for segment name ──
    document.addEventListener('DOMContentLoaded', function() {
        var segName = document.getElementById('seg-name');
        var segSlug = document.getElementById('seg-slug');
        if (segName && segSlug) {
            segName.addEventListener('input', function() {
                if (!segSlug.dataset.manual) {
                    segSlug.value = segName.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                }
            });
            segSlug.addEventListener('input', function() { segSlug.dataset.manual = '1'; });
        }
    });
</script>
@endsection
