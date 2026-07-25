@php
    $storefrontCompany = request()->attributes->get('ecommerce_company');
    $store = $storefrontCompany?->ecommerce_slug ?: 'techforge';
@endphp
@extends('ecommerce::admin.layout', ['title' => 'Storefront Listing', 'heading' => $listing->exists ? 'Edit Listing' : 'Add Storefront Listing'])

@section('content')
<form class="card" method="post" enctype="multipart/form-data" action="{{ $listing->exists ? route('ecommerce.admin.listings.update', $listing) : route('ecommerce.admin.listings.store') }}">
    @csrf
    @if ($listing->exists) @method('put') @endif

    @if ($errors->any())<p class="error">{{ $errors->first() }}</p>@endif

    <label>Manufacturing-approved Bill of Materials</label>
    <select name="bom_id" required>
        <option value="">Select an active Manufacturing BOM</option>
        @foreach ($boms as $bom)
            <option value="{{ $bom->id }}" @selected(old('bom_id', $listing->bom_id) == $bom->id)>{{ $bom->sku }} &middot; {{ $bom->name }}</option>
        @endforeach
    </select>
    <p class="hint">BOMs are created, changed, and removed only in Manufacturing. Selecting one here only attaches that approved BOM to this storefront listing.</p>

    <label>SKU</label><input name="sku" value="{{ old('sku', $listing->sku) }}" required>
    <label>Listing name</label><input name="name" value="{{ old('name', $listing->name) }}" required>
    <label>Description</label><textarea name="description">{{ old('description', $listing->description) }}</textarea>
    <label>Price</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $listing->price) }}" required>
    <label>Product image</label>
    @if($listing->exists && $listing->image_url)
        <div style="margin-bottom: 8px;">
            <img src="{{ asset('storage/' . $listing->image_url) }}" alt="Current Image" style="max-height: 120px; border-radius: 4px;">
        </div>
    @endif
    <input type="file" name="image" accept="image/*">
    <label>Publication status</label>
    <select name="status">
        <option value="draft" @selected(old('status', $listing->status) === 'draft')>Draft</option>
        <option value="active" @selected(old('status', $listing->status) === 'active')>Active</option>
        <option value="archived" @selected(old('status', $listing->status) === 'archived')>Archived</option>
    </select>
    <p><button>Save listing</button> <a class="button alt" href="{{ route('ecommerce.admin.listings') }}">Cancel</a></p>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const boms = @json($boms->keyBy('id'));
        const bomSelect = document.querySelector('select[name="bom_id"]');
        const skuInput = document.querySelector('input[name="sku"]');
        const nameInput = document.querySelector('input[name="name"]');
        const descInput = document.querySelector('textarea[name="description"]');

        bomSelect.addEventListener('change', function() {
            const bomId = this.value;
            if (bomId && boms[bomId]) {
                const bom = boms[bomId];
                // Auto-fill the fields with the BOM's data
                skuInput.value = bom.sku;
                nameInput.value = bom.name;
                descInput.value = bom.description || '';
            }
        });
    });
</script>
@endsection
