@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';
@endphp
@extends('ecommerce::admin.layout', ['title' => 'Storefront Listing', 'heading' => $listing->exists ? 'Edit Listing' : 'Add Storefront Listing'])

@section('content')
<style>
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 24px;
        align-items: start;
    }

    .form-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        overflow: hidden;
    }

    .form-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--c-border);
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fafbfc;
    }

    .form-card-header i {
        font-size: 18px;
        color: var(--c-primary);
    }

    .form-card-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        margin: 0;
    }

    .form-card-body {
        padding: 20px 24px 24px;
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        font-weight: 600;
        color: var(--c-text);
    }

    .form-group .hint {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: var(--c-text-muted);
        line-height: 1.4;
    }

    .form-group .field-error {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: #dc2626;
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        margin-top: 0;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 12px;
        color: var(--c-text);
        font: inherit;
        font-size: 14px;
        transition: border-color 0.15s, box-shadow 0.15s;
        background: #fff;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        border-color: var(--c-primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(27, 111, 200, 0.1);
    }

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
        line-height: 1.5;
    }

    .form-group.input-error input,
    .form-group.input-error textarea,
    .form-group.input-error select {
        border-color: #dc2626;
    }

    .form-group.input-error input:focus,
    .form-group.input-error textarea:focus,
    .form-group.input-error select:focus {
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }

    /* BOM Preview Banner */
    .bom-preview {
        display: none;
        margin-top: 10px;
        padding: 12px 16px;
        background: #f0f7ff;
        border: 1px solid #b8d8fc;
        border-radius: 8px;
        font-size: 13px;
        gap: 10px;
        align-items: flex-start;
    }

    .bom-preview.visible {
        display: flex;
    }

    .bom-preview i {
        color: var(--c-primary);
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .bom-preview-content {
        flex: 1;
    }

    .bom-preview-content strong {
        display: block;
        font-size: 14px;
        color: var(--c-text);
        margin-bottom: 2px;
    }

    .bom-preview-content span {
        color: var(--c-text-muted);
        font-size: 12px;
    }

    /* Image Upload Drop Zone */
    .upload-zone {
        position: relative;
        border: 2px dashed #d1d5db;
        border-radius: 10px;
        padding: 32px 24px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
        background: #fafafa;
    }

    .upload-zone:hover,
    .upload-zone.dragover {
        border-color: var(--c-primary);
        background: #f0f7ff;
    }

    .upload-zone i {
        font-size: 40px;
        color: #9ca3af;
        display: block;
        margin-bottom: 12px;
    }

    .upload-zone:hover i {
        color: var(--c-primary);
    }

    .upload-zone .upload-label {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
    }

    .upload-zone .upload-hint {
        font-size: 12px;
        color: var(--c-text-muted);
        margin-top: 4px;
    }

    .upload-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .image-preview {
        display: none;
        margin-top: 14px;
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid var(--c-border);
    }

    .image-preview.visible {
        display: block;
    }

    .image-preview img {
        width: 100%;
        max-height: 200px;
        object-fit: cover;
        display: block;
    }

    .image-preview .remove-image {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 0;
        background: rgba(0,0,0,0.6);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
    }

    .image-preview .remove-image:hover {
        background: rgba(220, 38, 38, 0.8);
    }

    /* Status Selector Pills */
    .status-pills {
        display: flex;
        gap: 10px;
    }

    .status-pills input[type="radio"] {
        display: none;
    }

    .status-pills label {
        flex: 1;
        text-align: center;
        padding: 10px 16px;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        background: #fafafa;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin: 0;
    }

    .status-pills label .dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .status-pills input[type="radio"]:checked + label {
        border-color: currentColor;
        background: #fff;
    }

    .status-pills label[data-status="draft"] {
        color: #6b7280;
    }

    .status-pills label[data-status="draft"] .dot {
        background: #9ca3af;
    }

    .status-pills input[type="radio"][value="draft"]:checked + label {
        border-color: #6b7280;
        background: #f9fafb;
    }

    .status-pills label[data-status="active"] {
        color: #16a34a;
    }

    .status-pills label[data-status="active"] .dot {
        background: #16a34a;
    }

    .status-pills input[type="radio"][value="active"]:checked + label {
        border-color: #16a34a;
        background: #f0fdf4;
    }

    .status-pills label[data-status="archived"] {
        color: #dc2626;
    }

    .status-pills label[data-status="archived"] .dot {
        background: #dc2626;
    }

    .status-pills input[type="radio"][value="archived"]:checked + label {
        border-color: #dc2626;
        background: #fef2f2;
    }

    /* Sidebar Card */
    .sidebar-card {
        background: #fff;
        border: 1px solid var(--c-border);
        border-radius: 12px;
        overflow: hidden;
        position: sticky;
        top: 88px;
    }

    .sidebar-card-header {
        padding: 18px 24px;
        border-bottom: 1px solid var(--c-border);
        background: #fafbfc;
    }

    .sidebar-card-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: var(--c-text);
        margin: 0;
    }

    .sidebar-card-body {
        padding: 20px 24px;
    }

    .sidebar-info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 13px;
    }

    .sidebar-info-row:last-child {
        border-bottom: none;
    }

    .sidebar-info-row .info-label {
        color: var(--c-text-muted);
    }

    .sidebar-info-row .info-value {
        font-weight: 600;
        color: var(--c-text);
    }

    /* Action Bar */
    .action-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 0;
        margin-top: 8px;
        border-top: 1px solid var(--c-border);
    }

    .action-bar .btn-group {
        display: flex;
        gap: 10px;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 0;
        border-radius: 8px;
        padding: 10px 20px;
        background: var(--c-text);
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s, transform 0.1s;
    }

    .btn-primary:hover {
        background: #1a2a47;
    }

    .btn-primary:active {
        transform: scale(0.98);
    }

    .btn-primary i {
        font-size: 16px;
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 10px 20px;
        background: #fff;
        color: var(--c-text);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: background 0.15s, border-color 0.15s;
    }

    .btn-secondary:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }

    .btn-secondary i {
        font-size: 16px;
        color: var(--c-text-muted);
    }

    /* Auto-fill flash animation */
    @keyframes fieldFlash {
        0%, 100% { background: #fff; }
        50% { background: #f0f7ff; }
    }

    .field-autofilled {
        animation: fieldFlash 0.6s ease-in-out 2;
    }
</style>

<form method="post" enctype="multipart/form-data" action="{{ $listing->exists ? route('ecommerce.admin.listings.update', $listing) : route('ecommerce.admin.listings.store') }}">
    @csrf
    @if ($listing->exists) @method('put') @endif

    <div class="form-grid">
        <!-- Left Column: Main Fields -->
        <div>
            <!-- Product Details Card -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="ph ph-package"></i>
                    <h3>Product Details</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-group {{ $errors->has('bom_id') ? 'input-error' : '' }}">
                        <label for="bom_id">Manufacturing-approved Bill of Materials</label>
                        <select name="bom_id" id="bom_id" required>
                            <option value="">Select an active Manufacturing BOM</option>
                            @foreach ($boms as $bom)
                                <option value="{{ $bom->id }}" data-sku="{{ $bom->sku }}" data-name="{{ $bom->name }}" data-description="{{ $bom->description ?? '' }}" @selected(old('bom_id', $listing->bom_id) == $bom->id)>{{ $bom->sku }} &middot; {{ $bom->name }}</option>
                            @endforeach
                        </select>
                        <div class="bom-preview" id="bomPreview">
                            <i class="ph ph-check-circle"></i>
                            <div class="bom-preview-content">
                                <strong id="bomPreviewName"></strong>
                                <span id="bomPreviewSku"></span>
                            </div>
                        </div>
                        @if($errors->has('bom_id'))
                            <span class="field-error">{{ $errors->first('bom_id') }}</span>
                        @else
                            <span class="hint">BOMs are created, changed, and removed only in Manufacturing. Selecting one here only attaches that approved BOM to this storefront listing.</span>
                        @endif
                    </div>

                    <div class="form-group {{ $errors->has('sku') ? 'input-error' : '' }}">
                        <label for="sku">SKU</label>
                        <input name="sku" id="sku" value="{{ old('sku', $listing->sku) }}" required>
                        @if($errors->has('sku'))<span class="field-error">{{ $errors->first('sku') }}</span>@endif
                    </div>

                    <div class="form-group {{ $errors->has('name') ? 'input-error' : '' }}">
                        <label for="name">Listing name</label>
                        <input name="name" id="name" value="{{ old('name', $listing->name) }}" required>
                        @if($errors->has('name'))<span class="field-error">{{ $errors->first('name') }}</span>@endif
                    </div>
                </div>
            </div>

            <!-- Description & Pricing Card -->
            <div class="form-card" style="margin-top: 20px;">
                <div class="form-card-header">
                    <i class="ph ph-currency-circle-dollar"></i>
                    <h3>Description &amp; Pricing</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-group {{ $errors->has('description') ? 'input-error' : '' }}">
                        <label for="description">Description</label>
                        <textarea name="description" id="description">{{ old('description', $listing->description) }}</textarea>
                        @if($errors->has('description'))<span class="field-error">{{ $errors->first('description') }}</span>@endif
                    </div>

                    <div class="form-group {{ $errors->has('price') ? 'input-error' : '' }}">
                        <label for="price">Price (₱)</label>
                        <input type="number" step="0.01" min="0" name="price" id="price" value="{{ old('price', $listing->price) }}" required>
                        @if($errors->has('price'))<span class="field-error">{{ $errors->first('price') }}</span>@endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Sidebar -->
        <div>
            <!-- Media Card -->
            <div class="form-card">
                <div class="form-card-header">
                    <i class="ph ph-image"></i>
                    <h3>Product Image</h3>
                </div>
                <div class="form-card-body">
                    @if($listing->exists && $listing->image_url)
                        <div class="image-preview visible" id="currentImagePreview">
                            <img src="{{ asset('storage/' . $listing->image_url) }}" alt="Current Image">
                        </div>
                    @endif

                    <div class="upload-zone" id="uploadZone">
                        <i class="ph ph-cloud-arrow-up"></i>
                        <div class="upload-label">Click to upload or drag &amp; drop</div>
                        <div class="upload-hint">PNG, JPG, WebP &middot; Max 4MB</div>
                        <input type="file" name="image" id="imageInput" accept="image/*">
                    </div>

                    <div class="image-preview" id="newImagePreview">
                        <img id="newImagePreviewSrc" src="#" alt="New image preview">
                        <button type="button" class="remove-image" id="removeNewImage" title="Remove">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>

                    @if($errors->has('image'))
                        <span class="field-error" style="margin-top: 8px;">{{ $errors->first('image') }}</span>
                    @endif
                </div>
            </div>

            <!-- Publication Card -->
            <div class="form-card" style="margin-top: 20px;">
                <div class="form-card-header">
                    <i class="ph ph-globe"></i>
                    <h3>Publication</h3>
                </div>
                <div class="form-card-body">
                    <div class="form-group">
                        <label>Status</label>
                        <div class="status-pills">
                            <input type="radio" name="status" id="statusDraft" value="draft" {{ old('status', $listing->status ?? 'draft') === 'draft' ? 'checked' : '' }}>
                            <label for="statusDraft" data-status="draft">
                                <span class="dot"></span> Draft
                            </label>

                            <input type="radio" name="status" id="statusActive" value="active" {{ old('status', $listing->status) === 'active' ? 'checked' : '' }}>
                            <label for="statusActive" data-status="active">
                                <span class="dot"></span> Active
                            </label>

                            <input type="radio" name="status" id="statusArchived" value="archived" {{ old('status', $listing->status) === 'archived' ? 'checked' : '' }}>
                            <label for="statusArchived" data-status="archived">
                                <span class="dot"></span> Archived
                            </label>
                        </div>
                        @if($errors->has('status'))<span class="field-error">{{ $errors->first('status') }}</span>@endif
                    </div>
                </div>
            </div>

            <!-- Summary Card (sidebar) -->
            <div class="sidebar-card" style="margin-top: 20px;">
                <div class="sidebar-card-header">
                    <h3>Summary</h3>
                </div>
                <div class="sidebar-card-body">
                    <div class="sidebar-info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value" id="summaryStatus">
                            {{ ucfirst(old('status', $listing->status ?? 'draft')) }}
                        </span>
                    </div>
                    <div class="sidebar-info-row">
                        <span class="info-label">BOM</span>
                        <span class="info-value" id="summaryBom">{{ $listing->bom_id ? 'Selected' : 'None' }}</span>
                    </div>
                    <div class="sidebar-info-row">
                        <span class="info-label">Price</span>
                        <span class="info-value" id="summaryPrice">₱{{ old('price', $listing->price ?? '0.00') }}</span>
                    </div>
                    <div class="sidebar-info-row">
                        <span class="info-label">Store</span>
                        <span class="info-value">{{ $store }}.{{ config('ecommerce.storefront_base_domain') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="action-bar">
        <span style="font-size: 13px; color: var(--c-text-muted);">
            @if($listing->exists)
                Last updated {{ $listing->updated_at ? $listing->updated_at->diffForHumans() : 'never' }}
            @else
                <i class="ph ph-info" style="margin-right: 4px;"></i> Fill in the details and save to create a new listing
            @endif
        </span>
        <div class="btn-group">
            <a class="btn-secondary" href="{{ route('ecommerce.admin.listings') }}">
                <i class="ph ph-x"></i> Cancel
            </a>
            <button class="btn-primary" type="submit">
                <i class="ph ph-floppy-disk"></i>
                {{ $listing->exists ? 'Save Changes' : 'Create Listing' }}
            </button>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const boms = @json($boms->keyBy('id'));
        const bomSelect = document.querySelector('select[name="bom_id"]');
        const skuInput = document.querySelector('input[name="sku"]');
        const nameInput = document.querySelector('input[name="name"]');
        const descInput = document.querySelector('textarea[name="description"]');
        const bomPreview = document.getElementById('bomPreview');
        const bomPreviewName = document.getElementById('bomPreviewName');
        const bomPreviewSku = document.getElementById('bomPreviewSku');

        function showBomPreview(bomId) {
            if (bomId && boms[bomId]) {
                const bom = boms[bomId];
                bomPreviewName.textContent = bom.name;
                bomPreviewSku.textContent = 'SKU: ' + bom.sku;
                bomPreview.classList.add('visible');
            } else {
                bomPreview.classList.remove('visible');
            }
        }

        // Initial state
        showBomPreview(bomSelect.value);

        bomSelect.addEventListener('change', function() {
            const bomId = this.value;
            showBomPreview(bomId);

            // Flash animation class removal helper
            function flashField(el) {
                el.classList.remove('field-autofilled');
                void el.offsetWidth; // force reflow
                el.classList.add('field-autofilled');
            }

            if (bomId && boms[bomId]) {
                const bom = boms[bomId];
                skuInput.value = bom.sku;
                nameInput.value = bom.name;
                descInput.value = bom.description || '';
                flashField(skuInput);
                flashField(nameInput);
                flashField(descInput);
            }
        });

        // Image Upload Drop Zone
        const uploadZone = document.getElementById('uploadZone');
        const imageInput = document.getElementById('imageInput');
        const newImagePreview = document.getElementById('newImagePreview');
        const newImagePreviewSrc = document.getElementById('newImagePreviewSrc');
        const removeNewImage = document.getElementById('removeNewImage');

        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                imageInput.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });

        imageInput.addEventListener('change', function() {
            if (this.files.length) {
                handleFile(this.files[0]);
            }
        });

        function handleFile(file) {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                newImagePreviewSrc.src = e.target.result;
                newImagePreview.classList.add('visible');
                // Hide current image preview if exists
                const currentPreview = document.getElementById('currentImagePreview');
                if (currentPreview) currentPreview.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        removeNewImage.addEventListener('click', function() {
            newImagePreview.classList.remove('visible');
            newImagePreviewSrc.src = '#';
            imageInput.value = '';
        });

        // Status pills → update summary
        document.querySelectorAll('input[name="status"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    document.getElementById('summaryStatus').textContent =
                        this.value.charAt(0).toUpperCase() + this.value.slice(1);
                }
            });
        });

        // Price updates
        document.querySelector('input[name="price"]').addEventListener('input', function() {
            const val = parseFloat(this.value);
            document.getElementById('summaryPrice').textContent =
                '₱' + (isNaN(val) ? '0.00' : val.toFixed(2));
        });
    });
</script>
@endsection
