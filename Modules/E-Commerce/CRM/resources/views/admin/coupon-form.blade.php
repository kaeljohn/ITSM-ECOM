@php
    $crmAdmin = auth('ecommerce_admin')->user();
    $crmCompany = $crmAdmin?->getCompany();
    $companyName = $crmCompany?->company_name ?? 'Nexora';
    $isEditing = $coupon->exists;
@endphp

@extends('ecommerce::admin.layout')

@section('title', ($isEditing ? 'Edit' : 'Create') . ' Coupon — CRM — ' . $companyName)

@section('content')
<div style="max-width:800px; margin:0 auto;">
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:24px;">
        <a href="{{ route('ecommerce.admin.crm.coupons') }}" style="color:var(--c-text-muted); text-decoration:none; font-size:18px;">
            <i class="ph ph-arrow-left"></i>
        </a>
        <div>
            <h1 style="font-size:24px; font-weight:600; margin:0;">{{ $isEditing ? 'Edit' : 'Create' }} Coupon</h1>
            <p style="color:var(--c-text-muted); font-size:13px; margin-top:4px;">{{ $isEditing ? 'Update discount code' : 'Create a new discount code' }}</p>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ $isEditing ? route('ecommerce.admin.crm.coupons.update', $coupon->id) : route('ecommerce.admin.crm.coupons.store') }}">
            @csrf
            @if($isEditing) @method('PUT') @endif

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:0 20px;">
                <!-- Code -->
                <div style="grid-column:span 2;">
                    <label>Coupon Code</label>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" placeholder="e.g. SUMMER25" required maxlength="50" style="font-family:monospace; font-size:16px; font-weight:700; text-transform:uppercase;">
                    <p class="hint">Customers will enter this code at checkout. Auto-uppercased.</p>
                </div>

                <!-- Type -->
                <div>
                    <label>Discount Type</label>
                    <select name="type" required>
                        <option value="fixed" {{ old('type', $coupon->type) === 'fixed' ? 'selected' : '' }}>Fixed Amount (₱)</option>
                        <option value="percentage" {{ old('type', $coupon->type) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="free_shipping" {{ old('type', $coupon->type) === 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                    </select>
                </div>

                <!-- Value -->
                <div>
                    <label>Value</label>
                    <input type="number" name="value" value="{{ old('value', $coupon->value) }}" step="0.01" min="0" placeholder="0.00">
                    <p class="hint">Amount or percentage depending on type.</p>
                </div>

                <!-- Max Discount (percentage only) -->
                <div>
                    <label>Max Discount (for % coupons)</label>
                    <input type="number" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" step="0.01" min="0" placeholder="Leave empty for no cap">
                </div>

                <!-- Min Order -->
                <div>
                    <label>Minimum Order Amount</label>
                    <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" step="0.01" min="0" placeholder="Optional">
                </div>

                <!-- Status -->
                <div>
                    <label>Status</label>
                    <select name="status" required>
                        <option value="active" {{ old('status', $coupon->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $coupon->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- Max Uses -->
                <div>
                    <label>Max Total Uses</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses', $coupon->max_uses) }}" min="1" placeholder="Unlimited">
                </div>

                <!-- Per User Limit -->
                <div>
                    <label>Uses Per Customer</label>
                    <input type="number" name="per_user_limit" value="{{ old('per_user_limit', $coupon->per_user_limit ?? 1) }}" min="1">
                </div>

                <!-- Segment -->
                <div>
                    <label>Restrict to Segment</label>
                    <select name="segment_id">
                        <option value="">All Customers</option>
                        @foreach($segments as $seg)
                            <option value="{{ $seg->id }}" {{ old('segment_id', $coupon->segment_id) == $seg->id ? 'selected' : '' }}>{{ $seg->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Start -->
                <div>
                    <label>Valid From</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}">
                </div>

                <!-- Expires -->
                <div>
                    <label>Expires At</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}">
                </div>
            </div>

            <!-- Description -->
            <div>
                <label>Description <span style="color:#888; font-weight:400;">(internal notes)</span></label>
                <textarea name="description" rows="3" placeholder="Internal notes about this coupon...">{{ old('description', $coupon->description) }}</textarea>
            </div>

            <!-- Usage Stats (edit only) -->
            @if($isEditing)
                <div style="margin-top:16px; padding:12px; background:#f9fafb; border-radius:8px; display:flex; gap:24px;">
                    <div>
                        <span style="font-size:11px; color:#888; text-transform:uppercase; font-weight:600;">Used</span>
                        <div style="font-size:18px; font-weight:700;">{{ $coupon->usage_count }}</div>
                    </div>
                    @if($coupon->max_uses)
                        <div>
                            <span style="font-size:11px; color:#888; text-transform:uppercase; font-weight:600;">Remaining</span>
                            <div style="font-size:18px; font-weight:700;">{{ max(0, $coupon->max_uses - $coupon->usage_count) }}</div>
                        </div>
                    @endif
                    <div>
                        <span style="font-size:11px; color:#888; text-transform:uppercase; font-weight:600;">Created</span>
                        <div style="font-size:14px; font-weight:600; margin-top:2px;">{{ $coupon->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
            @endif

            <div style="margin-top:24px; display:flex; gap:8px;">
                <button type="submit" class="button">{{ $isEditing ? 'Update Coupon' : 'Create Coupon' }}</button>
                <a href="{{ route('ecommerce.admin.crm.coupons') }}" class="button alt">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
