@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $isEdit = $template->exists;
@endphp

@extends('ecommerce::admin.layout')

@section('title', ($isEdit ? 'Edit' : 'Create') . ' Template — CRM — ' . $companyName)

@section('head')
<style>
    .tmpl { max-width: 900px; margin: 0 auto; }

    .tmpl-card {
        background: #fff; border: 1px solid var(--c-border);
        border-radius: 12px; overflow: hidden; margin-bottom: 20px;
    }
    .tmpl-card-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }
    .tmpl-card-header h3 {
        font-size: 14px; font-weight: 600; color: var(--c-text);
        display: flex; align-items: center; gap: 8px; margin: 0;
    }
    .tmpl-card-body { padding: 20px; }

    .tmpl-form-row { margin-bottom: 16px; }
    .tmpl-form-row label {
        display: block; font-size: 12px; font-weight: 600;
        color: var(--c-text); margin-bottom: 4px;
    }
    .tmpl-form-row input, .tmpl-form-row select, .tmpl-form-row textarea {
        width: 100%; padding: 8px 12px; border: 1px solid #d1d5db;
        border-radius: 6px; font-size: 13px; font-family: inherit;
        color: var(--c-text); transition: border-color 0.15s;
    }
    .tmpl-form-row input:focus, .tmpl-form-row select:focus, .tmpl-form-row textarea:focus {
        border-color: var(--c-primary); outline: none;
        box-shadow: 0 0 0 2px rgba(27,111,200,0.1);
    }
    .tmpl-form-row textarea { resize: vertical; min-height: 200px; font-family: 'SF Mono', 'Fira Code', monospace; }

    .tmpl-grid-2 {
        display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
    }

    /* Variable chips */
    .tmpl-vars {
        display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px;
    }
    .tmpl-var-chip {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 8px; border-radius: 5px; border: 1px solid #e1e3e5;
        background: #f9fafb; font-size: 11px; font-weight: 500;
        color: var(--c-text); cursor: pointer; transition: all 0.12s;
        font-family: 'SF Mono', 'Fira Code', monospace;
    }
    .tmpl-var-chip:hover {
        border-color: var(--c-primary); background: #eff6ff; color: var(--c-primary);
    }
    .tmpl-var-chip .v-desc {
        font-size: 10px; color: var(--c-text-muted); font-family: Inter, Arial, sans-serif;
    }

    /* Preview panel */
    .tmpl-preview {
        background: #f9fafb; border: 1px solid #e5e7eb;
        border-radius: 8px; padding: 16px; margin-top: 12px;
        min-height: 100px;
    }
    .tmpl-preview .preview-subject {
        font-size: 14px; font-weight: 600; color: var(--c-text);
        margin-bottom: 8px; padding-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
    }
    .tmpl-preview .preview-body {
        font-size: 13px; color: var(--c-text); line-height: 1.6;
        white-space: pre-wrap;
    }
    .tmpl-preview .preview-empty {
        color: var(--c-text-muted); font-size: 13px; text-align: center;
        padding: 20px;
    }

    .tmpl-save-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 24px; border: 0; border-radius: 8px;
        background: var(--c-primary); color: #fff;
        font-size: 14px; font-weight: 600; cursor: pointer;
        transition: background 0.15s;
    }
    .tmpl-save-btn:hover { background: #1a5aa8; }
    .tmpl-save-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .tmpl-back {
        display: inline-flex; align-items: center; gap: 6px;
        color: var(--c-text-muted); font-size: 13px; font-weight: 500;
        margin-bottom: 20px; text-decoration: none; transition: color 0.15s;
    }
    .tmpl-back:hover { color: var(--c-primary); }
</style>
@endsection

@section('content')
<div class="tmpl">
    <a href="{{ route('ecommerce.admin.crm.templates') }}" class="tmpl-back">
        <i class="ph ph-arrow-left"></i> Back to Templates
    </a>

    <h1 style="font-size:24px; font-weight:700; margin:0 0 24px;">
        {{ $isEdit ? 'Edit' : 'Create' }} Template
    </h1>

    <form method="POST" action="{{ $isEdit ? route('ecommerce.admin.crm.templates.update', $template->id) : route('ecommerce.admin.crm.templates.store') }}">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <div style="display:grid; grid-template-columns: 1.5fr 1fr; gap:20px; align-items:start;">
            {{-- Left: Main form --}}
            <div>
                {{-- Basic info --}}
                <div class="tmpl-card">
                    <div class="tmpl-card-header">
                        <h3><i class="ph ph-file-text"></i> Template Details</h3>
                    </div>
                    <div class="tmpl-card-body">
                        <div class="tmpl-grid-2">
                            <div class="tmpl-form-row">
                                <label>Template Name *</label>
                                <input type="text" name="name" value="{{ old('name', $template->name) }}" required placeholder="e.g. Welcome Email">
                            </div>
                            <div class="tmpl-form-row">
                                <label>Type *</label>
                                <select name="type" required>
                                    @foreach($fields['types'] as $val => $label)
                                        <option value="{{ $val }}" {{ (old('type', $template->type) === $val) ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="tmpl-form-row">
                            <label>Subject / Title</label>
                            <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" placeholder="e.g. Welcome to @{{company.name}}, @{{customer.first_name}}!">
                        </div>

                        <div class="tmpl-form-row">
                            <label>Body * <span style="font-weight:400; color:var(--c-text-muted);">— use &#123;&#123;variables&#125;&#125; for dynamic content</span></label>
                            <textarea name="body" rows="12" placeholder="Dear @{{customer.first_name}},&#10;&#10;Welcome to @{{company.name}}!&#10;&#10;We're excited to have you on board.&#10;&#10;Best regards,&#10;The @{{company.name}} Team">{{ old('body', $template->body) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Save --}}
                <div style="display:flex; align-items:center; gap:12px;">
                    <button type="submit" class="tmpl-save-btn">
                        <i class="ph ph-{{ $isEdit ? 'check' : 'plus' }}"></i>
                        {{ $isEdit ? 'Update Template' : 'Create Template' }}
                    </button>
                    @if($isEdit)
                        <a href="{{ route('ecommerce.admin.crm.templates') }}" style="color:var(--c-text-muted); font-size:13px;">Cancel</a>
                    @endif
                </div>
            </div>

            {{-- Right: Settings + Variables + Preview --}}
            <div>
                {{-- Settings --}}
                <div class="tmpl-card">
                    <div class="tmpl-card-header">
                        <h3><i class="ph ph-gear"></i> Settings</h3>
                    </div>
                    <div class="tmpl-card-body">
                        <div class="tmpl-form-row">
                            <label>Status</label>
                            <select name="status" required>
                                @foreach($fields['statuses'] as $val => $label)
                                    <option value="{{ $val }}" {{ (old('status', $template->status ?: 'draft') === $val) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="tmpl-form-row">
                            <label>Trigger Event</label>
                            <select name="trigger_event">
                                @foreach($fields['triggerEvents'] as $val => $label)
                                    <option value="{{ $val }}" {{ (old('trigger_event', $template->trigger_event) === $val) ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div style="font-size:11px; color:var(--c-text-muted); margin-top:3px;">Select an event to auto-send this template when the event occurs.</div>
                        </div>
                    </div>
                </div>

                {{-- Available variables --}}
                <div class="tmpl-card">
                    <div class="tmpl-card-header">
                        <h3><i class="ph ph-code"></i> Available Variables</h3>
                        <span style="font-size:11px; color:var(--c-text-muted);">Click to insert</span>
                    </div>
                    <div class="tmpl-card-body">
                        <div class="tmpl-vars">
                            @foreach($fields['availableVariables'] as $var => $desc)
                                <span class="tmpl-var-chip" onclick="insertVariable('{{ $var }}')" title="{{ $desc }}">
                                    {{ $var }} <span class="v-desc">{{ $desc }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Test send --}}
                <div class="tmpl-card">
                    <div class="tmpl-card-header">
                        <h3><i class="ph ph-paper-plane-tilt"></i> Test Send</h3>
                    </div>
                    <div class="tmpl-card-body">
                        <form method="POST" action="{{ route('ecommerce.admin.crm.templates.test-send') }}" onsubmit="return confirm('Send test to the specified recipient?')">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $template->id }}">
                            <div class="tmpl-form-row">
                                <label>Send to Email</label>
                                <input type="email" name="email" placeholder="test@example.com" value="{{ $crmAdmin?->email ?? '' }}">
                            </div>
                            <button type="submit" class="tmpl-var-chip" style="cursor:pointer; padding:6px 14px;">
                                <i class="ph ph-paper-plane-tilt"></i> Send Test
                            </button>
                            @if(!$isEdit)
                                <div style="font-size:11px; color:var(--c-text-muted); margin-top:6px;">Save the template first before testing.</div>
                            @endif
                        </form>
                    </div>
                </div>

                {{-- Live preview --}}
                <div class="tmpl-card">
                    <div class="tmpl-card-header">
                        <h3><i class="ph ph-eye"></i> Preview</h3>
                        <button type="button" class="tmpl-var-chip" onclick="refreshPreview()" style="cursor:pointer;">
                            <i class="ph ph-arrows-clockwise"></i> Refresh
                        </button>
                    </div>
                    <div class="tmpl-card-body">
                        <div class="tmpl-preview" id="preview-panel">
                            <div class="preview-empty">
                                <i class="ph ph-eye-slash" style="font-size:24px; display:block; margin-bottom:6px; color:#d1d5db;"></i>
                                Fill in the body and click Refresh to preview
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    function insertVariable(variable) {
        var bodyField = document.querySelector('[name=body]');
        var subjectField = document.querySelector('[name=subject]');
        var activeEl = document.activeElement;

        if (activeEl === subjectField) {
            insertAtCursor(subjectField, variable);
        } else {
            insertAtCursor(bodyField, variable);
        }
    }

    function insertAtCursor(field, text) {
        var start = field.selectionStart;
        var end = field.selectionEnd;
        field.value = field.value.substring(0, start) + text + field.value.substring(end);
        field.selectionStart = field.selectionEnd = start + text.length;
        field.focus();
    }

    function refreshPreview() {
        var panel = document.getElementById('preview-panel');
        panel.innerHTML = '<div style="text-align:center; padding:20px; color:var(--c-text-muted);"><i class="ph ph-spinner ph-spin" style="font-size:20px;"></i></div>';

        var formData = new FormData();
        formData.append('subject', document.querySelector('[name=subject]').value);
        formData.append('body', document.querySelector('[name=body]').value);
        formData.append('_token', document.querySelector('[name=_token]').value);

        fetch('{{ route("ecommerce.admin.crm.templates.preview") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('[name=_token]').value },
            body: formData,
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var html = '';
                if (data.data.subject) {
                    html += '<div class="preview-subject">' + escapeHtml(data.data.subject) + '</div>';
                }
                html += '<div class="preview-body">' + escapeHtml(data.data.body) + '</div>';
                panel.innerHTML = html;
            } else {
                panel.innerHTML = '<div style="color:#dc2626; padding:20px;">Preview failed.</div>';
            }
        })
        .catch(function() {
            panel.innerHTML = '<div style="color:#dc2626; padding:20px;">Preview failed.</div>';
        });
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>
@endsection
