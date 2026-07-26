@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $isEditing = isset($lead) && $lead->exists;
@endphp

@extends('ecommerce::admin.layout')

@section('title', ($isEditing ? 'Edit' : 'New') . ' Lead — CRM — ' . $companyName)

@section('content')
<div style="max-width:800px; margin:0 auto;">
    <a href="{{ route('ecommerce.admin.crm.leads.pipeline') }}" style="display:inline-flex; align-items:center; gap:6px; color:var(--c-text-muted); font-size:14px; margin-bottom:24px; text-decoration:none;">
        <i class="ph ph-arrow-left"></i> Back to Pipeline
    </a>

    <h1 style="font-size:24px; font-weight:600; margin-bottom:24px;">{{ $isEditing ? 'Edit Lead' : 'New Lead' }}</h1>

    <form method="POST" action="{{ $isEditing ? route('ecommerce.admin.crm.leads.update', $lead->id) : route('ecommerce.admin.crm.leads.store') }}" class="card">
        @csrf
        @if($isEditing) @method('PUT') @endif

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
            <div>
                <label>First Name *</label>
                <input type="text" name="first_name" value="{{ old('first_name', $lead->first_name) }}" required>
                @error('first_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Last Name *</label>
                <input type="text" name="last_name" value="{{ old('last_name', $lead->last_name) }}" required>
                @error('last_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Email *</label>
                <input type="email" name="email" value="{{ old('email', $lead->email) }}" required>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Phone</label>
                <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}">
                @error('phone') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Company</label>
                <input type="text" name="company_name" value="{{ old('company_name', $lead->company_name) }}" placeholder="Company name">
                @error('company_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Job Title</label>
                <input type="text" name="job_title" value="{{ old('job_title', $lead->job_title) }}" placeholder="e.g. CEO, Manager">
                @error('job_title') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 20px; margin-top:8px;">
            <div>
                <label>Status *</label>
                <select name="status" required>
                    @foreach(\Modules\Ecommerce\CRM\Models\Lead::STATUSES as $val => $label)
                        <option value="{{ $val }}" {{ old('status', $lead->status ?: 'new') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Source</label>
                <select name="source">
                    <option value="">Select source...</option>
                    @foreach(['website','referral','social','walk-in','call-in','email','event','other'] as $src)
                        <option value="{{ $src }}" {{ old('source', $lead->source) == $src ? 'selected' : '' }}>{{ ucfirst($src) }}</option>
                    @endforeach
                </select>
                @error('source') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Assigned To</label>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $lead->assigned_to) }}" placeholder="Sales rep name">
                @error('assigned_to') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:0 20px; margin-top:8px;">
            <div>
                <label>Expected Value (₱)</label>
                <input type="number" name="expected_value" step="0.01" min="0" value="{{ old('expected_value', $lead->expected_value ?: 0) }}">
                @error('expected_value') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Probability (%)</label>
                <input type="number" name="probability" min="0" max="100" value="{{ old('probability', $lead->probability ?: 10) }}">
                <p class="hint">0–100% chance of closing</p>
                @error('probability') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label>Expected Close Date</label>
                <input type="date" name="expected_close_date" value="{{ old('expected_close_date', $lead->expected_close_date?->format('Y-m-d')) }}">
                @error('expected_close_date') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        @if($isEditing)
            <div style="margin-top:8px;">
                <label>Actual Value (₱) — set when won</label>
                <input type="number" name="actual_value" step="0.01" min="0" value="{{ old('actual_value', $lead->actual_value ?: 0) }}">
                @error('actual_value') <div class="error">{{ $message }}</div> @enderror
            </div>
        @endif

        @if($isEditing && $customers->isNotEmpty())
            <div style="margin-top:8px;">
                <label>Link to Existing Customer</label>
                <select name="customer_id">
                    <option value="">— No customer linked —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ old('customer_id', $lead->customer_id) == $c->id ? 'selected' : '' }}>
                            {{ $c->full_name }} ({{ $c->email }})
                        </option>
                    @endforeach
                </select>
                <p class="hint">Link this lead to an existing CRM customer profile</p>
                @error('customer_id') <div class="error">{{ $message }}</div> @enderror
            </div>
        @endif

        <div style="margin-top:8px;">
            <label>Notes</label>
            <textarea name="notes" rows="4" style="margin-top:4px;">{{ old('notes', $lead->notes) }}</textarea>
            @error('notes') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div style="display:flex; gap:12px; margin-top:24px;">
            <button type="submit" class="button">{{ $isEditing ? 'Update Lead' : 'Create Lead' }}</button>
            <a href="{{ route('ecommerce.admin.crm.leads.pipeline') }}" class="button alt">Cancel</a>
        </div>
    </form>
</div>
@endsection
